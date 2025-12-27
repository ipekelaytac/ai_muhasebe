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
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $request->validate([
            'name' => 'required|string|max:190',
            'address' => 'nullable|string|max:500',
        ]);

        Branch::create([
            'company_id' => $user->company_id,
            'name' => $request->name,
            'address' => $request->address,
        ]);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Şube başarıyla oluşturuldu.');
    }

    public function edit(Branch $branch)
    {
        $user = Auth::user();
        if ($user->company_id && $branch->company_id != $user->company_id) {
            abort(403);
        }

        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $user = Auth::user();
        if ($user->company_id && $branch->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:190',
            'address' => 'nullable|string|max:500',
        ]);

        $branch->update([
            'name' => $request->name,
            'address' => $request->address,
        ]);

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

