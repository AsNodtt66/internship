<?php

namespace Tests\Unit\Services;

use App\Models\Pengajuan;
use App\Services\PengajuanTimelineService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PengajuanTimelineServiceTest extends TestCase
{
    public function test_unknown_timeline_step_has_a_safe_not_processed_state(): void
    {
        $method = new \ReflectionMethod(PengajuanTimelineService::class, 'resolveState');
        $method->setAccessible(true);

        $state = $method->invoke(
            new PengajuanTimelineService,
            'future_step',
            'draft',
            new Collection,
            new Pengajuan(['status' => 'draft']),
        );

        $this->assertSame('belum_diproses', $state);
    }
}
