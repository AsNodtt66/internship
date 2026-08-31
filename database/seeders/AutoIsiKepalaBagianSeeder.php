<?php

namespace Database\Seeders;

use App\Models\Bagian;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder cepat untuk mengisi Kepala Bagian pada SEMUA bagian yang belum
 * punya kepala_bagian_id. Untuk tiap bagian yang kosong, dibuatkan 1 user
 * baru dengan role 'kepala_bagian', langsung di-assign ke bagian_id yang
 * sesuai, dan bagian tersebut di-update kepala_bagian_id-nya.
 *
 * Bagian yang SUDAH punya Kepala Bagian tidak akan diubah/ditimpa.
 *
 * Password default semua user hasil seeder ini: "password"
 * (silakan ganti manual nanti kalau mau dipakai selain untuk testing).
 *
 * Cara pakai:
 *   1. Copy file ini ke: database/seeders/AutoIsiKepalaBagianSeeder.php
 *   2. Jalankan: php artisan db:seed --class=Database\\Seeders\\AutoIsiKepalaBagianSeeder
 */
class AutoIsiKepalaBagianSeeder extends Seeder
{
    public function run(): void
    {
        $roleKepalaBagian = Role::where('slug', 'kepala_bagian')->first();

        if (! $roleKepalaBagian) {
            $this->command->error("Role 'kepala_bagian' tidak ditemukan di tabel roles. Seeder dibatalkan.");

            return;
        }

        $bagianKosong = Bagian::whereNull('kepala_bagian_id')->get();

        if ($bagianKosong->isEmpty()) {
            $this->command->info('Semua bagian sudah punya Kepala Bagian. Tidak ada yang dibuat.');

            return;
        }

        foreach ($bagianKosong as $bagian) {
            $slugEmail = Str::slug($bagian->nama_bagian, '.');
            $email = "kabag.{$slugEmail}@krebetbaru.co.id";

            // Kalau email sudah kepakai (misal seeder dijalankan 2x), pakai user yang ada.
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Kepala Bagian {$bagian->nama_bagian}",
                    'password' => Hash::make('password'),
                    'role_id' => $roleKepalaBagian->id,
                    'bagian_id' => $bagian->id,
                    'is_active' => true,
                ]
            );

            $bagian->update(['kepala_bagian_id' => $user->id]);

            $this->command->info("[OK] {$bagian->nama_bagian} -> {$user->name} ({$user->email})");
        }

        $this->command->info('Selesai. Password default semua user baru: password');
    }
}
