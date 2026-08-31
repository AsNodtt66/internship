<?php

namespace Database\Seeders;

use App\Models\Bagian;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Membuat 1 akun demo untuk setiap aktor pada flowchart AS-IS, agar seluruh
     * alur (verifikasi, disposisi, penempatan, pembimbingan, evaluasi) dapat
     * langsung dicoba end-to-end. Password demo dibaca dari SEED_DEFAULT_PASSWORD (minimal 12 karakter) dan hanya boleh dipakai pada local/testing.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo user seeder tidak boleh dijalankan di production.');
        }

        $password = (string) env('SEED_DEFAULT_PASSWORD', '');

        if (strlen($password) < 12) {
            throw new RuntimeException('SEED_DEFAULT_PASSWORD minimal 12 karakter untuk demo users lokal.');
        }

        $bagianSdm = Bagian::firstOrCreate(['nama_bagian' => 'Sumber Daya Manusia']);
        $bagianAkuntansi = Bagian::firstOrCreate(['nama_bagian' => 'Akuntansi dan Keuangan']);

        $role = fn (string $slug) => Role::where('slug', $slug)->first();

        // 1. General Manager - menyetujui disposisi tahap akhir.
        $gm = User::updateOrCreate(
            ['email' => 'gm@krebetbaru.co.id'],
            [
                'name' => 'General Manager',
                'password' => $password,
                'role_id' => $role('gm')->id,
                'is_active' => true,
            ]
        );

        // 2. Kepala Bagian SDM - menyetujui disposisi tahap kedua & sekaligus Kepala Bagian SDM.
        $kabagSdm = User::updateOrCreate(
            ['email' => 'kabagsdm@krebetbaru.co.id'],
            [
                'name' => 'Kepala Bagian SDM',
                'password' => $password,
                'role_id' => $role('kabag_sdm')->id,
                'bagian_id' => $bagianSdm->id,
                'is_active' => true,
            ]
        );
        $bagianSdm->update(['kepala_bagian_id' => $kabagSdm->id]);

        // 3. Staff SDM - disposisi tahap pertama & administrasi umum.
        User::updateOrCreate(
            ['email' => 'staffsdm@krebetbaru.co.id'],
            [
                'name' => 'Staff SDM',
                'password' => $password,
                'role_id' => $role('staff_sdm')->id,
                'bagian_id' => $bagianSdm->id,
                'no_hp' => '081234567890',
                'is_active' => true,
            ]
        );

        // 4. PIC PKL/Penelitian - verifikasi dokumen, rekap, penerbitan surat balasan.
        User::updateOrCreate(
            ['email' => 'pic@krebetbaru.co.id'],
            [
                'name' => 'PIC PKL/Penelitian',
                'password' => $password,
                'role_id' => $role('pic')->id,
                'bagian_id' => $bagianSdm->id,
                'is_active' => true,
            ]
        );

        // 5. Kepala Bagian tujuan (contoh: Akuntansi dan Keuangan) - menerima penempatan peserta.
        $kepalaBagianTujuan = User::updateOrCreate(
            ['email' => 'kabag.akuntansi@krebetbaru.co.id'],
            [
                'name' => 'Kepala Bagian Akuntansi dan Keuangan',
                'password' => $password,
                'role_id' => $role('kepala_bagian')->id,
                'bagian_id' => $bagianAkuntansi->id,
                'is_active' => true,
            ]
        );
        $bagianAkuntansi->update(['kepala_bagian_id' => $kepalaBagianTujuan->id]);

        // 6. Pembimbing Lapangan - membimbing & menilai peserta di lapangan.
        User::updateOrCreate(
            ['email' => 'pembimbing@krebetbaru.co.id'],
            [
                'name' => 'Pembimbing Lapangan',
                'password' => $password,
                'role_id' => $role('pembimbing_lapangan')->id,
                'bagian_id' => $bagianAkuntansi->id,
                'is_active' => true,
            ]
        );

        // 7. Peserta PKL contoh (tidak memiliki akses panel, data dientri oleh PIC).
        $userPeserta = User::updateOrCreate(
            ['email' => 'peserta@gmail.com'],
            [
                'name' => 'Budi Santoso',
                'password' => $password,
                'role_id' => $role('peserta')->id,
                'no_hp' => '089876543210',
                'is_active' => true,
            ]
        );

        Peserta::updateOrCreate(
            ['user_id' => $userPeserta->id],
            [
                'nim' => '220101001',
                'universitas' => 'Universitas Negeri',
                'jurusan' => 'Sistem Informasi',
                'alamat' => 'Jl. Merdeka No. 12',
            ]
        );
    }
}