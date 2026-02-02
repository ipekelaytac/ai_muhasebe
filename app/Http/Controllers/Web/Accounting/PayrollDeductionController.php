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
        
        $validated = $request->validate([
            'deductions' => 'required|array|min:1',
            'deductions.*.advance_document_id' => 'required|exists:documents,id',
            'deductions.*.amount' => 'required|numeric|min:0.01',
        ]);
        
        try {
            $result = $this->advanceService->applyAdvanceDeductionToPayroll(
                $salaryDocument->id,
                $validated['deductions']
            );
            
            return redirect()
                ->back()
                ->with('success', "Avans kesintileri başarıyla uygulandı. Toplam kesinti: " . number_format($result['total_deduction_amount'], 2) . " ₺");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
