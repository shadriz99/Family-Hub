<?php

declare(strict_types=1);

return [
    'air_conditioner' => [
        [
            'name' => 'Cuci AC',
            'trigger' => 'time',
            'interval_value' => 3,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Cuci filter, blower indoor, dan kondensor outdoor',
        ],
        [
            'name' => 'Cek freon & servis besar',
            'trigger' => 'time',
            'interval_value' => 12,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Cek tekanan freon, arus kompresor, dan bersihkan total',
        ],
    ],
    'motorcycle' => [
        [
            'name' => 'Ganti oli mesin',
            'trigger' => 'time_or_meter',
            'interval_value' => 2,
            'interval_unit' => 'month',
            'meter_interval' => 2000,
            'instructions' => 'Ganti oli mesin sesuai viskositas rekomendasi pabrikan',
        ],
        [
            'name' => 'Servis berkala (CVT/injeksi)',
            'trigger' => 'time_or_meter',
            'interval_value' => 4,
            'interval_unit' => 'month',
            'meter_interval' => 4000,
            'instructions' => 'Pembersihan ruang CVT, injektor, cek busi, dan filter udara',
        ],
        [
            'name' => 'Ganti ban',
            'trigger' => 'meter',
            'interval_value' => null,
            'interval_unit' => null,
            'meter_interval' => 20000,
            'instructions' => 'Periksa ketebalan alur ban depan & belakang',
        ],
    ],
    'car' => [
        [
            'name' => 'Ganti oli mesin + filter',
            'trigger' => 'time_or_meter',
            'interval_value' => 6,
            'interval_unit' => 'month',
            'meter_interval' => 10000,
            'instructions' => 'Ganti oli mesin dan filter oli baru',
        ],
        [
            'name' => 'Servis berkala',
            'trigger' => 'time_or_meter',
            'interval_value' => 6,
            'interval_unit' => 'month',
            'meter_interval' => 10000,
            'instructions' => 'Tune up, rotasi ban, cek rem, dan suspensi',
        ],
        [
            'name' => 'Ganti aki',
            'trigger' => 'time',
            'interval_value' => 24,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Cek tegangan aki atau ganti jika voltase melemah',
        ],
    ],
    'laundry' => [
        [
            'name' => 'Bersihkan tabung',
            'trigger' => 'time',
            'interval_value' => 1,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Gunakan mode tub clean dengan serbuk pembersih mesin cuci',
        ],
    ],
    'kitchen' => [
        [
            'name' => 'Bersihkan kulkas & cek karet pintu',
            'trigger' => 'time',
            'interval_value' => 3,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Bersihkan bunga es, evaporator, dan sekat karet pintu kulkas',
        ],
        [
            'name' => 'Cek selang & regulator',
            'trigger' => 'time',
            'interval_value' => 6,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Periksa kebocoran sambungan regulator tabung gas dengan busa sabun',
        ],
    ],
    'plumbing' => [
        [
            'name' => 'Kuras toren/tandon',
            'trigger' => 'time',
            'interval_value' => 6,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Kuras endapan lumpur dasar toren dan cuci dinding tandon air',
        ],
        [
            'name' => 'Ganti filter air',
            'trigger' => 'time',
            'interval_value' => 3,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Ganti cartridge filter spun / CTO / sedimentasi',
        ],
    ],
    'electrical' => [
        [
            'name' => 'Cek MCB & instalasi',
            'trigger' => 'time',
            'interval_value' => 12,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Periksa kekencangan baut terminal kabel MCB dan kotak sekring',
        ],
    ],
    'building' => [
        [
            'name' => 'Cek atap & talang (jelang musim hujan)',
            'trigger' => 'time',
            'interval_value' => 12,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Bersihkan sampah daun di talang dan cek genteng geser / retak',
        ],
        [
            'name' => 'Kuras septic tank',
            'trigger' => 'time',
            'interval_value' => 36,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Panggil jasa sedot WC berkala',
        ],
        [
            'name' => 'Fogging/anti rayap',
            'trigger' => 'time',
            'interval_value' => 12,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Pemeriksaan kusen kayu dan perlakuan obat anti-rayap',
        ],
    ],
    'garden' => [
        [
            'name' => 'Pangkas pohon',
            'trigger' => 'time',
            'interval_value' => 6,
            'interval_unit' => 'month',
            'meter_interval' => null,
            'instructions' => 'Pangkas dahan pohon yang dekat kabel PLN atau menimpa atap',
        ],
    ],
];
