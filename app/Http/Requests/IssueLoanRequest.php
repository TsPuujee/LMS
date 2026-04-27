<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueLoanRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')],
            'book_copy_id' => ['required', 'integer', Rule::exists('book_copies', 'id')],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'member_id' => 'Уншигч',
            'book_copy_id' => 'Номын хувь',
            'due_at' => 'Буцаах огноо',
            'note' => 'Тэмдэглэл',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'member_id.required' => 'Уншигчаа сонгоно уу.',
            'book_copy_id.required' => 'Зээлүүлэх номын хувийг сонгоно уу.',
            'due_at.after_or_equal' => 'Буцаах огноо өнөөдрөөс өмнө байж болохгүй.',
        ];
    }
}
