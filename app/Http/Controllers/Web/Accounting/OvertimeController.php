<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Enums\DocumentType;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Services\DocumentService;
use App\Domain\Accounting\Services\PeriodService;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    protected DocumentService $documentService;
    protected PeriodService $periodService;
    
    public function __construct(DocumentService $documentService, PeriodService $periodService)
    {
        $this->documentService = $documentService;
        $this->periodService = $periodService;
    }
    
    /**
     * List overtime documents
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $filters = [
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'type' => DocumentType::OVERTIME_DUE,
            'status' => $request->get('status'),
            'party_id' => $request->get('party_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'open_only' => $request->boolean('open_only', true),
            'search' => $request->get('search'),
        ];
        
        $documents = $this->documentService->listDocuments($filters);
        $parties = Party::where('company_id', $user->company_id)
            ->where('type', 'employee')
            ->active()
            ->orderBy('name')
            ->get();
        
        return view('accounting.overtime.index', compact('documents', 'parties'));
    }
    
    /**
     * Show create form for overtime document
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
        
        $parties = Party::where('company_id', $user->company_id)
            ->where('type', 'employee')
            ->active()
            ->orderBy('name')
            ->get();
        
        $partyId = $request->get('party_id');
        
        // Get employee contract info if party is selected
        $contractInfo = null;
        if ($partyId) {
            $employee = \App\Models\Employee::where('party_id', $partyId)->first();
            if ($employee) {
                $contract = \App\Models\EmployeeContract::where('employee_id', $employee->id)
                    ->where('effective_from', '<=', now()->toDateString())
                    ->where(function ($q) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', now()->toDateString());
                    })
                    ->latest('effective_from')
                    ->first();
                
                if ($contract) {
                    // Calculate hourly overtime rate: (monthly_salary / 225) * 1.5
                    $hourlyOvertimeRate = ($contract->monthly_net_salary / 225) * 1.5;
                    $contractInfo = [
                        'monthly_salary' => $contract->monthly_net_salary,
                        'hourly_overtime_rate' => $hourlyOvertimeRate,
                    ];
                }
            }
        }
        
        return view('accounting.overtime.create', compact('branches', 'parties', 'partyId', 'contractInfo'));
    }
    
    /**
     * Store overtime document
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
            'overtime_date' => 'required|date',
            'hours' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        // Validate party is employee
        $party = Party::findOrFail($validated['party_id']);
        if ($party->type !== 'employee') {
            return back()->withInput()->withErrors(['party_id' => 'Sadece personel seçilebilir.']);
        }
        
        // Set defaults
        $validated['company_id'] = $user->company_id;
        $validated['type'] = DocumentType::OVERTIME_DUE;
        $validated['direction'] = 'payable'; // Company owes employee
        $validated['status'] = 'pending';
        
        // Build description if not provided
        if (empty($validated['description'])) {
            $overtimeDate = \Carbon\Carbon::parse($validated['overtime_date'])->format('d.m.Y');
            $hours = $validated['hours'] ?? 0;
            $rate = $validated['rate'] ?? 0;
            $validated['description'] = "Mesai Tahakkuku - {$overtimeDate}" . ($hours > 0 ? " ({$hours} saat)" : '');
        }
        
        // Build notes
        $notes = [];
        if (!empty($validated['overtime_date'])) {
            $notes[] = "Mesai Tarihi: " . \Carbon\Carbon::parse($validated['overtime_date'])->format('d.m.Y');
        }
        if (!empty($validated['hours'])) {
            $notes[] = "Saat: {$validated['hours']}";
        }
        if (!empty($validated['rate'])) {
            $notes[] = "Saatlik Ücret: " . number_format($validated['rate'], 2) . " ₺";
        }
        if (!empty($validated['notes'])) {
            $notes[] = $validated['notes'];
        }
        $validated['notes'] = implode("\n", $notes);
        
        try {
            $document = $this->documentService->createDocument($validated);
            
            return redirect()->route('accounting.overtime.show', $document)
                ->with('success', 'Mesai tahakkuku başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Show overtime document
     */
    public function show(Document $document)
    {
        $user = Auth::user();
        if ($document->company_id != $user->company_id) {
            abort(403);
        }
        
        if ($document->type !== DocumentType::OVERTIME_DUE) {
            abort(404);
        }
        
        $document->load(['party', 'activeAllocations.payment']);
        
        return view('accounting.overtime.show', compact('document'));
    }
    
    /**
     * Get employee contract info for overtime rate calculation (AJAX)
     */
    public function getContractInfo(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user->company_id) {
                return response()->json(['error' => 'Şirket bilgisi bulunamadı.'], 403);
            }
            
            $request->validate([
                'party_id' => 'required|exists:parties,id',
            ]);
            
            $party = Party::findOrFail($request->party_id);
            if ($party->type !== 'employee') {
                return response()->json(['error' => 'Sadece personel seçilebilir.'], 400);
            }
            
            // Get employee by party_id
            $employee = \App\Models\Employee::where('party_id', $party->id)->first();
            if (!$employee) {
                return response()->json([
                    'error' => 'Personel bulunamadı. Lütfen personelin cari kaydının olduğundan emin olun.',
                    'debug' => 'party_id: ' . $party->id
                ], 404);
            }
            
            // Get active contract
            $contract = \App\Models\EmployeeContract::where('employee_id', $employee->id)
                ->where('effective_from', '<=', now()->toDateString())
                ->where(function ($q) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', now()->toDateString());
                })
                ->latest('effective_from')
                ->first();
            
            if (!$contract) {
                return response()->json([
                    'error' => 'Aktif sözleşme bulunamadı. Lütfen personel için sözleşme oluşturun.',
                    'debug' => 'employee_id: ' . $employee->id
                ], 404);
            }
            
            // Calculate hourly overtime rate: (monthly_salary / 225) * 1.5
            $hourlyOvertimeRate = ($contract->monthly_net_salary / 225) * 1.5;
            
            return response()->json([
                'monthly_salary' => $contract->monthly_net_salary,
                'hourly_overtime_rate' => round($hourlyOvertimeRate, 2),
                'hourly_salary_rate' => round($contract->monthly_net_salary / 225, 2),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Geçersiz veri: ' . implode(', ', $e->errors())], 400);
        } catch (\Exception $e) {
            \Log::error('Overtime contract info error: ' . $e->getMessage(), [
                'party_id' => $request->party_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Hata: ' . $e->getMessage()], 500);
        }
    }
}
