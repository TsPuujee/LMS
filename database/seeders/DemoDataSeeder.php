<?php

namespace Database\Seeders;

use App\Enums\CopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Database\Seeder;

/**
 * Demo catalogue for local development (make fresh). Never run in production.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * [title, author, category, year, copies]
     *
     * @var list<array{0: string, 1: string, 2: string, 3: int|null, 4: int}>
     */
    private const BOOKS = [
        ['Тунгалаг Тамир', 'Ч. Лодойдамба', 'Уран зохиол', 1962, 3],
        ['Учиртай гурван толгой', 'Д. Нацагдорж', 'Уран зохиол', 1934, 2],
        ['Бяцхан ханхүү', 'Антуан де Сент-Экзюпери', 'Хүүхдийн ном', 1943, 3],
        ['Гэмт хэрэг ба шийтгэл', 'Фёдор Достоевский', 'Уран зохиол', 1866, 2],
        ['Өвгөн загасчин ба тэнгис', 'Эрнест Хемингуэй', 'Уран зохиол', 1952, 2],
        ['Алхимич', 'Пауло Коэльо', 'Уран зохиол', 1988, 3],
        ['Зуун жилийн ганцаардал', 'Габриэль Гарсиа Маркес', 'Уран зохиол', 1967, 1],
        ['1984', 'Жорж Оруэлл', 'Уран зохиол', 1949, 2],
        ['Дайн ба энх', 'Лев Толстой', 'Уран зохиол', 1869, 1],
        ['Шерлок Холмсын адал явдал', 'Артур Конан Дойл', 'Уран зохиол', 1892, 2],
        ['Гарри Поттер ба шидийн чулуу', 'Ж. К. Роулинг', 'Хүүхдийн ном', 1997, 3],
        ['Монголын нууц товчоо', 'Зохиогч тодорхойгүй', 'Түүх', null, 3],
        ['Чингис хаан ба орчин үеийн ертөнц', 'Жек Уэзерфорд', 'Түүх', 2004, 2],
        ['Sapiens: Хүн төрөлхтний товч түүх', 'Юваль Ноа Харари', 'Түүх', 2011, 2],
        ['Баян аав, ядуу аав', 'Роберт Кийосаки', 'Эдийн засаг, бизнес', 1997, 2],
        ['Атомын дадал', 'Жеймс Клир', 'Сэтгэл зүй, өөрийгөө хөгжүүлэх', 2018, 3],
        ['Сэтгэхүйн хурдан ба удаан', 'Даниел Канеман', 'Сэтгэл зүй, өөрийгөө хөгжүүлэх', 2011, 1],
        ['Цэвэр код', 'Роберт С. Мартин', 'Мэдээллийн технологи', 2008, 2],
        ['Цагийн товч түүх', 'Стивен Хокинг', 'Шинжлэх ухаан', 1988, 1],
        ['Зүйлийн үүсэл', 'Чарльз Дарвин', 'Шинжлэх ухаан', 1859, 1],
    ];

    private const GIVEN_NAMES = [
        'Батболд', 'Сарангэрэл', 'Төмөрбаатар', 'Номин', 'Ганбаатар', 'Оюунчимэг',
        'Энхжаргал', 'Мөнхбат', 'Анударь', 'Билгүүн', 'Хулан', 'Тэмүүлэн',
        'Солонго', 'Эрдэнэбат', 'Номуун', 'Ариунаа', 'Дөлгөөн', 'Цэцэгмаа',
        'Бат-Эрдэнэ', 'Уянга',
    ];

    private const INITIALS = ['Б', 'Д', 'Г', 'Ц', 'Н', 'О', 'Э', 'Т', 'С', 'Ж', 'А', 'М'];

    public function run(LoanService $loans): void
    {
        $categories = Category::query()->pluck('id', 'name');

        foreach (self::BOOKS as [$title, $author, $category, $year, $copies]) {
            $book = Book::query()->firstOrCreate(
                ['title' => $title, 'author' => $author],
                [
                    'category_id' => $categories[$category] ?? $categories->first(),
                    'published_year' => $year,
                    'language' => 'Монгол',
                ],
            );

            if ($book->wasRecentlyCreated) {
                BookCopy::factory()->count($copies)->for($book)->create(['shelf' => mb_substr($category, 0, 1).'-'.random_int(1, 20)]);
            }
        }

        if (Member::query()->exists()) {
            return; // demo loans were already created
        }

        $members = collect(self::GIVEN_NAMES)->map(fn (string $name, int $i) => Member::factory()->create([
            'name' => self::INITIALS[$i % count(self::INITIALS)].'. '.$name,
            'email' => null,
        ]));
        $members->last()?->update(['is_active' => false]);

        $librarian = User::query()->first();
        $available = BookCopy::query()->available()->inRandomOrder()->get();

        // 15 open loans, a third of them overdue.
        foreach ($available->take(15)->values() as $i => $copy) {
            $member = $members[$i % ($members->count() - 1)];
            $loan = $loans->issue($copy, $member, by: $librarian);

            $borrowedDaysAgo = match (true) {
                $i % 3 === 0 => random_int(18, 30),  // overdue
                $i % 3 === 1 => random_int(10, 13),  // due in the next few days
                default => random_int(0, 5),
            };

            $loan->update([
                'borrowed_at' => now()->subDays($borrowedDaysAgo)->setTime(random_int(9, 17), random_int(0, 59)),
                'due_at' => today()->subDays($borrowedDaysAgo)->addDays((int) config('library.loan_days')),
            ]);
        }

        // Returned loans for the history pages.
        foreach ($available->slice(15)->take(12)->values() as $i => $copy) {
            $member = $members[($i + 5) % ($members->count() - 1)];
            $loan = $loans->issue($copy, $member, by: $librarian);
            $borrowedDaysAgo = random_int(20, 90);

            $loan->update([
                'borrowed_at' => now()->subDays($borrowedDaysAgo)->setTime(random_int(9, 17), random_int(0, 59)),
                'due_at' => today()->subDays($borrowedDaysAgo)->addDays((int) config('library.loan_days')),
            ]);

            $loans->return($loan, $librarian, $i === 0 ? CopyStatus::Damaged : CopyStatus::Available);

            $loan->update(['returned_at' => now()->subDays($borrowedDaysAgo - random_int(3, 16))]);
        }

        Loan::query()->whereNotNull('returned_at')->whereColumn('returned_at', '<', 'borrowed_at')
            ->update(['returned_at' => now()]);
    }
}
