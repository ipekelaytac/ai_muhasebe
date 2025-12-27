<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayrollDeductionType;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollDeductionTypeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = PayrollDeductionType::with('company')->latest();
        
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }
        
        $deductionTypes = $query->paginate(20);
        return view('admin.payroll.deduction-types.index', compact('deductionTypes'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        return view('admin.payroll.deduction-types.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->company_id) {
            abort(403, 'Şirket bilgisi bulunamadı.');
        }
        
        $request->validate([
            'name' => 'required|string|max:190',
            'is_active' => 'boolean',
        ]);

        PayrollDeductionType::create([
            'company_id' => $user->company_id,
            'name' => $request->name,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.payroll.deduction-types.index')
            ->with('success', 'Kesinti tipi başarıyla oluşturuldu.');
    }

    public function edit(PayrollDeductionType $deductionType)
    {
        $user = Auth::user();
        if ($user->company_id && $deductionType->company_id != $user->company_id) {
            abort(403);
        }

        return view('admin.payroll.deduction-types.edit', compact('deductionType'));
    }

    public function update(Request $request, PayrollDeductionType $deductionType)
    {
        $user = Auth::user();
        if ($user->company_id && $deductionType->company_id != $user->company_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:190',
            'is_active' => 'boolean',
        ]);

        $deductionType->update([
            'name' => $request->name,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.payroll.deduction-types.index')
            ->with('success', 'Kesinti tipi başarıyla güncellendi.');
    }

    public function destroy(PayrollDeductionType $deductionType)
    {
        $user = Auth::user();
        if ($user->company_id && $deductionType->company_id != $user->company_id) {
            abort(403);
        }

        $deductionType->delete();
        return redirect()->route('admin.payroll.deduction-types.index')
            ->with('success', 'Kesinti tipi başarıyla silindi.');
    }
}

