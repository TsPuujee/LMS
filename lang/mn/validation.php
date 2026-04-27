<?php

/*
| Mongolian validation messages. Keys that are missing here fall back to the
| English defaults (APP_FALLBACK_LOCALE=en).
*/

return [
    'accepted' => ':attribute талбарыг зөвшөөрөх шаардлагатай.',
    'after' => ':attribute нь :date-с хойших огноо байх ёстой.',
    'after_or_equal' => ':attribute нь :date буюу түүнээс хойших огноо байх ёстой.',
    'array' => ':attribute нь жагсаалт байх ёстой.',
    'before' => ':attribute нь :date-с өмнөх огноо байх ёстой.',
    'before_or_equal' => ':attribute нь :date буюу түүнээс өмнөх огноо байх ёстой.',
    'between' => [
        'numeric' => ':attribute нь :min-с :max хооронд байх ёстой.',
        'string' => ':attribute нь :min-с :max тэмдэгт байх ёстой.',
    ],
    'boolean' => ':attribute нь тийм эсвэл үгүй байх ёстой.',
    'confirmed' => ':attribute баталгаажуулалт таарахгүй байна.',
    'current_password' => 'Нууц үг буруу байна.',
    'date' => ':attribute нь зөв огноо байх ёстой.',
    'date_format' => ':attribute нь :format хэлбэртэй байх ёстой.',
    'different' => ':attribute болон :other ялгаатай байх ёстой.',
    'digits' => ':attribute нь :digits оронтой тоо байх ёстой.',
    'digits_between' => ':attribute нь :min-с :max оронтой тоо байх ёстой.',
    'email' => ':attribute нь зөв и-мэйл хаяг байх ёстой.',
    'enum' => 'Сонгосон :attribute буруу байна.',
    'exists' => 'Сонгосон :attribute олдсонгүй.',
    'filled' => ':attribute талбарыг бөглөнө үү.',
    'in' => 'Сонгосон :attribute буруу байна.',
    'integer' => ':attribute нь бүхэл тоо байх ёстой.',
    'max' => [
        'array' => ':attribute нь :max-с олон элементгүй байх ёстой.',
        'file' => ':attribute нь :max килобайтаас ихгүй байх ёстой.',
        'numeric' => ':attribute нь :max-с ихгүй байх ёстой.',
        'string' => ':attribute нь :max тэмдэгтээс уртгүй байх ёстой.',
    ],
    'min' => [
        'array' => ':attribute нь хамгийн багадаа :min элементтэй байх ёстой.',
        'file' => ':attribute нь хамгийн багадаа :min килобайт байх ёстой.',
        'numeric' => ':attribute нь хамгийн багадаа :min байх ёстой.',
        'string' => ':attribute нь хамгийн багадаа :min тэмдэгт байх ёстой.',
    ],
    'numeric' => ':attribute нь тоо байх ёстой.',
    'password' => [
        'letters' => ':attribute дор хаяж нэг үсэг агуулах ёстой.',
        'mixed' => ':attribute дор хаяж нэг том, нэг жижиг үсэг агуулах ёстой.',
        'numbers' => ':attribute дор хаяж нэг тоо агуулах ёстой.',
        'symbols' => ':attribute дор хаяж нэг тусгай тэмдэгт агуулах ёстой.',
        'uncompromised' => 'Энэ :attribute нийтэд задарсан мэдээлэлд илэрсэн байна. Өөр нууц үг сонгоно уу.',
    ],
    'regex' => ':attribute буруу хэлбэртэй байна.',
    'required' => ':attribute талбарыг заавал бөглөнө.',
    'required_if' => ':other нь :value үед :attribute талбарыг заавал бөглөнө.',
    'required_with' => ':values байгаа үед :attribute талбарыг заавал бөглөнө.',
    'same' => ':attribute болон :other таарах ёстой.',
    'string' => ':attribute нь текст байх ёстой.',
    'unique' => 'Ийм :attribute аль хэдийн бүртгэгдсэн байна.',
    'url' => ':attribute нь зөв холбоос байх ёстой.',

    'attributes' => [
        'name' => 'Нэр',
        'email' => 'И-мэйл',
        'password' => 'Нууц үг',
        'current_password' => 'Одоогийн нууц үг',
        'password_confirmation' => 'Нууц үгийн баталгаажуулалт',
        'code' => 'Код',
    ],
];
