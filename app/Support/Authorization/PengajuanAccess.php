<?php

namespace App\Support\Authorization;

use App\Enums\RoleSlug;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for record visibility and query scoping of Pengajuan.
 *
 * UI navigation remains a usability concern only. Actual data visibility is
 * enforced here and by PengajuanPolicy so direct URLs and table queries stay
 * aligned.
 */
final class PengajuanAccess
{
    public static function canView(User $user, Pengajuan $pengajuan): bool
    {
        if ($user->hasAnyRole(RoleSlug::administrativeRoles())) {
            return true;
        }

        if ($user->hasRole(RoleSlug::KEPALA_BAGIAN)) {
            return $pengajuan->bagianTujuan?->kepala_bagian_id === $user->id;
        }

        if ($user->hasRole(RoleSlug::PEMBIMBING_LAPANGAN)) {
            return $pengajuan->penugasanPembimbing?->pembimbing_id === $user->id;
        }

        if ($user->hasRole(RoleSlug::PESERTA)) {
            return $pengajuan->peserta?->user_id === $user->id;
        }

        return false;
    }

    /**
     * Apply the same visibility rules to an Eloquent query.
     *
     * @param  Builder<Pengajuan>  $query
     * @return Builder<Pengajuan>
     */
    public static function scope(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(RoleSlug::administrativeRoles())) {
            return $query;
        }

        if ($user->hasRole(RoleSlug::KEPALA_BAGIAN)) {
            return $query->whereHas(
                'bagianTujuan',
                fn (Builder $bagian) => $bagian->where('kepala_bagian_id', $user->id),
            );
        }

        if ($user->hasRole(RoleSlug::PEMBIMBING_LAPANGAN)) {
            return $query->whereHas(
                'penugasanPembimbing',
                fn (Builder $penugasan) => $penugasan->where('pembimbing_id', $user->id),
            );
        }

        if ($user->hasRole(RoleSlug::PESERTA)) {
            return $query->whereHas(
                'peserta',
                fn (Builder $peserta) => $peserta->where('user_id', $user->id),
            );
        }

        return $query->whereRaw('1 = 0');
    }
}
