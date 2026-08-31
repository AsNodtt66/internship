<?php

namespace App\Observers;

use App\Models\ApprovalWorkflow;
use App\Models\AuditLog;
use App\Models\Evaluasi;
use App\Models\Pengajuan;
use App\Models\Penilaian;
use App\Models\PenugasanPembimbing;
use App\Models\Perpanjangan;
use App\Models\SuratBalasan;
use App\Models\SuratKeterangan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DomainAuditObserver
{
    /** @var array<class-string<Model>, array<int, string>> */
    private const SAFE_FIELDS = [
        Pengajuan::class => [
            'status', 'bagian_tujuan_id', 'tanggal_mulai', 'tanggal_selesai',
            'diteruskan_ke_kabag_at', 'pengajuan_asal_id', 'pengingat_perpanjangan_terkirim_at',
        ],
        ApprovalWorkflow::class => [
            'urutan', 'penandatangan_id', 'status', 'diproses_at', 'diteruskan_oleh_id', 'diteruskan_at',
        ],
        PenugasanPembimbing::class => [
            'pembimbing_id', 'pembimbing_lapangan_id', 'status',
        ],
        Evaluasi::class => [
            'status', 'hasil', 'jadwal_evaluasi', 'nilai_akhir',
        ],
        Perpanjangan::class => [
            'pengajuan_baru_id', 'tanggal_mulai_baru', 'tanggal_selesai_baru', 'status',
        ],
        Penilaian::class => [
            'status', 'nilai', 'hasil',
        ],
        SuratBalasan::class => [
            'status', 'tanggal_surat',
        ],
        SuratKeterangan::class => [
            'status', 'jenis', 'tanggal_surat',
        ],
    ];

    public function created(Model $model): void
    {
        $this->record($model, 'created', null);
    }

    public function updated(Model $model): void
    {
        $safe = self::SAFE_FIELDS[$model::class] ?? [];
        $changes = [];

        foreach ($safe as $field) {
            if (! $model->wasChanged($field)) {
                continue;
            }

            $changes[$field] = [
                'from' => $this->normalize($model->getOriginal($field)),
                'to' => $this->normalize($model->getAttribute($field)),
            ];
        }

        if ($changes !== []) {
            $this->record($model, 'updated', $changes);
        }
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', null);
    }

    /** @param array<string, array{from: mixed, to: mixed}>|null $changes */
    private function record(Model $model, string $event, ?array $changes): void
    {
        // Older databases may boot the app while this migration has not run yet.
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::query()->create([
            'actor_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => (string) $model->getKey(),
            'changes' => $changes,
            'request_id' => app()->runningInConsole()
                ? null
                : request()->attributes->get('request_id'),
            'source' => app()->runningInConsole() ? 'console' : 'web',
        ]);
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }
}
