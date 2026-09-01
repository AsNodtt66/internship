<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormulirPenilaian extends Model
{
    protected $fillable = ['evaluasi_id', 'aspek_penilaian', 'skor', 'catatan'];

    /** @return BelongsTo<Evaluasi, $this> */
    public function evaluasi(): BelongsTo
    {
        return $this->belongsTo(Evaluasi::class);
    }
}
