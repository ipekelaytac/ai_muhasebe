<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Services\PeriodService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AccountingPeriodController extends Controller
{
    protected PeriodService $periodService;

    public function __construct(PeriodService $periodService)
    {
        $this->periodService = $periodService;
    }

    /**
     * Display a listing of periods
     */
    public function index(Request $request): JsonResponse
    {
        $query = AccountingPeriod::query()
            ->forCompany($request->get('company_id'))
            ->forBranch($request->get('branch_id'));

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('year')) {
            $query->where('year', $request->get('year'));
        }

        $periods = $query->with(['company', 'branch', 'lockedBy'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($periods);
    }

    /**
     * Lock period
     */
    public function lock(Request $request, AccountingPeriod $period): JsonResponse
    {
        try {
            $lockedPeriod = $this->periodService->lockPeriod(
                $period->company_id,
                $period->year,
                $period->month,
                $request->get('notes')
            );

            return response()->json([
                'message' => 'Period locked successfully',
                'period' => $lockedPeriod
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Unlock period
     */
    public function unlock(Request $request, AccountingPeriod $period): JsonResponse
    {
        try {
            $unlockedPeriod = $this->periodService->unlockPeriod(
                $period->company_id,
                $period->year,
                $period->month,
                $request->get('reason')
            );

            return response()->json([
                'message' => 'Period unlocked successfully',
                'period' => $unlockedPeriod
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
