<?php

declare(strict_types=1);

return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'confirmed' => ':attribute dan konfirmasinya tidak cocok.',
    'min' => [
        'string' => ':attribute minimal :min karakter.',
    ],
    'max' => [
        'string' => ':attribute maksimal :max karakter.',
    ],
    'attributes' => [
        'email' => 'Alamat email',
        'name' => 'Nama',
        'password' => 'Kata sandi',
        'password_confirmation' => 'Konfirmasi kata sandi',
        'token' => 'Token',
    ],
];
