<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\AllocatePaymentRequest;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\AllocatePaymentService;
use Illuminate\Http\JsonResponse;

class PaymentAllocationController extends Controller
{
    protected $allocatePaymentService;

    public function __construct(AllocatePaymentService $allocatePaymentService)
    {
        $this->allocatePaymentService = $allocatePaymentService;
    }

    /**
     * Allocate payment to documents
     */
    public function store(AllocatePaymentRequest $request, Payment $payment): JsonResponse
    {
        try {
            $allocations = $this->allocatePaymentService->allocate(
                $payment,
                $request->validated()['allocations']
            );

            return response()->json([
                'message' => 'Payment allocated successfully',
                'allocations' => $allocations
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove allocation
     */
    public function destroy(Payment $payment, PaymentAllocation $allocation): JsonResponse
    {
        try {
            if ($allocation->payment_id !== $payment->id) {
                return response()->json([
                    'message' => 'Allocation does not belong to this payment'
                ], 422);
            }

            $this->allocatePaymentService->removeAllocation($allocation);

            return response()->json(['message' => 'Allocation removed successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
