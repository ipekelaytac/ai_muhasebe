<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use App\Domain\Accounting\Services\AllocationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    protected AllocationService $allocationService;
    
    public function __construct(AllocationService $allocationService)
    {
        $this->allocationService = $allocationService;
    }
    
    /**
     * Allocate payment to documents
     */
    public function allocate(Request $request, int $paymentId): JsonResponse
    {
        $payment = Payment::findOrFail($paymentId);
        
        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.document_id' => 'required|integer|exists:documents,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'allocations.*.allocation_date' => 'nullable|date',
            'allocations.*.notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $allocations = $this->allocationService->allocate($payment, $validated['allocations']);
            
            return response()->json([
                'success' => true,
                'message' => 'Ödeme dağıtıldı.',
                'data' => $allocations,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Auto-allocate payment to oldest documents
     */
    public function autoAllocate(Request $request, int $paymentId): JsonResponse
    {
        $payment = Payment::findOrFail($paymentId);
        
        $validated = $request->validate([
            'party_id' => 'nullable|integer|exists:parties,id',
        ]);
        
        try {
            $allocations = $this->allocationService->autoAllocate($payment, $validated['party_id'] ?? null);
            
            if (empty($allocations)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dağıtılacak açık belge bulunamadı.',
                    'data' => [],
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Ödeme otomatik dağıtıldı.',
                'data' => $allocations,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Get allocation suggestions for a payment
     */
    public function suggestions(int $paymentId): JsonResponse
    {
        $payment = Payment::findOrFail($paymentId);
        
        $suggestions = $this->allocationService->getSuggestions($payment);
        
        return response()->json([
            'success' => true,
            'data' => [
                'payment' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'allocated_amount' => $payment->allocated_amount,
                    'unallocated_amount' => $payment->unallocated_amount,
                ],
                'suggestions' => $suggestions,
            ],
        ]);
    }
    
    /**
     * Cancel an allocation
     */
    public function cancel(Request $request, int $allocationId): JsonResponse
    {
        $allocation = PaymentAllocation::findOrFail($allocationId);
        
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        
        try {
            $this->allocationService->cancelAllocation($allocation, $validated['reason'] ?? null);
            
            return response()->json([
                'success' => true,
                'message' => 'Dağıtım iptal edildi.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Handle overpayment
     */
    public function handleOverpayment(Request $request, int $paymentId): JsonResponse
    {
        $payment = Payment::findOrFail($paymentId);
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
        
        try {
            $advanceDocument = $this->allocationService->handleOverpayment($payment, $validated['amount']);
            
            return response()->json([
                'success' => true,
                'message' => 'Fazla ödeme avans olarak kaydedildi.',
                'data' => $advanceDocument,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
