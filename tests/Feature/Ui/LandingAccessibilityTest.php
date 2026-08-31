<?php

namespace Tests\Feature\Ui;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_exposes_core_accessibility_landmarks(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<html lang="id">', false)
            ->assertSee('href="#main-content"', false)
            ->assertSee('<main id="main-content"', false)
            ->assertSee('aria-label="Navigasi utama"', false);
    }

    public function test_landing_page_uses_consistent_formal_copy(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('magangmu')
            ->assertDontSee('kamu bisa')
            ->assertSee('Buat Pengajuan');
    }
}
