<?php

namespace Tests\Feature\Services;

use App\Exceptions\WhatsAppException;
use App\Services\WhatsAppService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
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

    public function test_sends_official_meta_template_payload_and_returns_message_id(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/123456/messages' => Http::response(['messages' => [['id' => 'wamid.123']]]),
        ]);

        $messageId = app(WhatsAppService::class)->sendTemplate(
            '0812-3456-7890',
            'tagihan_bulanan',
            'id',
            ['Budi', 'A1'],
        );

        $this->assertSame('wamid.123', $messageId);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://graph.facebook.com/v23.0/123456/messages'
                && $request->hasHeader('Authorization', 'Bearer secret-test-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '6281234567890'
                && $request['template']['name'] === 'tagihan_bulanan';
        });
    }

    public function test_connection_check_returns_phone_without_exposing_token(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/123456*' => Http::response(['display_phone_number' => '+62 812 0000']),
        ]);

        $result = app(WhatsAppService::class)->testConnection();

        $this->assertSame(['connected' => true, 'phone' => '+62 812 0000'], $result);
    }

    public function test_api_failure_returns_safe_message(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['error' => ['message' => 'Sensitive Meta detail']], 500),
        ]);

        $this->expectException(WhatsAppException::class);
        $this->expectExceptionMessage('Pesan gagal dikirim. Silakan coba kembali.');

        app(WhatsAppService::class)->sendTemplate('6281234567890', 'tagihan_bulanan', 'id', []);
    }

    public function test_rejects_invalid_phone_before_api_request(): void
    {
        Http::preventStrayRequests();

        try {
            app(WhatsAppService::class)->sendTemplate('123', 'tagihan_bulanan', 'id', []);
            $this->fail('Nomor tidak valid seharusnya ditolak.');
        } catch (WhatsAppException $exception) {
            $this->assertSame('Nomor WhatsApp penerima tidak valid.', $exception->getMessage());
        }

        Http::assertNothingSent();
    }
}
