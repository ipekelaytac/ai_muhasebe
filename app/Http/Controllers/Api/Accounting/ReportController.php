<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Domain\Accounting\Services\ReportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;
    
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    /**
     * Get cash and bank balances
     */
    public function cashBankBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'as_of_date' => 'nullable|date',
        ]);
        
        $data = $this->reportService->getCashBankBalances(
            $validated['company_id'],
            $validated['branch_id'] ?? null,
            $validated['as_of_date'] ?? null
        );
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * Get payables aging report
     */
    public function payablesAging(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'party_type' => 'nullable|string|in:customer,supplier,employee,other',
            'as_of_date' => 'nullable|date',
        ]);
        
        $data = $this->reportService->getAgingReport(
            $validated['company_id'],
            'payable',
            $validated['branch_id'] ?? null,
            $validated['party_type'] ?? null,
            $validated['as_of_date'] ?? null
        );
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * Get receivables aging report
     */
    public function receivablesAging(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'party_type' => 'nullable|string|in:customer,supplier,employee,other',
            'as_of_date' => 'nullable|date',
        ]);
        
        $data = $this->reportService->getAgingReport(
            $validated['company_id'],
            'receivable',
            $validated['branch_id'] ?? null,
            $validated['party_type'] ?? null,
            $validated['as_of_date'] ?? null
        );
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * Get employee dues aging
     */
    public function employeeDuesAging(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);
        
        $data = $this->reportService->getEmployeeDuesAging(
            $validated['company_id'],
            $validated['branch_id'] ?? null
        );
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * Get cashflow forecast
     */
    public function cashflowForecast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'days' => 'nullable|integer|min:7|max:365',
        ]);
        
        $data = $this->reportService->getCashflowForecast(
            $validated['company_id'],
            $validated['days'] ?? 90,
            $validated['branch_id'] ?? null
        );
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * Get party statement (cari ekstre)
     */
    public function partyStatement(Request $request, int $partyId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        
        try {
            $data = $this->reportService->getPartyStatement(
                $partyId,
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            );
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
    
    /**
     * Get monthly P&L
     */
    public function monthlyPnL(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        $data = $this->reportService->getMonthlyPnL(
            $validated['company_id'],
            $validated['year'],
            $validated['month'],
            $validated['branch_id'] ?? null
        );
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * Get top suppliers by volume
     */
    public function topSuppliers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'limit' => 'nullable|integer|min:1|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        
        $data = $this->reportService->getTopParties(
            $validated['company_id'],
            'payable',
            $validated['limit'] ?? 10,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * Get top parties by volume (generic endpoint)
     * 
     * @param string $type - 'customer', 'supplier', 'employee', or 'all'
     */
    public function topParties(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'type' => 'nullable|string|in:customer,supplier,employee,all',
            'direction' => 'nullable|string|in:receivable,payable',
            'limit' => 'nullable|integer|min:1|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        
        // Determine direction from type if not provided
        $direction = $validated['direction'] ?? null;
        if (!$direction && isset($validated['type'])) {
            $direction = match($validated['type']) {
                'customer' => 'receivable',
                'supplier' => 'payable',
                default => 'receivable', // Default for employee/all
            };
        }
        $direction = $direction ?? 'receivable'; // Final fallback
        
        $data = $this->reportService->getTopParties(
            $validated['company_id'],
            $direction,
            $validated['limit'] ?? 10,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            $validated['type'] ?? null // Filter by party type if provided
        );
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * Get top customers by volume
     * @deprecated Use topParties with type=customer instead
     * Kept for backward compatibility
     */
    public function topCustomers(Request $request): JsonResponse
    {
        // Redirect to topParties with type=customer
        $request->merge(['type' => 'customer', 'direction' => 'receivable']);
        return $this->topParties($request);
    }
}
