<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Domain\Accounting\Services\PeriodService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeriodController extends Controller
{
    protected PeriodService $periodService;
    
    public function __construct(PeriodService $periodService)
    {
        $this->periodService = $periodService;
    }
    
    /**
     * List periods for a company
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);
        
        $periods = $this->periodService->getPeriods($validated['company_id'], $validated['year'] ?? null);
        
        return response()->json([
            'success' => true,
            'data' => $periods,
        ]);
    }
    
    /**
     * Get open periods
     */
    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
        ]);
        
        $periods = $this->periodService->getOpenPeriods($validated['company_id']);
        
        return response()->json([
            'success' => true,
            'data' => $periods,
        ]);
    }
    
    /**
     * Lock a period
     */
    public function lock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $period = $this->periodService->lockPeriod(
                $validated['company_id'],
                $validated['year'],
                $validated['month'],
                $validated['notes'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Dönem kilitlendi: ' . $period->getPeriodLabel(),
                'data' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Unlock a period (requires special permission)
     */
    public function unlock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $period = $this->periodService->unlockPeriod(
                $validated['company_id'],
                $validated['year'],
                $validated['month'],
                $validated['notes'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Dönem kilidi açıldı: ' . $period->getPeriodLabel(),
                'data' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
    /**
     * Close a period permanently
     */
    public function close(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        try {
            $period = $this->periodService->closePeriod(
                $validated['company_id'],
                $validated['year'],
                $validated['month']
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Dönem kapatıldı: ' . $period->getPeriodLabel(),
                'data' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
