<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Branch::with('company')->latest();
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        
        $branches = $query->paginate(20);
        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        $user = Auth::user();
        $companies = Company::all();
        
        if ($user->company_id) {
            $companies = Company::where('id', $user->company_id)->get();
        }
        
        return view('admin.branches.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:190',
            'address' => 'nullable|string|max:500',
        ]);

        if ($user->company_id && $request->company_id != $user->company_id) {
            return back()->withErrors(['company_id' => 'Yetkisiz işlem.']);
        }

        Branch::create($request->only(['company_id', 'name', 'address']));

        return redirect()->route('admin.branches.index')
            ->with('success', 'Şube başarıyla oluşturuldu.');
    }

    public function edit(Branch $branch)
    {
        $user = Auth::user();
        if ($user->company_id && $branch->company_id != $user->company_id) {
            abort(403);
        }

        $companies = Company::all();
        if ($user->company_id) {
            $companies = Company::where('id', $user->company_id)->get();
        }

        return view('admin.branches.edit', compact('branch', 'companies'));
    }

    public function update(Request $request, Branch $branch)
    {
        $user = Auth::user();
        if ($user->company_id && $branch->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:190',
            'address' => 'nullable|string|max:500',
        ]);

        if ($user->company_id && $request->company_id != $user->company_id) {
            return back()->withErrors(['company_id' => 'Yetkisiz işlem.']);
        }

        $branch->update($request->only(['company_id', 'name', 'address']));

        return redirect()->route('admin.branches.index')
            ->with('success', 'Şube başarıyla güncellendi.');
    }

    public function destroy(Branch $branch)
    {
        $user = Auth::user();
        if ($user->company_id && $branch->company_id != $user->company_id) {
            abort(403);
        }

        $branch->delete();
        return redirect()->route('admin.branches.index')
            ->with('success', 'Şube başarıyla silindi.');
    }
}

