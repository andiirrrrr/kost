<?php

namespace Tests\Feature\Jobs;

use App\Enums\BroadcastStatus;
use App\Enums\WhatsAppStatus;
use App\Exceptions\WhatsAppException;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Broadcast;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendWhatsAppMessageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp', [
            'enabled' => true,
            'api_url' => 'https://graph.facebook.com',
            'api_version' => 'v23.0',
            'phone_number_id' => '123456',
            'business_account_id' => '654321',
            'access_token' => 'secret-test-token',
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
    }

    public function test_marks_log_sent_and_completes_broadcast(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.sent']]])]);
        $template = WhatsAppTemplate::factory()->create(['template_name' => 'tagihan_bulanan']);
        $broadcast = Broadcast::factory()->for($template, 'template')->create([
            'template_name' => $template->template_name,
            'total_recipient' => 1,
            'status' => BroadcastStatus::QUEUED,
        ]);
        $log = WhatsAppLog::factory()->for($broadcast)->create([
            'template_name' => $template->template_name,
            'phone' => '6281234567890',
        ]);

        (new SendWhatsAppMessage($log->id))->handle(app(WhatsAppService::class));

        $this->assertSame(WhatsAppStatus::SENT, $log->refresh()->status);
        $this->assertSame('wamid.sent', $log->message_id);
        $this->assertSame(1, $log->attempts);
        $this->assertSame(BroadcastStatus::COMPLETED, $broadcast->refresh()->status);
        $this->assertSame(1, $broadcast->total_sent);
    }

    public function test_terminal_failure_marks_log_and_broadcast_failed(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://graph.facebook.com/*' => Http::response(['error' => ['message' => 'Internal detail']], 500)]);
        $template = WhatsAppTemplate::factory()->create(['template_name' => 'tagihan_bulanan']);
        $broadcast = Broadcast::factory()->for($template, 'template')->create([
            'template_name' => $template->template_name,
            'total_recipient' => 1,
            'status' => BroadcastStatus::QUEUED,
        ]);
        $log = WhatsAppLog::factory()->for($broadcast)->create([
            'template_name' => $template->template_name,
            'phone' => '6281234567890',
        ]);
        $job = new SendWhatsAppMessage($log->id);

        try {
            $job->handle(app(WhatsAppService::class));
            $this->fail('API failure seharusnya melempar exception untuk retry queue.');
        } catch (WhatsAppException $exception) {
            $this->assertSame('Pesan gagal dikirim. Silakan coba kembali.', $exception->getMessage());
        }
        $job->failed(new WhatsAppException('failed'));

        $this->assertSame(WhatsAppStatus::FAILED, $log->refresh()->status);
        $this->assertSame('Pesan gagal dikirim setelah beberapa percobaan.', $log->error_message);
        $this->assertSame(BroadcastStatus::FAILED, $broadcast->refresh()->status);
        $this->assertSame(1, $broadcast->total_failed);
    }
}
