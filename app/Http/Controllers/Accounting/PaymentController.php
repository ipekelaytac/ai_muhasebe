<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Services\RecordPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    protected $recordPaymentService;

    public function __construct(RecordPaymentService $recordPaymentService)
    {
        $this->recordPaymentService = $recordPaymentService;
    }

    /**
     * Display a listing of payments
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->forCompany($request->get('company_id'))
            ->forBranch($request->get('branch_id'));

        if ($request->has('payment_type')) {
            $query->byPaymentType($request->get('payment_type'));
        }

        if ($request->has('direction')) {
            $query->where('direction', $request->get('direction'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('party_id')) {
            $query->where('party_id', $request->get('party_id'));
        }

        if ($request->has('cashbox_id')) {
            $query->where('cashbox_id', $request->get('cashbox_id'));
        }

        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->get('bank_account_id'));
        }

        if ($request->has('unallocated_only')) {
            $query->unallocated();
        }

        $payments = $query->with([
            'party',
            'cashbox',
            'bankAccount',
            'fromCashbox',
            'toCashbox',
            'fromBankAccount',
            'toBankAccount',
            'allocations.document'
        ])
            ->orderBy('payment_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($payments);
    }

    /**
     * Store a newly created payment
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->recordPaymentService->create($request->validated());

            return response()->json($payment->load([
                'party',
                'cashbox',
                'bankAccount',
                'accountingPeriod'
            ]), 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified payment
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load([
            'party',
            'cashbox',
            'bankAccount',
            'fromCashbox',
            'toCashbox',
            'fromBankAccount',
            'toBankAccount',
            'allocations.document',
            'accountingPeriod'
        ]);

        return response()->json($payment);
    }

    /**
     * Update the specified payment
     */
    public function update(StorePaymentRequest $request, Payment $payment): JsonResponse
    {
        try {
            if ($payment->isLocked()) {
                return response()->json([
                    'message' => 'Cannot update payment in locked period'
                ], 422);
            }

            if ($payment->status !== 'draft') {
                return response()->json([
                    'message' => 'Can only update draft payments'
                ], 422);
            }

            $payment->update($request->validated());

            return response()->json($payment->fresh()->load([
                'party',
                'cashbox',
                'bankAccount',
                'accountingPeriod'
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified payment
     */
    public function destroy(Payment $payment): JsonResponse
    {
        try {
            if ($payment->isLocked()) {
                return response()->json([
                    'message' => 'Cannot delete payment in locked period'
                ], 422);
            }

            if ($payment->allocations()->count() > 0) {
                return response()->json([
                    'message' => 'Cannot delete payment with allocations'
                ], 422);
            }

            $payment->status = 'canceled';
            $payment->save();
            $payment->delete();

            return response()->json(['message' => 'Payment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
