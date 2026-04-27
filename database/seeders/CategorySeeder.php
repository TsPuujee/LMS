<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'Уран зохиол' => 'Роман, өгүүллэг, яруу найраг, жүжиг',
        'Түүх' => 'Монголын болон дэлхийн түүх, намтар',
        'Шинжлэх ухаан' => 'Байгалийн ухаан, математик, физик, биологи',
        'Мэдээллийн технологи' => 'Програмчлал, компьютер, сүлжээ',
        'Эдийн засаг, бизнес' => 'Санхүү, менежмент, маркетинг',
        'Сэтгэл зүй, өөрийгөө хөгжүүлэх' => 'Сэтгэл зүй, зуршил, амжилт',
        'Хүүхдийн ном' => 'Үлгэр, зурагт ном, хүүхдийн уран зохиол',
        'Сурах бичиг' => 'Ерөнхий боловсролын болон их сургуулийн сурах бичиг',
        'Лавлах ном' => 'Толь бичиг, нэвтэрхий толь, атлас',
        'Урлаг, соёл' => 'Уран зураг, хөгжим, кино, ёс заншил',
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $name => $description) {
            Category::query()->firstOrCreate(['name' => $name], ['description' => $description]);
        }
    }
}
