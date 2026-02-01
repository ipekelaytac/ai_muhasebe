<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'payment_type' => 'required|in:cash_in,cash_out,bank_in,bank_out,transfer,pos_in',
            'direction' => 'required|in:inflow,outflow',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'party_id' => 'nullable|exists:parties,id',
            'cashbox_id' => 'required_if:payment_type,cash_in,cash_out|nullable|exists:cashboxes,id',
            'bank_account_id' => 'required_if:payment_type,bank_in,bank_out,pos_in|nullable|exists:bank_accounts,id',
            // Schema has to_* columns but NOT from_* columns - source is cashbox_id/bank_account_id
            'to_cashbox_id' => 'required_if:payment_type,transfer|nullable|exists:cashboxes,id',
            'to_bank_account_id' => 'required_if:payment_type,transfer|nullable|exists:bank_accounts,id',
            'description' => 'nullable|string|max:1000',
            'payment_number' => 'nullable|string|max:100',
            // Schema does NOT have metadata column - validation allows but won't persist
        ];
    }

    public function messages()
    {
        return [
            'cashbox_id.required_if' => 'Cashbox is required for cash payments',
            'bank_account_id.required_if' => 'Bank account is required for bank/POS payments',
        ];
    }
}
