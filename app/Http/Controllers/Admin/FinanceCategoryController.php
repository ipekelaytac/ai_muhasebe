<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceCategoryController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = FinanceCategory::with('company')->latest();
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        
        $categories = $query->paginate(20);
        return view('admin.finance.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.finance.categories.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'type' => 'required|in:income,expense',
            'name' => 'required|string|max:190',
            'parent_id' => 'nullable|exists:finance_categories,id',
            'is_active' => 'boolean',
        ]);

        $companyId = $user->company_id ?? $request->input('company_id');
        if (!$companyId) {
            return back()->withErrors(['company_id' => 'Şirket seçilmelidir.']);
        }

        FinanceCategory::create([
            'company_id' => $companyId,
            'type' => $request->type,
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.finance.categories.index')
            ->with('success', 'Kategori başarıyla oluşturuldu.');
    }

    public function edit(FinanceCategory $category)
    {
        $user = Auth::user();
        if ($user->company_id && $category->company_id != $user->company_id) {
            abort(403);
        }

        return view('admin.finance.categories.edit', compact('category'));
    }

    public function update(Request $request, FinanceCategory $category)
    {
        $user = Auth::user();
        if ($user->company_id && $category->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'type' => 'required|in:income,expense',
            'name' => 'required|string|max:190',
            'parent_id' => 'nullable|exists:finance_categories,id',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'type' => $request->type,
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.finance.categories.index')
            ->with('success', 'Kategori başarıyla güncellendi.');
    }

    public function destroy(FinanceCategory $category)
    {
        $user = Auth::user();
        if ($user->company_id && $category->company_id != $user->company_id) {
            abort(403);
        }

        $category->delete();
        return redirect()->route('admin.finance.categories.index')
            ->with('success', 'Kategori başarıyla silindi.');
    }
}

