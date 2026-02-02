<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Services\ReportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    protected ReportService $reportService;
    
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    public function index()
    {
        return view('accounting.reports.index');
    }
    
    public function cashBankBalance(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $balances = $this->reportService->getCashBankBalances($user->company_id, $user->branch_id, $asOfDate);
        
        return view('accounting.reports.cash-bank-balance', compact('balances', 'asOfDate'));
    }
    
    public function receivablesAging(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $partyType = $request->get('party_type');
        
        $report = $this->reportService->getAgingReport(
            $user->company_id,
            'receivable',
            $user->branch_id,
            $partyType,
            $asOfDate
        );
        
        return view('accounting.reports.receivables-aging', compact('report', 'asOfDate', 'partyType'));
    }
    
    public function payablesAging(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $partyType = $request->get('party_type');
        
        $report = $this->reportService->getAgingReport(
            $user->company_id,
            'payable',
            $user->branch_id,
            $partyType,
            $asOfDate
        );
        
        return view('accounting.reports.payables-aging', compact('report', 'asOfDate', 'partyType'));
    }
    
    public function employeeDuesAging(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        
        $report = $this->reportService->getEmployeeDuesAging($user->company_id, $user->branch_id);
        $report['as_of_date'] = $asOfDate;
        
        return view('accounting.reports.employee-dues-aging', compact('report', 'asOfDate'));
    }
    
    public function cashflowForecast(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $days = $request->get('days', 90);
        
        $forecast = $this->reportService->getCashflowForecast($user->company_id, $days, $user->branch_id);
        
        return view('accounting.reports.cashflow-forecast', compact('forecast', 'days'));
    }
    
    public function partyStatement(Request $request, \App\Domain\Accounting\Models\Party $party)
    {
        $user = Auth::user();
        if ($party->company_id != $user->company_id) {
            abort(403);
        }
        
        $startDate = $request->get('start_date', now()->subYear()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        
        $statement = $this->reportService->getPartyStatement($party->id, $startDate, $endDate);
        
        return view('accounting.reports.party-statement', compact('statement', 'party', 'startDate', 'endDate'));
    }
    
    public function monthlyPnL(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        
        $pnl = $this->reportService->getMonthlyPnL($user->company_id, $year, $month, $user->branch_id);
        
        return view('accounting.reports.monthly-pnl', compact('pnl', 'year', 'month'));
    }
}
