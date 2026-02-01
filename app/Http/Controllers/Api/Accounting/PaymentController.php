<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Domain\Accounting\Enums\PaymentType;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Services\PaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * List payments
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'type' => 'nullable|string|in:' . implode(',', PaymentType::ALL),
            'direction' => 'nullable|string|in:in,out',
            'status' => 'nullable|string|in:pending,confirmed,cancelled,reversed',
            'party_id' => 'nullable|integer|exists:parties,id',
            'cashbox_id' => 'nullable|integer|exists:cashboxes,id',
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'search' => 'nullable|string|max:100',
            'sort_by' => 'nullable|string|in:payment_date,amount,created_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        
        $payments = $this->paymentService->listPayments($validated);
        
        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }
    
    /**
     * Create payment
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'type' => 'required|string|in:' . implode(',', PaymentType::ALL),
            'party_id' => 'nullable|integer|exists:parties,id',
            'cashbox_id' => 'nullable|integer|exists:cashboxes,id',
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
            'to_cashbox_id' => 'nullable|integer|exists:cashboxes,id',
            'to_bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'fee_amount' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $payment = $this->paymentService->createPayment($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Ödeme kaydedildi.',
                'data' => $payment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get payment details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->getPayment($id);
            
            return response()->json([
                'success' => true,
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ödeme bulunamadı.',
            ], 404);
        }
    }
    
    /**
     * Update payment
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        
        $validated = $request->validate([
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        try {
            $payment = $this->paymentService->updatePayment($payment, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Ödeme güncellendi.',
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Cancel payment
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $payment = $this->paymentService->cancelPayment($payment, $validated['reason'] ?? null);
            
            return response()->json([
                'success' => true,
                'message' => 'Ödeme iptal edildi.',
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Reverse payment
     */
    public function reverse(Request $request, int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $reversalPayment = $this->paymentService->reversePayment($payment, $validated['reason'] ?? null);
            
            return response()->json([
                'success' => true,
                'message' => 'Ters kayıt oluşturuldu.',
                'data' => $reversalPayment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
