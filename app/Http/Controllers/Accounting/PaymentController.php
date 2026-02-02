<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
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
            $payment = $this->paymentService->createPayment($request->validated());

            return response()->json($payment->load([
                'party',
                'cashbox',
                'bankAccount'
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
            // Check period lock
            if ($payment->isInLockedPeriod()) {
                return response()->json([
                    'message' => 'Bu ödeme kilitli bir dönemde. Düzenleme yapılamaz. Ters kayıt kullanın.'
                ], 422);
            }

            // Check if payment can be modified
            if (!$payment->canModify()) {
                return response()->json([
                    'message' => 'Bu ödeme değiştirilemez. Dağıtımı olan ödemeler değiştirilemez.'
                ], 422);
            }

            $payment = $this->paymentService->updatePayment($payment, $request->validated());

            return response()->json($payment->load([
                'party',
                'cashbox',
                'bankAccount'
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
            // Check period lock
            if ($payment->isInLockedPeriod()) {
                return response()->json([
                    'message' => 'Bu ödeme kilitli bir dönemde. Silinemez. İptal edin veya ters kayıt kullanın.'
                ], 422);
            }

            // Use service to cancel (soft delete)
            $this->paymentService->cancelPayment($payment, 'API üzerinden silindi');

            return response()->json(['message' => 'Payment cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
