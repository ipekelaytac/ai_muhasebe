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
     * Get top customers by volume
     */
    public function topCustomers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'limit' => 'nullable|integer|min:1|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        
        $data = $this->reportService->getTopParties(
            $validated['company_id'],
            'receivable',
            $validated['limit'] ?? 10,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
