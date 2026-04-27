<?php

namespace App\Http\Requests;

use App\Enums\CopyStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnLoanRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Condition of the copy when it came back.
            'status' => ['nullable', Rule::enum(CopyStatus::class)->only(CopyStatus::manual())],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'Хувийн төлөв',
            'note' => 'Тэмдэглэл',
        ];
    }
}
