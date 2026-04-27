<?php

namespace App\Http\Requests;

use App\Enums\CopyStatus;
use App\Models\BookCopy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BookCopyRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var BookCopy|null $copy */
        $copy = $this->route('copy');
        $creating = $copy === null;

        return [
            'inventory_no' => ['nullable', 'string', 'max:50', Rule::unique('book_copies', 'inventory_no')->ignore($copy)],
            'shelf' => ['nullable', 'string', 'max:50'],
            'acquired_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'quantity' => [Rule::excludeIf(! $creating), 'nullable', 'integer', 'min:1', 'max:50'],
            'status' => [Rule::excludeIf($creating), 'required', Rule::enum(CopyStatus::class)],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('inventory_no') && (int) $this->input('quantity', 1) > 1) {
                    $validator->errors()->add('quantity', 'Бүртгэлийн дугаар оруулсан үед нэг л хувь нэмнэ. Олон хувь нэмэх бол дугаарыг хоосон орхино уу.');
                }

                /** @var BookCopy|null $copy */
                $copy = $this->route('copy');
                $status = $this->enum('status', CopyStatus::class);

                if ($copy === null || $status === null) {
                    return;
                }

                if ($copy->status === CopyStatus::Borrowed && $status !== CopyStatus::Borrowed) {
                    $validator->errors()->add('status', 'Зээлсэн хувийн төлөвийг зөвхөн «Буцаах» үйлдлээр өөрчилнө.');
                } elseif ($copy->status !== CopyStatus::Borrowed && $status === CopyStatus::Borrowed) {
                    $validator->errors()->add('status', '«Зээлсэн» төлөвийг зөвхөн зээл олгох үед автоматаар тавина.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inventory_no' => 'Бүртгэлийн дугаар',
            'shelf' => 'Тавиур',
            'acquired_at' => 'Бүртгэсэн огноо',
            'note' => 'Тэмдэглэл',
            'quantity' => 'Тоо',
            'status' => 'Төлөв',
        ];
    }
}
