<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\AllocatePaymentRequest;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use App\Domain\Accounting\Services\AllocationService;
use Illuminate\Http\JsonResponse;

class PaymentAllocationController extends Controller
{
    protected AllocationService $allocationService;

    public function __construct(AllocationService $allocationService)
    {
        $this->allocationService = $allocationService;
    }

    /**
     * Allocate payment to documents
     */
    public function store(AllocatePaymentRequest $request, Payment $payment): JsonResponse
    {
        try {
            // Check period lock
            if ($payment->isInLockedPeriod()) {
                return response()->json([
                    'message' => 'Bu ödeme kilitli bir dönemde. Dağıtım yapılamaz.'
                ], 422);
            }

            $allocations = $this->allocationService->allocate(
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

            // Check period lock
            if ($payment->isInLockedPeriod()) {
                return response()->json([
                    'message' => 'Bu ödeme kilitli bir dönemde. Dağıtım iptal edilemez.'
                ], 422);
            }

            $this->allocationService->cancelAllocation($allocation);

            return response()->json(['message' => 'Allocation cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
