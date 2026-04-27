<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('isbn'))) {
            // Accept "978-99973-0-123-4" and store "9789997301234".
            $isbn = strtoupper((string) preg_replace('/[^0-9Xx]/', '', $this->input('isbn')));
            $this->merge(['isbn' => $isbn === '' ? null : $isbn]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Book|null $book */
        $book = $this->route('book');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'regex:/^(\d{9}[\dX]|\d{13})$/', Rule::unique('books', 'isbn')->ignore($book)],
            'publisher' => ['nullable', 'string', 'max:255'],
            'published_year' => ['nullable', 'integer', 'min:1000', 'max:'.(now()->year + 1)],
            'language' => ['nullable', 'string', 'max:50'],
            'pages' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'description' => ['nullable', 'string', 'max:5000'],
            // Only used when creating: how many physical copies to register.
            'copies' => [Rule::excludeIf($book !== null), 'nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'Ангилал',
            'title' => 'Номын нэр',
            'author' => 'Зохиогч',
            'isbn' => 'ISBN',
            'publisher' => 'Хэвлэлийн газар',
            'published_year' => 'Хэвлэгдсэн он',
            'language' => 'Хэл',
            'pages' => 'Хуудасны тоо',
            'description' => 'Тайлбар',
            'copies' => 'Хувийн тоо',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'isbn.regex' => 'ISBN нь 10 эсвэл 13 оронтой байх ёстой.',
        ];
    }
}
