<?php

namespace Tests\Feature;

use App\Enums\BroadcastStatus;
use App\Enums\WhatsAppStatus;
use App\Models\Broadcast;
use App\Models\WhatsAppLog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WhatsAppWebhookControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.whatsapp.verify_token', 'verify-token');
        config()->set('services.whatsapp.app_secret', 'app-secret');
    }

    public function test_returns_challenge_for_valid_verification_request(): void
    {
        $response = $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=verify-token&hub.challenge=123456');

        $response->assertOk()->assertContent('123456');
    }

    public function test_returns_403_for_invalid_verification_token(): void
    {
        $response = $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=123456');

        $response->assertForbidden();
    }

    public function test_returns_403_for_invalid_payload_signature_without_changing_log(): void
    {
        $log = WhatsAppLog::factory()->create([
            'message_id' => 'wamid.invalid-signature',
            'status' => WhatsAppStatus::SENT,
        ]);
        $payload = $this->statusPayload($log->message_id, 'delivered', 1788091200);

        $response = $this->postWebhook($payload, 'wrong-secret');

        $response->assertForbidden();
        $this->assertSame(WhatsAppStatus::SENT, $log->refresh()->status);
        $this->assertNull($log->delivered_at);
    }

    public function test_updates_delivery_status_and_ignores_out_of_order_regression(): void
    {
        $broadcast = Broadcast::factory()->create([
            'total_recipient' => 1,
            'status' => BroadcastStatus::PROCESSING,
        ]);
        $log = WhatsAppLog::factory()->for($broadcast)->create([
            'message_id' => 'wamid.delivery',
            'status' => WhatsAppStatus::SENT,
        ]);

        $this->postWebhook($this->statusPayload($log->message_id, 'delivered', 1788091200))->assertOk();
        $this->postWebhook($this->statusPayload($log->message_id, 'read', 1788091260))->assertOk();
        $this->postWebhook($this->statusPayload($log->message_id, 'sent', 1788091000))->assertOk();

        $this->assertSame(WhatsAppStatus::READ, $log->refresh()->status);
        $this->assertSame('2026-08-30 04:00:00', $log->delivered_at?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-30 04:01:00', $log->read_at?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(BroadcastStatus::COMPLETED, $broadcast->refresh()->status);
        $this->assertSame(1, $broadcast->total_sent);
    }

    public function test_marks_message_failed_and_records_safe_error_detail(): void
    {
        $broadcast = Broadcast::factory()->create([
            'total_recipient' => 1,
            'status' => BroadcastStatus::PROCESSING,
        ]);
        $log = WhatsAppLog::factory()->for($broadcast)->create([
            'message_id' => 'wamid.failed',
            'status' => WhatsAppStatus::SENT,
        ]);
        $payload = $this->statusPayload($log->message_id, 'failed', 1788091200, [
            ['error_data' => ['details' => 'Nomor tujuan tidak dapat menerima pesan.']],
        ]);

        $this->postWebhook($payload)->assertOk();

        $this->assertSame(WhatsAppStatus::FAILED, $log->refresh()->status);
        $this->assertSame('Nomor tujuan tidak dapat menerima pesan.', $log->error_message);
        $this->assertSame('2026-08-30 04:00:00', $log->failed_at?->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(BroadcastStatus::FAILED, $broadcast->refresh()->status);
        $this->assertSame(1, $broadcast->total_failed);
    }

    /**
     * @param  list<array<string, mixed>>  $errors
     * @return array<string, mixed>
     */
    private function statusPayload(string $messageId, string $status, int $timestamp, array $errors = []): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'statuses' => [[
                            'id' => $messageId,
                            'status' => $status,
                            'timestamp' => (string) $timestamp,
                            'errors' => $errors,
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function postWebhook(array $payload, string $secret = 'app-secret'): TestResponse
    {
        $content = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $content, $secret);

        return $this->call(
            'POST',
            '/webhooks/whatsapp',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $signature],
            content: $content,
        );
    }
}
