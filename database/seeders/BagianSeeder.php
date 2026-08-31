<?php

namespace Database\Seeders;

use App\Models\Bagian;
use Illuminate\Database\Seeder;

class BagianSeeder extends Seeder
{
    public function run(): void
    {
        $bagians = [
            'SDM & Umum',
            'Akuntansi dan Keuangan',
            'Tanaman & Kemitraan',
            'Quality Assurance',
            'Pabrikasi',
            'Instalasi',
            'Lainnya',
        ];

        foreach ($bagians as $nama) {
            Bagian::firstOrCreate(['nama_bagian' => $nama]);
        }
    }
}