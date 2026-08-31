<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private WhatsAppWebhookService $webhookService) {}

    public function __invoke(Request $request): Response|JsonResponse
    {
        if ($request->isMethod('get')) {
            return $this->verify($request);
        }

        if (! $this->hasValidSignature($request)) {
            return response()->json(['message' => 'Invalid webhook signature.'], Response::HTTP_FORBIDDEN);
        }

        $this->webhookService->handle($request->json()->all());

        return response()->json(['received' => true]);
    }

    private function verify(Request $request): Response|JsonResponse
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');
        $verifyToken = (string) config('services.whatsapp.verify_token');

        if ($mode !== 'subscribe' || $verifyToken === '' || ! hash_equals($verifyToken, (string) $token)) {
            return response()->json(['message' => 'Webhook verification failed.'], Response::HTTP_FORBIDDEN);
        }

        return response((string) $challenge, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }

    private function hasValidSignature(Request $request): bool
    {
        $appSecret = (string) config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');

        if ($appSecret === '' || ! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
