<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Services\LockPeriodService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AccountingPeriodController extends Controller
{
    protected $lockPeriodService;

    public function __construct(LockPeriodService $lockPeriodService)
    {
        $this->lockPeriodService = $lockPeriodService;
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
            $lockedPeriod = $this->lockPeriodService->lock(
                $period,
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
            $unlockedPeriod = $this->lockPeriodService->unlock(
                $period,
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
