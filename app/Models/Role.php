<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Role extends Model
{
    protected static function booted(): void
    {
        static::updating(function (Role $role): void {
            if ($role->isDirty('slug')) {
                throw new LogicException('Slug role sistem tidak boleh diubah.');
            }
        });

        static::deleting(function (Role $role): void {
            throw new LogicException('Role sistem tidak boleh dihapus.');
        });
    }

    protected $fillable = ['nama_role', 'slug'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}