<?php

return [
    'accepted' => ':Attribute harus diterima.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'max' => [
        'numeric' => ':Attribute tidak boleh lebih dari :max.',
        'file' => ':Attribute tidak boleh lebih dari :max kilobita.',
        'string' => ':Attribute tidak boleh lebih dari :max karakter.',
        'array' => ':Attribute tidak boleh lebih dari :max anggota.',
    ],
    'min' => [
        'numeric' => ':Attribute minimal bernilai :min.',
        'file' => ':Attribute minimal bernilai :min kilobita.',
        'string' => ':Attribute minimal berisi :min karakter.',
        'array' => ':Attribute minimal harus memiliki :min anggota.',
    ],
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus mengandung setidaknya satu huruf.',
        'mixed' => ':Attribute harus mengandung setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus mengandung setidaknya satu angka.',
        'symbols' => ':Attribute harus mengandung setidaknya satu simbol.',
        'uncompromised' => ':Attribute yang diberikan telah muncul dalam kebocoran data. Silakan pilih :attribute yang berbeda.',
    ],
    'required' => ':Attribute wajib diisi.',
    'same' => ':Attribute dan :other harus cocok.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah digunakan.',
    'attributes' => [
        'name' => 'nama',
        'email' => 'alamat email',
        'password' => 'kata sandi',
        'current_password' => 'kata sandi saat ini',
        'password_confirmation' => 'konfirmasi kata sandi',
        'phone' => 'nomor telepon',
    ],
];
