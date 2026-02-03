<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Services\DocumentService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDebtController extends Controller
{
    protected DocumentService $documentService;
    
    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }
    
    /**
     * Show form to create employee debt
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $branches = Branch::where('company_id', $user->company_id)->get();
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        // Get employees with party_id
        $employees = Employee::whereHas('party')
            ->where('company_id', $user->company_id)
            ->when($user->branch_id, function($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->with('party')
            ->orderBy('full_name')
            ->get();
        
        // Get employee parties
        $parties = Party::where('company_id', $user->company_id)
            ->where('type', 'employee')
            ->active()
            ->orderBy('name')
            ->get();
        
        $employeeId = $request->get('employee_id');
        $partyId = $request->get('party_id');
        
        // If employee_id provided, get party_id
        if ($employeeId && !$partyId) {
            $employee = Employee::find($employeeId);
            if ($employee && $employee->party_id) {
                $partyId = $employee->party_id;
            }
        }
        
        return view('accounting.employees.debts.create', compact(
            'branches', 
            'employees', 
            'parties', 
            'employeeId', 
            'partyId'
        ));
    }
    
    /**
     * Store employee debt as expense_due document
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'party_id' => 'required|exists:parties,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'total_amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        // Verify party is an employee
        $party = Party::findOrFail($validated['party_id']);
        if ($party->type !== 'employee') {
            return back()->withInput()->withErrors(['party_id' => 'Seçilen cari bir çalışan değil.']);
        }
        
        // Verify party belongs to user's company
        if ($party->company_id != $user->company_id) {
            abort(403, 'Bu cariye erişim yetkiniz yok.');
        }
        
        $validated['company_id'] = $user->company_id;
        $validated['type'] = DocumentType::EXPENSE_DUE;
        // For employee debts, direction should be "receivable" (company is owed)
        // But expense_due defaults to "payable", so we override it
        $validated['direction'] = 'receivable';
        
        // Set due_date to document_date if not provided
        if (empty($validated['due_date'])) {
            $validated['due_date'] = $validated['document_date'];
        }
        
        try {
            $document = $this->documentService->createDocument($validated);
            
            // Redirect based on context
            $redirectTo = $request->get('redirect_to');
            if ($redirectTo === 'payroll' && $request->get('payroll_item_id')) {
                return redirect()->route('admin.payroll.item', $request->get('payroll_item_id'))
                    ->with('success', 'Çalışan borcu başarıyla oluşturuldu.');
            }
            
            return redirect()->route('accounting.documents.show', $document)
                ->with('success', 'Çalışan borcu başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
