<?php
// app/Http/Requests/StoreTransactionRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Transaction::class);
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0|max:999999999.99',
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'transaction_date' => 'required|date|before_or_equal:today',
            'category_id' => 'required|exists:categories,id',
            'report_id' => 'nullable|exists:reports,id',
            'metadata' => 'nullable|array',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Amount must be greater than or equal to 0.',
            'amount.max' => 'Amount cannot exceed 999,999,999.99.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
            'receipt.max' => 'Receipt file size cannot exceed 5MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Clean amount input (remove commas, currency symbols)
        if ($this->has('amount')) {
            $amount = preg_replace('/[^\d.]/', '', $this->amount);
            $this->merge(['amount' => $amount]);
        }
    }
}