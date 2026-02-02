<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Services\PeriodService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeriodController extends Controller
{
    protected PeriodService $periodService;
    
    public function __construct(PeriodService $periodService)
    {
        $this->periodService = $periodService;
    }
    
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $year = $request->get('year', now()->year);
        
        $periods = $this->periodService->getPeriods($user->company_id, $year)
            ->load('lockedBy');
        
        return view('accounting.periods.index', compact('periods', 'year'));
    }
    
    public function lock(Request $request, AccountingPeriod $period)
    {
        $user = Auth::user();
        if ($period->company_id != $user->company_id) {
            abort(403);
        }
        
        // Check permission (admin only)
        if (!$user->hasRole('admin')) {
            abort(403, 'Sadece yöneticiler dönem kilitleyebilir.');
        }
        
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $this->periodService->lockPeriod(
                $period->company_id,
                $period->year,
                $period->month,
                $validated['notes'] ?? null
            );
            
            return back()->with('success', 'Dönem başarıyla kilitlendi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function unlock(Request $request, AccountingPeriod $period)
    {
        $user = Auth::user();
        if ($period->company_id != $user->company_id) {
            abort(403);
        }
        
        // Check permission (admin only)
        if (!$user->hasRole('admin')) {
            abort(403, 'Sadece yöneticiler dönem kilidi açabilir.');
        }
        
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $this->periodService->unlockPeriod(
                $period->company_id,
                $period->year,
                $period->month,
                $validated['notes'] ?? null
            );
            
            return back()->with('success', 'Dönem kilidi açıldı.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
