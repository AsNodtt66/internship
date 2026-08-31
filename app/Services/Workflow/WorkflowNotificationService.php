<?php

namespace App\Services\Workflow;

use App\Models\Notifikasi;
use App\Models\Pengajuan;
use App\Models\Role;
use App\Models\User;

/**
 * Single entry point for workflow notifications.
 *
 * Keeping notification persistence here prevents business services and UI
 * actions from duplicating role/user lookup logic.
 */
class WorkflowNotificationService
{
    /** @var array<string, Role|null> */
    private array $rolesBySlug = [];

    public function role(Pengajuan $pengajuan, string $roleSlug, string $judul, string $pesan): void
    {
        $role = $this->rolesBySlug[$roleSlug] ??= Role::where('slug', $roleSlug)->first();

        if (! $role) {
            return;
        }

        $role->users()
            ->where('is_active', true)
            ->each(fn (User $user) => $this->user($user, $pengajuan, $judul, $pesan));
    }

    public function participant(Pengajuan $pengajuan, string $judul, string $pesan): void
    {
        $user = $pengajuan->peserta?->user;

        if ($user) {
            $this->user($user, $pengajuan, $judul, $pesan);
        }
    }

    public function user(User $user, Pengajuan $pengajuan, string $judul, string $pesan): void
    {
        Notifikasi::create([
            'user_id' => $user->id,
            'pengajuan_id' => $pengajuan->id,
            'judul' => $judul,
            'pesan' => $pesan,
            'is_read' => false,
        ]);
    }
}
