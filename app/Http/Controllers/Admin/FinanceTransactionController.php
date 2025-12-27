<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\FinanceCategory;
use App\Models\TransactionAttachment;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FinanceTransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = FinanceTransaction::with(['company', 'branch', 'category', 'attachments'])->latest();
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }
        
        $transactions = $query->paginate(20);
        
        $categories = FinanceCategory::where('company_id', $user->company_id ?? 0)
            ->active()
            ->get();
        
        return view('admin.finance.transactions.index', compact('transactions', 'categories'));
    }

    public function create()
    {
        $user = Auth::user();
        $companies = Company::all();
        $branches = Branch::all();
        
        if ($user->company_id) {
            $companies = Company::where('id', $user->company_id)->get();
            $branches = Branch::where('company_id', $user->company_id)->get();
        }
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->get();
        }
        
        $categories = FinanceCategory::where('company_id', $user->company_id ?? 0)
            ->active()
            ->get();
        
        return view('admin.finance.transactions.create', compact('companies', 'branches', 'categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'type' => 'required|in:income,expense',
            'category_id' => 'required|exists:finance_categories,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
        ]);

        if ($user->company_id && $request->company_id != $user->company_id) {
            return back()->withErrors(['company_id' => 'Yetkisiz işlem.']);
        }

        $transaction = FinanceTransaction::create([
            'company_id' => $request->company_id,
            'branch_id' => $request->branch_id,
            'type' => $request->type,
            'category_id' => $request->category_id,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'amount' => $request->amount,
            'created_by' => $user->id,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('transaction-attachments', 'public');
                $transaction->attachments()->create([
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('admin.finance.transactions.index')
            ->with('success', 'İşlem başarıyla oluşturuldu.');
    }

    public function edit(FinanceTransaction $transaction)
    {
        $user = Auth::user();
        if ($user->company_id && $transaction->company_id != $user->company_id) {
            abort(403);
        }

        $companies = Company::all();
        $branches = Branch::all();
        
        if ($user->company_id) {
            $companies = Company::where('id', $user->company_id)->get();
            $branches = Branch::where('company_id', $user->company_id)->get();
        }
        
        $categories = FinanceCategory::where('company_id', $transaction->company_id)
            ->active()
            ->get();
        
        return view('admin.finance.transactions.edit', compact('transaction', 'companies', 'branches', 'categories'));
    }

    public function update(Request $request, FinanceTransaction $transaction)
    {
        $user = Auth::user();
        if ($user->company_id && $transaction->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'type' => 'required|in:income,expense',
            'category_id' => 'required|exists:finance_categories,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
        ]);

        $transaction->update($request->only([
            'company_id', 'branch_id', 'type', 'category_id',
            'transaction_date', 'description', 'amount'
        ]));

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('transaction-attachments', 'public');
                $transaction->attachments()->create([
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('admin.finance.transactions.index')
            ->with('success', 'İşlem başarıyla güncellendi.');
    }

    public function destroy(FinanceTransaction $transaction)
    {
        $user = Auth::user();
        if ($user->company_id && $transaction->company_id != $user->company_id) {
            abort(403);
        }

        foreach ($transaction->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $transaction->delete();
        return redirect()->route('admin.finance.transactions.index')
            ->with('success', 'İşlem başarıyla silindi.');
    }

    public function reports(Request $request)
    {
        $user = Auth::user();
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        
        $query = FinanceTransaction::whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month);
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        $income = (clone $query)->where('type', 'income')->sum('amount');
        $expense = (clone $query)->where('type', 'expense')->sum('amount');
        $net = $income - $expense;
        
        $transactions = $query->with(['category', 'branch'])->get();
        
        return view('admin.finance.reports', compact('income', 'expense', 'net', 'transactions', 'year', 'month'));
    }

    public function destroyAttachment(TransactionAttachment $attachment)
    {
        $user = Auth::user();
        $transaction = $attachment->transaction;
        
        if ($user->company_id && $transaction->company_id != $user->company_id) {
            abort(403);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Ek başarıyla silindi.');
    }
}

