<?php

namespace App\Models;

use App\Enums\RoleSlug;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'nip',
        'password',
        'role_id',
        'bagian_id',
        'no_hp',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function bagian(): BelongsTo
    {
        return $this->belongsTo(Bagian::class);
    }

    public function peserta(): HasOne
    {
        return $this->hasOne(Peserta::class);
    }

    /**
     * Daftar penugasan pembimbing di mana user ini berperan sebagai
     * Pembimbing Lapangan. Dipakai untuk menghitung beban bimbingan aktif
     * saat Kepala Bagian memilih pembimbing (lihat aksi Tetapkan Pembimbing).
     */
    public function penugasanPembimbings(): HasMany
    {
        return $this->hasMany(PenugasanPembimbing::class, 'pembimbing_id');
    }

    public function notifikasis(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }


    public function hasRole(RoleSlug|string $role): bool
    {
        $slug = $role instanceof RoleSlug ? $role->value : $role;

        return $this->role?->slug === $slug;
    }

    /**
     * @param  array<int, RoleSlug|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($panel->getId() === 'peserta') {
            return $this->hasRole(RoleSlug::PESERTA);
        }

        if ($panel->getId() === 'admin') {
            return $this->hasAnyRole(RoleSlug::adminPanelRoles());
        }

        return false;
    }
}