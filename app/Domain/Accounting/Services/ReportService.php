<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Enums\ChequeStatus;
use App\Domain\Accounting\Enums\DocumentStatus;
use App\Domain\Accounting\Models\BankAccount;
use App\Domain\Accounting\Models\Cashbox;
use App\Domain\Accounting\Models\Cheque;
use App\Domain\Accounting\Models\Document;
use App\Domain\Accounting\Models\Party;
use App\Domain\Accounting\Models\Payment;
use App\Domain\Accounting\Models\PaymentAllocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get cash and bank balances
     */
    public function getCashBankBalances(int $companyId, ?int $branchId = null, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now()->toDateString();
        
        // Cashbox balances
        $cashboxQuery = Cashbox::where('company_id', $companyId)->active();
        if ($branchId) {
            $cashboxQuery->where('branch_id', $branchId);
        }
        
        $cashboxes = $cashboxQuery->get()->map(function ($cashbox) use ($asOfDate) {
            return [
                'id' => $cashbox->id,
                'code' => $cashbox->code,
                'name' => $cashbox->name,
                'currency' => $cashbox->currency,
                'balance' => $cashbox->getBalanceAsOf($asOfDate),
                'type' => 'cashbox',
            ];
        });
        
        // Bank account balances
        $bankQuery = BankAccount::where('company_id', $companyId)->active();
        if ($branchId) {
            $bankQuery->where('branch_id', $branchId);
        }
        
        $bankAccounts = $bankQuery->get()->map(function ($bank) use ($asOfDate) {
            return [
                'id' => $bank->id,
                'code' => $bank->code,
                'name' => $bank->name,
                'bank_name' => $bank->bank_name,
                'currency' => $bank->currency,
                'balance' => $bank->getBalanceAsOf($asOfDate),
                'type' => 'bank',
            ];
        });
        
        $totalCash = $cashboxes->sum('balance');
        $totalBank = $bankAccounts->sum('balance');
        
        return [
            'as_of_date' => $asOfDate,
            'cashboxes' => $cashboxes,
            'bank_accounts' => $bankAccounts,
            'total_cash' => $totalCash,
            'total_bank' => $totalBank,
            'total' => $totalCash + $totalBank,
        ];
    }
    
    /**
     * Get aging report for payables or receivables
     */
    public function getAgingReport(
        int $companyId,
        string $direction,
        ?int $branchId = null,
        ?string $partyType = null,
        ?string $asOfDate = null
    ): array {
        $asOfDate = Carbon::parse($asOfDate ?? now()->toDateString());
        
        $query = Document::with('party')
            ->where('company_id', $companyId)
            ->where('direction', $direction)
            ->open();
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($partyType) {
            $query->whereHas('party', fn($q) => $q->where('type', $partyType));
        }
        
        $documents = $query->get();
        
        // Calculate aging buckets
        $buckets = [
            'current' => ['min' => null, 'max' => 0, 'label' => 'Vadesi Gelmemiş', 'amount' => 0, 'count' => 0],
            '1_7' => ['min' => 1, 'max' => 7, 'label' => '1-7 Gün', 'amount' => 0, 'count' => 0],
            '8_30' => ['min' => 8, 'max' => 30, 'label' => '8-30 Gün', 'amount' => 0, 'count' => 0],
            '31_60' => ['min' => 31, 'max' => 60, 'label' => '31-60 Gün', 'amount' => 0, 'count' => 0],
            '61_90' => ['min' => 61, 'max' => 90, 'label' => '61-90 Gün', 'amount' => 0, 'count' => 0],
            '90_plus' => ['min' => 91, 'max' => null, 'label' => '90+ Gün', 'amount' => 0, 'count' => 0],
        ];
        
        $byParty = [];
        
        foreach ($documents as $doc) {
            $unpaid = $doc->unpaid_amount;
            if ($unpaid <= 0) continue;
            
            $dueDate = $doc->due_date ?? $doc->document_date;
            $daysOverdue = $asOfDate->diffInDays($dueDate, false) * -1;
            
            // Determine bucket
            $bucket = 'current';
            if ($daysOverdue > 0) {
                if ($daysOverdue <= 7) $bucket = '1_7';
                elseif ($daysOverdue <= 30) $bucket = '8_30';
                elseif ($daysOverdue <= 60) $bucket = '31_60';
                elseif ($daysOverdue <= 90) $bucket = '61_90';
                else $bucket = '90_plus';
            }
            
            $buckets[$bucket]['amount'] += $unpaid;
            $buckets[$bucket]['count']++;
            
            // By party
            $partyId = $doc->party_id;
            if (!isset($byParty[$partyId])) {
                $byParty[$partyId] = [
                    'party_id' => $partyId,
                    'party_name' => $doc->party->name,
                    'party_type' => $doc->party->type,
                    'current' => 0,
                    '1_7' => 0,
                    '8_30' => 0,
                    '31_60' => 0,
                    '61_90' => 0,
                    '90_plus' => 0,
                    'total' => 0,
                ];
            }
            
            $byParty[$partyId][$bucket] += $unpaid;
            $byParty[$partyId]['total'] += $unpaid;
        }
        
        // Sort by total descending
        usort($byParty, fn($a, $b) => $b['total'] <=> $a['total']);
        
        return [
            'as_of_date' => $asOfDate->toDateString(),
            'direction' => $direction,
            'direction_label' => $direction === 'payable' ? 'Borçlar' : 'Alacaklar',
            'summary' => $buckets,
            'total' => array_sum(array_column($buckets, 'amount')),
            'by_party' => array_values($byParty),
        ];
    }
    
    /**
     * Get employee dues aging
     */
    public function getEmployeeDuesAging(int $companyId, ?int $branchId = null): array
    {
        return $this->getAgingReport($companyId, 'payable', $branchId, 'employee');
    }
    
    /**
     * Get cashflow forecast
     */
    public function getCashflowForecast(
        int $companyId,
        int $days = 90,
        ?int $branchId = null
    ): array {
        $today = Carbon::today();
        $endDate = $today->copy()->addDays($days);
        
        // Current balances
        $balances = $this->getCashBankBalances($companyId, $branchId);
        $currentBalance = $balances['total'];
        
        // Expected inflows (receivables due in period)
        $inflows = Document::where('company_id', $companyId)
            ->where('direction', 'receivable')
            ->open()
            ->where(function ($q) use ($today, $endDate) {
                $q->whereBetween('due_date', [$today, $endDate])
                    ->orWhereNull('due_date');
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->map(function ($doc) use ($today) {
                return [
                    'type' => 'receivable',
                    'document_id' => $doc->id,
                    'document_number' => $doc->document_number,
                    'party_name' => $doc->party->name ?? '',
                    'due_date' => ($doc->due_date ?? $today)->toDateString(),
                    'amount' => $doc->unpaid_amount,
                    'description' => $doc->description,
                ];
            });
        
        // Expected outflows (payables due in period)
        $outflows = Document::where('company_id', $companyId)
            ->where('direction', 'payable')
            ->open()
            ->where(function ($q) use ($today, $endDate) {
                $q->whereBetween('due_date', [$today, $endDate])
                    ->orWhereNull('due_date');
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->map(function ($doc) use ($today) {
                return [
                    'type' => 'payable',
                    'document_id' => $doc->id,
                    'document_number' => $doc->document_number,
                    'party_name' => $doc->party->name ?? '',
                    'due_date' => ($doc->due_date ?? $today)->toDateString(),
                    'amount' => $doc->unpaid_amount,
                    'description' => $doc->description,
                ];
            });
        
        // Cheques in portfolio (received - incoming)
        $chequesIn = Cheque::where('company_id', $companyId)
            ->where('type', 'received')
            ->forForecast()
            ->whereBetween('due_date', [$today, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->map(function ($cheque) {
                return [
                    'type' => 'cheque_in',
                    'cheque_id' => $cheque->id,
                    'cheque_number' => $cheque->cheque_number,
                    'party_name' => $cheque->party->name ?? '',
                    'due_date' => $cheque->due_date->toDateString(),
                    'amount' => $cheque->amount,
                    'status' => $cheque->status_label,
                ];
            });
        
        // Cheques issued (outgoing)
        $chequesOut = Cheque::where('company_id', $companyId)
            ->where('type', 'issued')
            ->forForecast()
            ->whereBetween('due_date', [$today, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->map(function ($cheque) {
                return [
                    'type' => 'cheque_out',
                    'cheque_id' => $cheque->id,
                    'cheque_number' => $cheque->cheque_number,
                    'party_name' => $cheque->party->name ?? '',
                    'due_date' => $cheque->due_date->toDateString(),
                    'amount' => $cheque->amount,
                    'status' => $cheque->status_label,
                ];
            });
        
        // Calculate period summaries
        $periods = [];
        $runningBalance = $currentBalance;
        
        foreach ([30, 60, 90] as $periodDays) {
            if ($periodDays > $days) break;
            
            $periodEnd = $today->copy()->addDays($periodDays);
            
            $periodInflows = $inflows->filter(fn($i) => Carbon::parse($i['due_date']) <= $periodEnd)->sum('amount');
            $periodOutflows = $outflows->filter(fn($o) => Carbon::parse($o['due_date']) <= $periodEnd)->sum('amount');
            $periodChequesIn = $chequesIn->filter(fn($c) => Carbon::parse($c['due_date']) <= $periodEnd)->sum('amount');
            $periodChequesOut = $chequesOut->filter(fn($c) => Carbon::parse($c['due_date']) <= $periodEnd)->sum('amount');
            
            $totalIn = $periodInflows + $periodChequesIn;
            $totalOut = $periodOutflows + $periodChequesOut;
            $endingBalance = $currentBalance + $totalIn - $totalOut;
            
            $periods[$periodDays . '_days'] = [
                'label' => "{$periodDays} Gün",
                'end_date' => $periodEnd->toDateString(),
                'expected_inflows' => $totalIn,
                'expected_outflows' => $totalOut,
                'net_change' => $totalIn - $totalOut,
                'projected_balance' => $endingBalance,
            ];
        }
        
        return [
            'as_of_date' => $today->toDateString(),
            'forecast_days' => $days,
            'current_balance' => $currentBalance,
            'periods' => $periods,
            'inflows' => $inflows->values()->toArray(),
            'outflows' => $outflows->values()->toArray(),
            'cheques_in' => $chequesIn->values()->toArray(),
            'cheques_out' => $chequesOut->values()->toArray(),
            'total_inflows' => $inflows->sum('amount') + $chequesIn->sum('amount'),
            'total_outflows' => $outflows->sum('amount') + $chequesOut->sum('amount'),
        ];
    }
    
    /**
     * Get party statement (cari ekstre)
     * 
     * Balance is calculated from documents minus allocations only.
     * Payments are shown for visibility but do NOT directly affect balance.
     */
    public function getPartyStatement(
        int $partyId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $party = Party::findOrFail($partyId);
        
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subYear();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();
        
        // Get all documents (including those before start date for opening balance)
        $allDocuments = Document::where('party_id', $partyId)
            ->where('document_date', '<=', $endDate)
            ->whereNotIn('status', [DocumentStatus::CANCELLED, DocumentStatus::REVERSED])
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();
        
        // Get documents in period
        $documents = $allDocuments->where('document_date', '>=', $startDate);
        
        // Get all payments in period (for display only)
        $payments = Payment::where('party_id', $partyId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'confirmed')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
        
        // Calculate opening balance: unpaid documents before start date
        // Balance = receivables - payables (both calculated from unpaid amounts)
        $openingReceivables = $allDocuments
            ->where('direction', 'receivable')
            ->where('document_date', '<', $startDate)
            ->sum(fn($doc) => $doc->unpaid_amount);
        
        $openingPayables = $allDocuments
            ->where('direction', 'payable')
            ->where('document_date', '<', $startDate)
            ->sum(fn($doc) => $doc->unpaid_amount);
        
        // Opening balance: positive = they owe us, negative = we owe them
        $openingBalance = $openingReceivables - $openingPayables;
        
        // Build statement lines
        $lines = [];
        $runningBalance = $openingBalance;
        
        // Combine documents and payments, sort by date
        $allItems = collect();
        
        foreach ($documents as $doc) {
            $allItems->push([
                'date' => $doc->document_date,
                'sort_order' => 1,
                'type' => 'document',
                'item' => $doc,
            ]);
        }
        
        foreach ($payments as $payment) {
            $allItems->push([
                'date' => $payment->payment_date,
                'sort_order' => 2,
                'type' => 'payment',
                'item' => $payment,
            ]);
        }
        
        $allItems = $allItems->sortBy([
            ['date', 'asc'],
            ['sort_order', 'asc'],
        ]);
        
        foreach ($allItems as $entry) {
            $item = $entry['item'];
            
            if ($entry['type'] === 'document') {
                // Document affects balance based on direction
                $debit = $item->direction === 'receivable' ? $item->total_amount : 0;
                $credit = $item->direction === 'payable' ? $item->total_amount : 0;
                $runningBalance += $debit - $credit;
                
                $lines[] = [
                    'date' => $item->document_date->toDateString(),
                    'type' => 'document',
                    'reference' => $item->document_number,
                    'description' => $item->type_label . ': ' . ($item->description ?? ''),
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $runningBalance,
                    'document_id' => $item->id,
                ];
            } else {
                // Payment shown for visibility but balance is adjusted by allocations
                // We need to find allocations for this payment to adjust balance correctly
                $allocatedAmount = PaymentAllocation::where('payment_id', $item->id)
                    ->where('status', 'active')
                    ->sum('amount');
                
                // Only adjust balance if payment was allocated
                // For receivables: payment reduces what they owe (debit)
                // For payables: payment reduces what we owe (credit)
                if ($allocatedAmount > 0) {
                    // Determine document direction from allocations
                    $allocation = PaymentAllocation::where('payment_id', $item->id)
                        ->where('status', 'active')
                        ->with('document')
                        ->first();
                    
                    if ($allocation && $allocation->document) {
                        $docDirection = $allocation->document->direction;
                        
                        if ($docDirection === 'receivable') {
                            // They paid us - reduces receivable (debit)
                            $debit = $allocatedAmount;
                            $credit = 0;
                            $runningBalance -= $allocatedAmount; // Reduces what they owe
                        } else {
                            // We paid them - reduces payable (credit)
                            $debit = 0;
                            $credit = $allocatedAmount;
                            $runningBalance += $allocatedAmount; // Reduces what we owe
                        }
                    } else {
                        // Fallback: use payment direction
                        $debit = $item->direction === 'in' ? $allocatedAmount : 0;
                        $credit = $item->direction === 'out' ? $allocatedAmount : 0;
                        $runningBalance += $debit - $credit;
                    }
                } else {
                    // Unallocated payment - show but don't affect balance
                    $debit = 0;
                    $credit = 0;
                }
                
                $lines[] = [
                    'date' => $item->payment_date->toDateString(),
                    'type' => 'payment',
                    'reference' => $item->payment_number,
                    'description' => $item->type_label . ': ' . ($item->description ?? '') . ($allocatedAmount > 0 ? ' (Kapama: ' . number_format($allocatedAmount, 2) . ')' : ' (Dağıtılmamış)'),
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $runningBalance,
                    'payment_id' => $item->id,
                ];
            }
        }
        
        // Final closing balance should match unpaid documents
        $closingReceivables = $allDocuments
            ->where('direction', 'receivable')
            ->where('document_date', '<=', $endDate)
            ->sum(fn($doc) => $doc->unpaid_amount);
        
        $closingPayables = $allDocuments
            ->where('direction', 'payable')
            ->where('document_date', '<=', $endDate)
            ->sum(fn($doc) => $doc->unpaid_amount);
        
        $closingBalance = $closingReceivables - $closingPayables;
        
        return [
            'party' => [
                'id' => $party->id,
                'code' => $party->code,
                'name' => $party->name,
                'type' => $party->type,
                'type_label' => $party->type_label,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance, // Use calculated closing balance, not running balance
            'total_debit' => collect($lines)->sum('debit'),
            'total_credit' => collect($lines)->sum('credit'),
            'lines' => $lines,
        ];
    }
    
    /**
     * Get monthly P&L report
     */
    public function getMonthlyPnL(
        int $companyId,
        int $year,
        int $month,
        ?int $branchId = null
    ): array {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();
        
        // Income from receivable documents (by category)
        $income = Document::with('category')
            ->where('company_id', $companyId)
            ->where('direction', 'receivable')
            ->whereNotIn('status', [DocumentStatus::CANCELLED, DocumentStatus::REVERSED])
            ->whereBetween('document_date', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy(fn($doc) => $doc->category_id ?? 0)
            ->map(function ($docs, $categoryId) {
                $category = $docs->first()->category;
                return [
                    'category_id' => $categoryId ?: null,
                    'category_name' => $category?->name ?? 'Kategorisiz',
                    'category_code' => $category?->code ?? '',
                    'amount' => $docs->sum('total_amount'),
                    'count' => $docs->count(),
                ];
            })
            ->values();
        
        // Expenses from payable documents (by category)
        $expenses = Document::with('category')
            ->where('company_id', $companyId)
            ->where('direction', 'payable')
            ->whereNotIn('type', ['payroll_due', 'overtime_due', 'meal_due']) // Separate payroll
            ->whereNotIn('status', [DocumentStatus::CANCELLED, DocumentStatus::REVERSED])
            ->whereBetween('document_date', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy(fn($doc) => $doc->category_id ?? 0)
            ->map(function ($docs, $categoryId) {
                $category = $docs->first()->category;
                return [
                    'category_id' => $categoryId ?: null,
                    'category_name' => $category?->name ?? 'Kategorisiz',
                    'category_code' => $category?->code ?? '',
                    'amount' => $docs->sum('total_amount'),
                    'count' => $docs->count(),
                ];
            })
            ->values();
        
        // Payroll expenses
        $payroll = Document::where('company_id', $companyId)
            ->whereIn('type', ['payroll_due', 'overtime_due', 'meal_due'])
            ->whereNotIn('status', [DocumentStatus::CANCELLED, DocumentStatus::REVERSED])
            ->whereBetween('document_date', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('type, SUM(total_amount) as amount, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->keyBy('type');
        
        $totalIncome = $income->sum('amount');
        $totalExpenses = $expenses->sum('amount');
        $totalPayroll = $payroll->sum('amount');
        $netIncome = $totalIncome - $totalExpenses - $totalPayroll;
        
        return [
            'period' => [
                'year' => $year,
                'month' => $month,
                'label' => $startDate->translatedFormat('F Y'),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'income' => [
                'categories' => $income,
                'total' => $totalIncome,
            ],
            'expenses' => [
                'categories' => $expenses,
                'total' => $totalExpenses,
            ],
            'payroll' => [
                'salary' => $payroll->get('payroll_due')?->amount ?? 0,
                'overtime' => $payroll->get('overtime_due')?->amount ?? 0,
                'meal' => $payroll->get('meal_due')?->amount ?? 0,
                'total' => $totalPayroll,
            ],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses + $totalPayroll,
                'gross_profit' => $totalIncome - $totalExpenses,
                'net_income' => $netIncome,
            ],
        ];
    }
    
    /**
     * Get top parties by volume
     * 
     * @param int $companyId
     * @param string $direction - 'receivable' or 'payable'
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $partyType - Filter by party type: 'customer', 'supplier', 'employee', or null for all
     */
    public function getTopParties(
        int $companyId,
        string $direction,
        int $limit = 10,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $partyType = null
    ): array {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subYear();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();
        
        $query = Document::with('party')
            ->where('company_id', $companyId)
            ->where('direction', $direction)
            ->whereNotIn('status', [DocumentStatus::CANCELLED, DocumentStatus::REVERSED])
            ->whereBetween('document_date', [$startDate, $endDate]);
        
        // Filter by party type if provided
        if ($partyType && $partyType !== 'all') {
            $query->whereHas('party', function ($q) use ($partyType) {
                $q->where('type', $partyType);
            });
        }
        
        $results = $query
            ->selectRaw('party_id, SUM(total_amount) as total_amount, COUNT(*) as document_count')
            ->groupBy('party_id')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();
        
        return $results->map(function ($row) {
            $party = Party::find($row->party_id);
            return [
                'party_id' => $row->party_id,
                'party_name' => $party?->name ?? 'Unknown',
                'party_code' => $party?->code ?? '',
                'party_type' => $party?->type ?? '',
                'total_amount' => $row->total_amount,
                'document_count' => $row->document_count,
            ];
        })->toArray();
    }
}
