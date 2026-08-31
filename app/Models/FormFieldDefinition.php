<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormFieldDefinition extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opsi' => 'array',
        'wajib_diisi' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (FormFieldDefinition $field) {
            if (blank($field->key)) {
                $field->key = Str::slug($field->label, '_');
            }
        });
    }

    public function scopeUntuk($query, string $target)
    {
        return $query->where('target', $target)->orderBy('urutan');
    }
}
