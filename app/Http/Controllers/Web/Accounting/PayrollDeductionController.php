<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Services\EmployeeAdvanceService;
use App\Domain\Accounting\Services\PeriodService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollDeductionController extends Controller
{
    protected EmployeeAdvanceService $advanceService;
    protected PeriodService $periodService;
    
    public function __construct(
        EmployeeAdvanceService $advanceService,
        PeriodService $periodService
    ) {
        $this->advanceService = $advanceService;
        $this->periodService = $periodService;
    }
    
    /**
     * Show deduction form for salary document
     */
    public function show(Document $salaryDocument)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        // Validate document
        if ($salaryDocument->type !== DocumentType::PAYROLL_DUE) {
            abort(404, 'Bu sayfa sadece maaş tahakkuku belgeleri için kullanılabilir.');
        }
        
        if ($salaryDocument->company_id !== $user->company_id) {
            abort(403, 'Bu belgeye erişim yetkiniz yok.');
        }
        
        $party = $salaryDocument->party;
        if ($party->type !== 'employee') {
            abort(404, 'Bu belge bir personele ait olmalıdır.');
        }
        
        // Get open advances for suggestion
        $openAdvances = $this->advanceService->suggestOpenAdvancesForEmployee($party->id, $salaryDocument->document_date);
        
        // Check if period is locked
        $isLocked = !$this->periodService->isDateInOpenPeriod($user->company_id, $salaryDocument->document_date);
        
        // Get existing deductions (internal_offset payments allocated to this salary document)
        $existingDeductions = \App\Domain\Accounting\Models\Payment::where('type', \App\Domain\Accounting\Enums\PaymentType::INTERNAL_OFFSET)
            ->where('reference_type', Document::class)
            ->where('reference_id', $salaryDocument->id)
            ->with(['allocations.document'])
            ->get();
        
        return view('accounting.payroll.deductions', compact(
            'salaryDocument',
            'party',
            'openAdvances',
            'isLocked',
            'existingDeductions'
        ));
    }
    
    /**
     * Apply advance deductions to payroll
     */
    public function store(Request $request, Document $salaryDocument)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        // PRIORITY: If installment_id is provided in request, use that document instead of route parameter
        // This allows the form to specify which installment to deduct from
        // Route model binding may have resolved a different document, so we override it here
        if ($request->has('installment_id') && $request->installment_id) {
            $selectedDocument = Document::findOrFail($request->installment_id);
            
            // Validate the selected document
            if ($selectedDocument->type !== DocumentType::PAYROLL_DUE) {
                abort(404, 'Seçilen belge bir maaş tahakkuku belgesi olmalıdır.');
            }
            
            if ($selectedDocument->company_id !== $user->company_id) {
                abort(403, 'Seçilen belgeye erişim yetkiniz yok.');
            }
            
            // Use the selected document instead of route parameter
            $salaryDocument = $selectedDocument;
            
            // Log for debugging (remove in production)
            \Log::info('Advance deduction: Using installment_id from form', [
                'installment_id' => $request->installment_id,
                'document_id' => $salaryDocument->id,
                'document_number' => $salaryDocument->document_number ?? null,
                'route_document_id' => $request->route('salaryDocument')->id ?? null,
            ]);
        } else {
            // Log for debugging (remove in production)
            \Log::info('Advance deduction: Using route parameter', [
                'route_document_id' => $salaryDocument->id,
                'document_number' => $salaryDocument->document_number ?? null,
                'has_installment_id' => $request->has('installment_id'),
                'installment_id_value' => $request->get('installment_id'),
            ]);
        }
        
        // Validate document
        if ($salaryDocument->type !== DocumentType::PAYROLL_DUE) {
            abort(404, 'Bu sayfa sadece maaş tahakkuku belgeleri için kullanılabilir.');
        }
        
        if ($salaryDocument->company_id !== $user->company_id) {
            abort(403, 'Bu belgeye erişim yetkiniz yok.');
        }
        
        $party = $salaryDocument->party;
        if ($party->type !== 'employee') {
            abort(404, 'Bu belge bir personele ait olmalıdır.');
        }
        
        // Support both old format (deductions array) and new format (advance_document_ids array)
        if ($request->has('advance_document_ids')) {
            // New format from payroll item modal
            $validated = $request->validate([
                'advance_document_ids' => 'required|array|min:1',
                'advance_document_ids.*' => 'required|exists:documents,id',
                'advance_amounts' => 'required|array',
                'advance_amounts.*' => 'required|numeric|min:0.01',
                'payroll_item_id' => 'nullable|exists:payroll_items,id',
                'installment_id' => 'nullable|exists:documents,id',
            ]);
            
            // Build deductions array
            $deductions = [];
            foreach ($validated['advance_document_ids'] as $advanceDocId) {
                $amount = $validated['advance_amounts'][$advanceDocId] ?? 0;
                if ($amount > 0) {
                    $deductions[] = [
                        'advance_document_id' => $advanceDocId,
                        'amount' => $amount,
                    ];
                }
            }
        } else {
            // Old format
            $validated = $request->validate([
                'deductions' => 'required|array|min:1',
                'deductions.*.advance_document_id' => 'required|exists:documents,id',
                'deductions.*.amount' => 'required|numeric|min:0.01',
            ]);
            $deductions = $validated['deductions'];
        }
        
        if (empty($deductions)) {
            return back()->withErrors(['error' => 'En az bir avans seçilmeli ve tutar girilmelidir.']);
        }
        
        try {
            // Get installment model for payroll_installment_id
            $installment = \App\Models\PayrollInstallment::where('accounting_document_id', $salaryDocument->id)->first();
            
            $result = $this->advanceService->applyAdvanceDeductionToPayroll(
                $salaryDocument->id,
                $deductions
            );
            
            // Update allocations with payroll_installment_id if available
            if ($installment) {
                foreach ($result['allocations'] as $allocation) {
                    if ($allocation->document_id == $salaryDocument->id) {
                        $allocation->update(['payroll_installment_id' => $installment->id]);
                    }
                }
            }
            
            $redirectRoute = $request->get('payroll_item_id') 
                ? route('admin.payroll.item', $request->get('payroll_item_id'))
                : route('accounting.documents.show', $salaryDocument->id);
            
            return redirect($redirectRoute)
                ->with('success', "Avans kesintileri başarıyla uygulandı. Toplam kesinti: " . number_format($result['total_deduction_amount'], 2) . " ₺");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
