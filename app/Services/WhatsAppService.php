<?php

namespace App\Services;

use App\Exceptions\WhatsAppException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.whatsapp.enabled')
            && filled(config('services.whatsapp.phone_number_id'))
            && filled(config('services.whatsapp.access_token'));
    }

    /** @return array{connected: bool, phone: ?string} */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['connected' => false, 'phone' => null];
        }

        try {
            $response = $this->client()
                ->retry([200, 500], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429)))
                ->get($this->phoneNumberEndpoint(), ['fields' => 'display_phone_number'])
                ->throw();

            return [
                'connected' => true,
                'phone' => $response->json('display_phone_number'),
            ];
        } catch (Throwable $exception) {
            Log::warning('WhatsApp connection test failed', [
                'exception' => $exception::class,
                'status' => $exception instanceof RequestException ? $exception->response->status() : null,
            ]);

            return ['connected' => false, 'phone' => null];
        }
    }

    /** @param list<string> $parameters */
    public function sendTemplate(string $phone, string $templateName, string $language, array $parameters): string
    {
        if (! $this->isConfigured()) {
            throw new WhatsAppException('Konfigurasi WhatsApp belum lengkap atau belum diaktifkan.');
        }

        $phone = self::normalizePhone($phone);
        if (! self::isValidPhone($phone)) {
            throw new WhatsAppException('Nomor WhatsApp penerima tidak valid.');
        }

        $template = [
            'name' => $templateName,
            'language' => ['code' => $language],
        ];

        if ($parameters !== []) {
            $template['components'] = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $value): array => ['type' => 'text', 'text' => $value],
                    $parameters,
                ),
            ]];
        }

        try {
            $response = $this->client()
                ->post($this->messagesEndpoint(), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $phone,
                    'type' => 'template',
                    'template' => $template,
                ])
                ->throw();

            $messageId = $response->json('messages.0.id');
            if (! is_string($messageId) || $messageId === '') {
                throw new WhatsAppException('WhatsApp API tidak mengembalikan ID pesan.');
            }

            return $messageId;
        } catch (WhatsAppException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('WhatsApp message send failed', [
                'exception' => $exception::class,
                'status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'recipient_suffix' => substr($phone, -4),
                'template' => $templateName,
            ]);

            throw new WhatsAppException('Pesan gagal dikirim. Silakan coba kembali.', previous: $exception);
        }
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            return '62'.$phone;
        }

        return $phone;
    }

    public static function isValidPhone(string $phone): bool
    {
        return preg_match('/^628[0-9]{7,12}$/', $phone) === 1;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.whatsapp.api_url'), '/'))
            ->withToken((string) config('services.whatsapp.access_token'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('services.whatsapp.connect_timeout', 5))
            ->timeout((int) config('services.whatsapp.timeout', 10));
    }

    private function phoneNumberEndpoint(): string
    {
        return sprintf(
            '/%s/%s',
            trim((string) config('services.whatsapp.api_version'), '/'),
            config('services.whatsapp.phone_number_id'),
        );
    }

    private function messagesEndpoint(): string
    {
        return $this->phoneNumberEndpoint().'/messages';
    }
}
