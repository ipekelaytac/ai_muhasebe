<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Domain\Accounting\Enums\ChequeStatus;
use App\Domain\Accounting\Models\Cheque;
use App\Domain\Accounting\Services\ChequeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChequeController extends Controller
{
    protected ChequeService $chequeService;
    
    public function __construct(ChequeService $chequeService)
    {
        $this->chequeService = $chequeService;
    }
    
    /**
     * List cheques
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'type' => 'nullable|string|in:received,issued',
            'status' => 'nullable|string|in:' . implode(',', ChequeStatus::ALL),
            'party_id' => 'nullable|integer|exists:parties,id',
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
            'due_start' => 'nullable|date',
            'due_end' => 'nullable|date',
            'in_portfolio' => 'nullable|boolean',
            'for_forecast' => 'nullable|boolean',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:due_date,amount,created_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        
        $cheques = $this->chequeService->listCheques($validated);
        
        return response()->json([
            'success' => true,
            'data' => $cheques,
        ]);
    }
    
    /**
     * Receive a cheque
     */
    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'party_id' => 'required|integer|exists:parties,id',
            'cheque_number' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:50',
            'drawer_name' => 'nullable|string|max:255',
            'drawer_tax_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
            'receive_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $cheque = $this->chequeService->receiveCheque($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Çek alındı.',
                'data' => $cheque,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Issue a cheque
     */
    public function issue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'party_id' => 'required|integer|exists:parties,id',
            'cheque_number' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'bank_account_id' => 'required|integer|exists:bank_accounts,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $cheque = $this->chequeService->issueCheque($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Çek kesildi.',
                'data' => $cheque,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get cheque details
     */
    public function show(int $id): JsonResponse
    {
        $cheque = Cheque::with(['party', 'bankAccount', 'document', 'events', 'endorsedToParty', 'clearedPayment'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $cheque,
        ]);
    }
    
    /**
     * Deposit cheque to bank
     */
    public function deposit(Request $request, int $id): JsonResponse
    {
        $cheque = Cheque::findOrFail($id);
        
        $validated = $request->validate([
            'bank_account_id' => 'required|integer|exists:bank_accounts,id',
        ]);
        
        try {
            $cheque = $this->chequeService->depositCheque($cheque, $validated['bank_account_id']);
            
            return response()->json([
                'success' => true,
                'message' => 'Çek bankaya verildi.',
                'data' => $cheque,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Collect cheque
     */
    public function collect(Request $request, int $id): JsonResponse
    {
        $cheque = Cheque::findOrFail($id);
        
        $validated = $request->validate([
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
        ]);
        
        try {
            $cheque = $this->chequeService->collectCheque($cheque, $validated['bank_account_id'] ?? null);
            
            return response()->json([
                'success' => true,
                'message' => 'Çek tahsil edildi.',
                'data' => $cheque,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Mark cheque as bounced
     */
    public function bounce(Request $request, int $id): JsonResponse
    {
        $cheque = Cheque::findOrFail($id);
        
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'fee' => 'nullable|numeric|min:0',
        ]);
        
        try {
            $cheque = $this->chequeService->bounceCheque($cheque, $validated['reason'], $validated['fee'] ?? 0);
            
            return response()->json([
                'success' => true,
                'message' => 'Çek karşılıksız olarak işaretlendi.',
                'data' => $cheque,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Endorse cheque to another party
     */
    public function endorse(Request $request, int $id): JsonResponse
    {
        $cheque = Cheque::findOrFail($id);
        
        $validated = $request->validate([
            'to_party_id' => 'required|integer|exists:parties,id',
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $cheque = $this->chequeService->endorseCheque($cheque, $validated['to_party_id'], $validated['notes'] ?? null);
            
            return response()->json([
                'success' => true,
                'message' => 'Çek ciro edildi.',
                'data' => $cheque,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Pay issued cheque
     */
    public function pay(int $id): JsonResponse
    {
        $cheque = Cheque::findOrFail($id);
        
        try {
            $cheque = $this->chequeService->payIssuedCheque($cheque);
            
            return response()->json([
                'success' => true,
                'message' => 'Çek ödendi.',
                'data' => $cheque,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Cancel cheque
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $cheque = Cheque::findOrFail($id);
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $cheque = $this->chequeService->cancelCheque($cheque, $validated['reason'] ?? null);
            
            return response()->json([
                'success' => true,
                'message' => 'Çek iptal edildi.',
                'data' => $cheque,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
