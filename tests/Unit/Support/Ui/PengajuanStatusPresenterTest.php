<?php

namespace Tests\Unit\Support\Ui;

use App\Support\Ui\PengajuanStatusPresenter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PengajuanStatusPresenterTest extends TestCase
{
    public static function statuses(): array
    {
        return [
            ['draft', 'Draft', 'gray'],
            ['diajukan', 'Menunggu Verifikasi PIC', 'warning'],
            ['dokumen_ditolak', 'Dokumen Perlu Revisi', 'danger'],
            ['proses_approval', 'Proses Persetujuan', 'warning'],
            ['berjalan', 'Sedang Berjalan', 'success'],
            ['selesai', 'Selesai', 'success'],
            ['perlu_perpanjangan', 'Perlu Tindak Lanjut Perpanjangan', 'warning'],
        ];
    }

    #[DataProvider('statuses')]
    public function test_it_presents_consistent_status_copy(string $status, string $label, string $color): void
    {
        self::assertSame($label, PengajuanStatusPresenter::label($status));
        self::assertSame($color, PengajuanStatusPresenter::color($status));
        self::assertNotSame('', PengajuanStatusPresenter::description($status));
    }

    public function test_unknown_status_has_readable_fallback(): void
    {
        self::assertSame('Status Baru', PengajuanStatusPresenter::label('status_baru'));
    }
}
