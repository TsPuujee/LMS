<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Member|null $member */
        $member = $this->route('member');

        return [
            'member_no' => ['nullable', 'string', 'max:50', Rule::unique('members', 'member_no')->ignore($member)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'member_no' => 'Үнэмлэхний дугаар',
            'name' => 'Овог нэр',
            'phone' => 'Утас',
            'email' => 'И-мэйл',
            'address' => 'Хаяг',
            'note' => 'Тэмдэглэл',
            'is_active' => 'Идэвхтэй эсэх',
        ];
    }
}
