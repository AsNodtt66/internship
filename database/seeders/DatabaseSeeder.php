<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Master data required by registration/workflow is safe to seed in
        // every environment. Demo identities are opt-in and never production.
        $this->call([
            RoleSeeder::class,
            BagianSeeder::class,
        ]);

        if (app()->environment(['local', 'testing']) && filter_var(env('SEED_DEMO_USERS', false), FILTER_VALIDATE_BOOL)) {
            $this->call(UserSeeder::class);
        }
    }
}
