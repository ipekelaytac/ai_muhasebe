<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Cashbox;
use App\Models\BankAccount;
use App\Models\Party;
use App\Models\Cheque;
use App\Models\FinanceCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Cash/Bank balance report
     */
    public function cashBankBalance(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $branchId = $request->get('branch_id');

        $cashboxes = Cashbox::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->active()
            ->get()
            ->map(function ($cashbox) {
                return [
                    'id' => $cashbox->id,
                    'name' => $cashbox->name,
                    'code' => $cashbox->code,
                    'balance' => $cashbox->balance,
                    'type' => 'cashbox',
                ];
            });

        $bankAccounts = BankAccount::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->active()
            ->get()
            ->map(function ($bankAccount) {
                return [
                    'id' => $bankAccount->id,
                    'name' => $bankAccount->name,
                    'code' => $bankAccount->code,
                    'bank_name' => $bankAccount->bank_name,
                    'account_number' => $bankAccount->account_number,
                    'currency' => $bankAccount->currency,
                    'balance' => $bankAccount->balance,
                    'type' => 'bank_account',
                ];
            });

        $totalCash = $cashboxes->sum('balance');
        $totalBank = $bankAccounts->sum('balance');

        return response()->json([
            'cashboxes' => $cashboxes,
            'bank_accounts' => $bankAccounts,
            'totals' => [
                'cash' => $totalCash,
                'bank' => $totalBank,
                'total' => $totalCash + $totalBank,
            ],
            'as_of_date' => now()->toDateString(),
        ]);
    }

    /**
     * Payables aging report
     */
    public function payablesAging(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $branchId = $request->get('branch_id');
        $asOfDate = $request->get('as_of_date', now()->toDateString());

        $documents = Document::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->payable()
            ->posted()
            ->unpaid()
            ->where('due_date', '<=', $asOfDate)
            ->with(['party', 'category'])
            ->get();

        $buckets = [
            '0-7' => ['days' => [0, 7], 'amount' => 0, 'documents' => []],
            '8-30' => ['days' => [8, 30], 'amount' => 0, 'documents' => []],
            '31-60' => ['days' => [31, 60], 'amount' => 0, 'documents' => []],
            '61-90' => ['days' => [61, 90], 'amount' => 0, 'documents' => []],
            '90+' => ['days' => [91, 9999], 'amount' => 0, 'documents' => []],
        ];

        foreach ($documents as $document) {
            $daysPastDue = Carbon::parse($asOfDate)->diffInDays(Carbon::parse($document->due_date));
            $unpaidAmount = $document->unpaid_amount;

            foreach ($buckets as $key => &$bucket) {
                if ($daysPastDue >= $bucket['days'][0] && $daysPastDue <= $bucket['days'][1]) {
                    $bucket['amount'] += $unpaidAmount;
                    $bucket['documents'][] = [
                        'id' => $document->id,
                        'document_number' => $document->document_number,
                        'party_name' => $document->party->name,
                        'due_date' => $document->due_date->toDateString(),
                        'days_past_due' => $daysPastDue,
                        'unpaid_amount' => $unpaidAmount,
                    ];
                    break;
                }
            }
        }

        $total = array_sum(array_column($buckets, 'amount'));

        return response()->json([
            'as_of_date' => $asOfDate,
            'buckets' => $buckets,
            'total' => $total,
        ]);
    }

    /**
     * Receivables aging report
     */
    public function receivablesAging(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $branchId = $request->get('branch_id');
        $asOfDate = $request->get('as_of_date', now()->toDateString());

        $documents = Document::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->receivable()
            ->posted()
            ->unpaid()
            ->where('due_date', '<=', $asOfDate)
            ->with(['party', 'category'])
            ->get();

        $buckets = [
            '0-7' => ['days' => [0, 7], 'amount' => 0, 'documents' => []],
            '8-30' => ['days' => [8, 30], 'amount' => 0, 'documents' => []],
            '31-60' => ['days' => [31, 60], 'amount' => 0, 'documents' => []],
            '61-90' => ['days' => [61, 90], 'amount' => 0, 'documents' => []],
            '90+' => ['days' => [91, 9999], 'amount' => 0, 'documents' => []],
        ];

        foreach ($documents as $document) {
            $daysPastDue = Carbon::parse($asOfDate)->diffInDays(Carbon::parse($document->due_date));
            $unpaidAmount = $document->unpaid_amount;

            foreach ($buckets as $key => &$bucket) {
                if ($daysPastDue >= $bucket['days'][0] && $daysPastDue <= $bucket['days'][1]) {
                    $bucket['amount'] += $unpaidAmount;
                    $bucket['documents'][] = [
                        'id' => $document->id,
                        'document_number' => $document->document_number,
                        'party_name' => $document->party->name,
                        'due_date' => $document->due_date->toDateString(),
                        'days_past_due' => $daysPastDue,
                        'unpaid_amount' => $unpaidAmount,
                    ];
                    break;
                }
            }
        }

        $total = array_sum(array_column($buckets, 'amount'));

        return response()->json([
            'as_of_date' => $asOfDate,
            'buckets' => $buckets,
            'total' => $total,
        ]);
    }

    /**
     * Employee dues aging report
     */
    public function employeeDuesAging(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $branchId = $request->get('branch_id');
        $asOfDate = $request->get('as_of_date', now()->toDateString());

        $documents = Document::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->payable()
            ->posted()
            ->unpaid()
            ->whereIn('type', ['payroll_due', 'overtime_due', 'meal_due']) // Schema uses 'type', not 'document_type'
            ->where('due_date', '<=', $asOfDate)
            ->with(['party'])
            ->get();

        $byEmployee = [];
        foreach ($documents as $document) {
            $employeeId = $document->party_id;
            if (!isset($byEmployee[$employeeId])) {
                $byEmployee[$employeeId] = [
                    'party_id' => $employeeId,
                    'party_name' => $document->party->name,
                    'total_due' => 0,
                    'by_type' => [
                        'payroll_due' => 0,
                        'overtime_due' => 0,
                        'meal_due' => 0,
                    ],
                    'documents' => [],
                ];
            }

            $byEmployee[$employeeId]['total_due'] += $document->unpaid_amount;
            $byEmployee[$employeeId]['by_type'][$document->type] += $document->unpaid_amount; // Schema uses 'type'
            $byEmployee[$employeeId]['documents'][] = [
                'id' => $document->id,
                'document_number' => $document->document_number,
                'document_type' => $document->type, // Map 'type' to 'document_type' for API response
                'due_date' => $document->due_date->toDateString(),
                'unpaid_amount' => $document->unpaid_amount,
            ];
        }

        return response()->json([
            'as_of_date' => $asOfDate,
            'employees' => array_values($byEmployee),
            'total' => array_sum(array_column($byEmployee, 'total_due')),
        ]);
    }

    /**
     * Cashflow forecast
     */
    public function cashflowForecast(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $branchId = $request->get('branch_id');
        $days = $request->get('days', 30);

        $endDate = Carbon::now()->addDays($days);

        // Expected inflows from receivables
        $receivables = Document::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->receivable()
            ->posted()
            ->unpaid()
            ->whereBetween('due_date', [now()->toDateString(), $endDate->toDateString()])
            ->selectRaw('due_date, SUM(unpaid_amount) as amount')
            ->groupBy('due_date')
            ->get()
            ->keyBy(function ($item) {
                return $item->due_date->toDateString();
            });

        // Expected outflows from payables
        $payables = Document::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->payable()
            ->posted()
            ->unpaid()
            ->whereBetween('due_date', [now()->toDateString(), $endDate->toDateString()])
            ->selectRaw('due_date, SUM(unpaid_amount) as amount')
            ->groupBy('due_date')
            ->get()
            ->keyBy(function ($item) {
                return $item->due_date->toDateString();
            });

        // Cheques affecting cashflow
        $cheques = Cheque::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->whereBetween('due_date', [now()->toDateString(), $endDate->toDateString()])
            ->where('status', 'in_portfolio')
            ->get();

        $chequesReceived = $cheques->where('type', 'received')
            ->groupBy(function ($cheque) {
                return $cheque->due_date->toDateString();
            })
            ->map(function ($group) {
                return $group->sum('amount');
            });

        $chequesIssued = $cheques->where('type', 'issued')
            ->groupBy(function ($cheque) {
                return $cheque->due_date->toDateString();
            })
            ->map(function ($group) {
                return $group->sum('amount');
            });

        // Build daily forecast
        $forecast = [];
        $runningBalance = 0; // Start from current cash+bank balance

        $cashboxes = Cashbox::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->active()
            ->get();
        $bankAccounts = BankAccount::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->active()
            ->get();

        $runningBalance = $cashboxes->sum('balance') + $bankAccounts->sum('balance');

        $currentDate = Carbon::now();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();
            $inflow = ($receivables->get($dateStr)->amount ?? 0) + ($chequesReceived->get($dateStr) ?? 0);
            $outflow = ($payables->get($dateStr)->amount ?? 0) + ($chequesIssued->get($dateStr) ?? 0);
            $net = $inflow - $outflow;
            $runningBalance += $net;

            $forecast[] = [
                'date' => $dateStr,
                'inflow' => $inflow,
                'outflow' => $outflow,
                'net' => $net,
                'running_balance' => $runningBalance,
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'forecast_period' => [
                'start_date' => now()->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $days,
            ],
            'starting_balance' => $cashboxes->sum('balance') + $bankAccounts->sum('balance'),
            'forecast' => $forecast,
            'summary' => [
                'total_inflow' => array_sum(array_column($forecast, 'inflow')),
                'total_outflow' => array_sum(array_column($forecast, 'outflow')),
                'net_cashflow' => array_sum(array_column($forecast, 'net')),
                'ending_balance' => $runningBalance,
            ],
        ]);
    }

    /**
     * Party statement (cari ekstre)
     */
    public function partyStatement(Request $request, Party $party): JsonResponse
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Document::query()
            ->where('party_id', $party->id)
            ->posted();

        if ($startDate) {
            $query->where('document_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('document_date', '<=', $endDate);
        }

        $documents = $query->with(['category', 'allocations.payment'])
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $payments = Payment::query()
            ->where('party_id', $party->id)
            ->posted();

        if ($startDate) {
            $payments->where('payment_date', '>=', $startDate);
        }
        if ($endDate) {
            $payments->where('payment_date', '<=', $endDate);
        }

        $payments = $payments->with(['allocations.document'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        // Build statement lines
        $statement = [];
        $runningBalance = 0;

        // Combine documents and payments, sort by date
        $allItems = collect($documents->map(function ($doc) {
            return [
                'type' => 'document',
                'date' => $doc->document_date,
                'item' => $doc,
            ];
        }))->merge($payments->map(function ($payment) {
            return [
                'type' => 'payment',
                'date' => $payment->payment_date,
                'item' => $payment,
            ];
        }))->sortBy(function ($item) {
            return $item['date']->timestamp;
        });

        foreach ($allItems as $item) {
            if ($item['type'] === 'document') {
                $doc = $item['item'];
                $debit = $doc->direction === 'receivable' ? $doc->total_amount : 0;
                $credit = $doc->direction === 'payable' ? $doc->total_amount : 0;
                $runningBalance += ($debit - $credit);

                $statement[] = [
                    'date' => $doc->document_date->toDateString(),
                    'type' => 'document',
                    'document_number' => $doc->document_number,
                    'document_type' => $doc->type, // Schema uses 'type', map to 'document_type' for API
                    'description' => $doc->description,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $runningBalance,
                ];
            } else {
                $payment = $item['item'];
                // Schema uses 'in'/'out', not 'inflow'/'outflow'
                $debit = $payment->direction === 'in' ? $payment->amount : 0;
                $credit = $payment->direction === 'out' ? $payment->amount : 0;
                $runningBalance += ($debit - $credit);

                $statement[] = [
                    'date' => $payment->payment_date->toDateString(),
                    'type' => 'payment',
                    'payment_number' => $payment->payment_number,
                    'payment_type' => $payment->type, // Schema uses 'type', map to 'payment_type' for API
                    'description' => $payment->description,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $runningBalance,
                ];
            }
        }

        return response()->json([
            'party' => [
                'id' => $party->id,
                'name' => $party->name,
                'type' => $party->type,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'opening_balance' => 0, // Could calculate from before start_date
            'statement' => $statement,
            'closing_balance' => $runningBalance,
        ]);
    }

    /**
     * P&L report
     */
    public function profitLoss(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');
        $branchId = $request->get('branch_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            return response()->json([
                'message' => 'start_date and end_date are required'
            ], 422);
        }

        // Income documents (receivables that are income)
        $incomeDocs = Document::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->receivable()
            ->posted()
            ->whereIn('type', ['customer_invoice']) // Schema uses 'type', not 'document_type'
            ->whereBetween('document_date', [$startDate, $endDate])
            ->with(['category'])
            ->get();

        // Expense documents (payables that are expenses)
        $expenseDocs = Document::query()
            ->forCompany($companyId)
            ->forBranch($branchId)
            ->payable()
            ->posted()
            ->whereIn('type', ['supplier_invoice', 'expense_due']) // Schema uses 'type', not 'document_type'
            ->whereBetween('document_date', [$startDate, $endDate])
            ->with(['category'])
            ->get();

        // Group by category
        $incomeByCategory = [];
        foreach ($incomeDocs as $doc) {
            $catId = $doc->category_id ?? 'uncategorized';
            $catName = $doc->category ? $doc->category->name : 'Uncategorized';
            if (!isset($incomeByCategory[$catId])) {
                $incomeByCategory[$catId] = [
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'amount' => 0,
                    'count' => 0,
                ];
            }
            $incomeByCategory[$catId]['amount'] += $doc->total_amount;
            $incomeByCategory[$catId]['count']++;
        }

        $expenseByCategory = [];
        foreach ($expenseDocs as $doc) {
            $catId = $doc->category_id ?? 'uncategorized';
            $catName = $doc->category ? $doc->category->name : 'Uncategorized';
            if (!isset($expenseByCategory[$catId])) {
                $expenseByCategory[$catId] = [
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'amount' => 0,
                    'count' => 0,
                ];
            }
            $expenseByCategory[$catId]['amount'] += $doc->total_amount;
            $expenseByCategory[$catId]['count']++;
        }

        $totalIncome = array_sum(array_column($incomeByCategory, 'amount'));
        $totalExpense = array_sum(array_column($expenseByCategory, 'amount'));
        $netProfit = $totalIncome - $totalExpense;

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'income' => [
                'by_category' => array_values($incomeByCategory),
                'total' => $totalIncome,
            ],
            'expenses' => [
                'by_category' => array_values($expenseByCategory),
                'total' => $totalExpense,
            ],
            'net_profit' => $netProfit,
            'profit_margin' => $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0,
        ]);
    }
}
