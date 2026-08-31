<?php

namespace Tests\Feature\Models;

use App\Models\WhatsAppTemplate;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WhatsAppTemplateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_renders_preview_using_named_placeholders(): void
    {
        $template = WhatsAppTemplate::factory()->make([
            'content' => 'Halo {{nama}}, tagihan kamar {{kamar}} sebesar {{total}}.',
        ]);

        $preview = $template->renderPreview([
            'nama' => 'Budi',
            'kamar' => 'A1',
            'total' => 'Rp 1.000.000',
        ]);

        $this->assertSame('Halo Budi, tagihan kamar A1 sebesar Rp 1.000.000.', $preview);
    }
}
