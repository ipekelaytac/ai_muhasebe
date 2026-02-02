<?php

namespace App\Http\Controllers\Web\Accounting;

use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use App\Domain\Accounting\Services\AllocationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AllocationController extends Controller
{
    protected AllocationService $allocationService;
    
    public function __construct(AllocationService $allocationService)
    {
        $this->allocationService = $allocationService;
    }
    
    public function create(Payment $payment)
    {
        $user = Auth::user();
        if ($payment->company_id != $user->company_id) {
            abort(403);
        }
        
        if ($payment->status !== 'confirmed') {
            return back()->withErrors(['error' => 'Sadece onaylanmış ödemeler dağıtılabilir.']);
        }
        
        $payment->load('party');
        
        // Get suggestions
        $suggestions = $this->allocationService->getSuggestions($payment, 20);
        
        return view('accounting.allocations.create', compact('payment', 'suggestions'));
    }
    
    public function store(Request $request, Payment $payment)
    {
        $user = Auth::user();
        if ($payment->company_id != $user->company_id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.document_id' => 'required|exists:documents,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'allocations.*.allocation_date' => 'nullable|date',
            'allocations.*.notes' => 'nullable|string|max:500',
        ]);
        
        try {
            $allocations = $this->allocationService->allocate($payment, $validated['allocations']);
            
            // Check for overpayment
            $payment->refresh();
            $unallocated = $payment->unallocated_amount;
            
            if ($unallocated > 0.01) {
                return redirect()->route('accounting.payments.show', $payment)
                    ->with('success', 'Dağıtım yapıldı.')
                    ->with('info', "Fazla ödeme: " . number_format($unallocated, 2) . " ₺. Fazla ödeme için avans belgesi oluşturabilirsiniz.");
            }
            
            return redirect()->route('accounting.payments.show', $payment)
                ->with('success', 'Dağıtım başarıyla yapıldı.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    public function cancel(PaymentAllocation $allocation)
    {
        $user = Auth::user();
        $payment = $allocation->payment;
        
        if ($payment->company_id != $user->company_id) {
            abort(403);
        }
        
        try {
            $this->allocationService->cancelAllocation($allocation, 'Kullanıcı tarafından iptal edildi');
            
            return back()->with('success', 'Dağıtım iptal edildi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
