<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Authorization handled by middleware
    }

    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'document_type' => 'required|in:supplier_invoice,customer_invoice,expense_due,payroll_due,overtime_due,meal_due,cheque_receivable,cheque_payable,adjustment',
            'direction' => 'required|in:receivable,payable',
            'party_id' => 'required|exists:parties,id',
            'document_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:document_date',
            'total_amount' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:finance_categories,id',
            'description' => 'nullable|string|max:1000',
            'document_number' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
            'lines' => 'nullable|array',
            'lines.*.category_id' => 'nullable|exists:finance_categories,id',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.quantity' => 'nullable|numeric|min:0',
            'lines.*.unit_price' => 'nullable|numeric|min:0',
            'lines.*.amount' => 'required_without_all:lines.*.quantity,lines.*.unit_price|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_amount' => 'nullable|numeric|min:0',
        ];
    }
}
