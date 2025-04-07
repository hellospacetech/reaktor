<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Kullanıcının kendi banka hesaplarını yönetme yetkisi var
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'bank_id' => ['required', 'exists:banks,id'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'iban' => ['required', 'string', 'max:50'],
            'branch_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_id.required' => 'Banka seçimi zorunludur.',
            'bank_id.exists' => 'Seçilen banka geçerli değil.',
            'iban.required' => 'IBAN numarası zorunludur.',
            'iban.max' => 'IBAN numarası en fazla 50 karakter olabilir.',
            'account_holder_name.max' => 'Hesap sahibi adı soyadı en fazla 255 karakter olabilir.',
        ];
    }
}
