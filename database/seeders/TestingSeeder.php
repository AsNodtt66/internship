<?php

namespace Database\Seeders;

use App\Models\Bagian;
use App\Models\PembimbingLapangan;
use App\Models\Pengajuan;
use App\Models\Peserta;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TestingSeeder extends Seeder
{
    public const PASSWORD = 'TestingOnly!2026';

    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('TestingSeeder hanya boleh dijalankan pada APP_ENV=testing.');
        }

        $this->call(RoleSeeder::class);

        $roles = Role::query()->pluck('id', 'slug');

        $bagianA = Bagian::query()->updateOrCreate(
            ['nama_bagian' => 'E2E - Teknologi Informasi'],
            ['kepala_bagian_id' => null],
        );
        $bagianB = Bagian::query()->updateOrCreate(
            ['nama_bagian' => 'E2E - Operasional'],
            ['kepala_bagian_id' => null],
        );

        $users = [
            'pic' => $this->user('E2E PIC', 'pic@example.test', 'pic', $roles),
            'gm' => $this->user('E2E GM', 'gm@example.test', 'gm', $roles),
            'kabag' => $this->user('E2E Kabag SDM', 'kabag@example.test', 'kabag_sdm', $roles),
            'staff' => $this->user('E2E Staff SDM', 'staff@example.test', 'staff_sdm', $roles),
            'kepala' => $this->user('E2E Kepala Bagian', 'kepala@example.test', 'kepala_bagian', $roles, $bagianA->id),
            'mentor' => $this->user('E2E Pembimbing', '900001@nip.internal', 'pembimbing_lapangan', $roles, $bagianA->id, '900001'),
            'peserta_a' => $this->user('E2E Peserta A', 'peserta.a@example.test', 'peserta', $roles),
            'peserta_b' => $this->user('E2E Peserta B', 'peserta.b@example.test', 'peserta', $roles),
            'inactive' => $this->user('E2E Inactive', 'inactive@example.test', 'staff_sdm', $roles, null, null, false),
        ];

        $bagianA->update(['kepala_bagian_id' => $users['kepala']->id]);

        $mentorMaster = PembimbingLapangan::query()->updateOrCreate(
            ['user_id' => $users['mentor']->id],
            [
                'nama' => $users['mentor']->name,
                'jabatan' => 'Pembimbing E2E',
                'bagian_id' => $bagianA->id,
                'is_active' => true,
            ],
        );

        $pesertaA = Peserta::query()->updateOrCreate(
            ['user_id' => $users['peserta_a']->id],
            [
                'nim' => 'E2E-A-001',
                'universitas' => 'Universitas E2E',
                'jurusan' => 'Teknik Informatika',
                'no_hp' => '081111111111',
            ],
        );
        $pesertaB = Peserta::query()->updateOrCreate(
            ['user_id' => $users['peserta_b']->id],
            [
                'nim' => 'E2E-B-001',
                'universitas' => 'Universitas E2E',
                'jurusan' => 'Sistem Informasi',
                'no_hp' => '082222222222',
            ],
        );

        $privatePath = 'e2e/peserta-a-cv.pdf';
        Storage::disk(config('filesystems.private_documents_disk', 'documents'))
            ->put($privatePath, "%PDF-1.4\n% E2E fixture only\n");

        $pengajuanA = Pengajuan::query()->updateOrCreate(
            ['nomor_agenda' => 'E2E-A-001'],
            [
                'peserta_id' => $pesertaA->id,
                'bagian_tujuan_id' => $bagianA->id,
                'jenis_pengajuan' => 'PKL',
                'tanggal_mulai' => now()->addDays(7)->toDateString(),
                'tanggal_selesai' => now()->addMonths(2)->toDateString(),
                'status' => 'draft',
                'nama_lengkap' => $users['peserta_a']->name,
                'nama_institusi' => 'Universitas E2E',
                'program_studi' => 'Teknik Informatika',
                'file_cv' => $privatePath,
            ],
        );

        $pengajuanB = Pengajuan::query()->updateOrCreate(
            ['nomor_agenda' => 'E2E-B-001'],
            [
                'peserta_id' => $pesertaB->id,
                'bagian_tujuan_id' => $bagianB->id,
                'jenis_pengajuan' => 'PKL',
                'tanggal_mulai' => now()->addDays(10)->toDateString(),
                'tanggal_selesai' => now()->addMonths(2)->addDays(3)->toDateString(),
                'status' => 'draft',
                'nama_lengkap' => $users['peserta_b']->name,
                'nama_institusi' => 'Universitas E2E',
                'program_studi' => 'Sistem Informasi',
            ],
        );

        $fixture = [
            'password' => self::PASSWORD,
            'users' => collect($users)->mapWithKeys(fn (User $user, string $key) => [$key => [
                'id' => $user->id,
                'email' => $user->email,
                'nip' => $user->nip,
            ]])->all(),
            'bagians' => ['a' => $bagianA->id, 'b' => $bagianB->id],
            'pesertas' => ['a' => $pesertaA->id, 'b' => $pesertaB->id],
            'pengajuans' => ['a' => $pengajuanA->id, 'b' => $pengajuanB->id],
            'pembimbing_lapangan' => $mentorMaster->id,
        ];

        $path = storage_path('framework/testing/e2e-fixtures.json');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        file_put_contents($path, json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /** @param \Illuminate\Support\Collection<string,int> $roles */
    private function user(
        string $name,
        string $email,
        string $roleSlug,
        $roles,
        ?int $bagianId = null,
        ?string $nip = null,
        bool $active = true,
    ): User {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'nip' => $nip,
                'password' => self::PASSWORD,
                'role_id' => $roles[$roleSlug],
                'bagian_id' => $bagianId,
                'is_active' => $active,
                'email_verified_at' => now(),
            ],
        );
    }
}
