<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['nama_role' => 'Peserta', 'slug' => 'peserta'],
            ['nama_role' => 'PIC PKL', 'slug' => 'pic'],
            ['nama_role' => 'Staff SDM', 'slug' => 'staff_sdm'],
            ['nama_role' => 'Kabag SDM', 'slug' => 'kabag_sdm'],
            ['nama_role' => 'GM', 'slug' => 'gm'],
            ['nama_role' => 'Kepala Bagian', 'slug' => 'kepala_bagian'],
            ['nama_role' => 'Pembimbing Lapangan', 'slug' => 'pembimbing_lapangan'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
