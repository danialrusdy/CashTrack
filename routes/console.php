<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('start:bot', function () {
    $token = config('services.telegram.token');
    $url = config('services.telegram.webhook_url');

    if (blank($token)) {
        $this->error('TELEGRAM_BOT_TOKEN belum diisi di .env.');
        return self::FAILURE;
    }

    if (blank($url)) {
        $this->error('TELEGRAM_WEBHOOK_URL belum diisi di .env.');
        return self::FAILURE;
    }

    $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
        'url' => $url,
    ]);

    if (! $response->ok() || ! $response->json('ok')) {
        $this->error('Gagal start bot.');
        $this->line($response->body());
        return self::FAILURE;
    }

    $this->info('Bot aktif.');
    $this->line("Webhook: {$url}");

    return self::SUCCESS;
})->purpose('Start Telegram bot by registering the webhook');

Artisan::command('stop:bot', function () {
    $token = config('services.telegram.token');

    if (blank($token)) {
        $this->error('TELEGRAM_BOT_TOKEN belum diisi di .env.');
        return self::FAILURE;
    }

    $response = Http::post("https://api.telegram.org/bot{$token}/deleteWebhook");

    if (! $response->ok() || ! $response->json('ok')) {
        $this->error('Gagal stop bot.');
        $this->line($response->body());
        return self::FAILURE;
    }

    $this->info('Bot nonaktif. Webhook sudah dihapus.');

    return self::SUCCESS;
})->purpose('Stop Telegram bot by deleting the webhook');

Artisan::command('status:bot', function () {
    $token = config('services.telegram.token');

    if (blank($token)) {
        $this->error('TELEGRAM_BOT_TOKEN belum diisi di .env.');
        return self::FAILURE;
    }

    $response = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");

    if (! $response->ok() || ! $response->json('ok')) {
        $this->error('Gagal cek status bot.');
        $this->line($response->body());
        return self::FAILURE;
    }

    $result = $response->json('result', []);
    $webhookUrl = $result['url'] ?? '';
    $pending = $result['pending_update_count'] ?? 0;
    $lastError = $result['last_error_message'] ?? null;

    $this->line('Status bot Telegram');
    $this->line('Mode: ' . ($webhookUrl ? 'aktif via webhook' : 'nonaktif / polling'));
    $this->line('Webhook: ' . ($webhookUrl ?: '-'));
    $this->line("Pending updates: {$pending}");

    if ($lastError) {
        $this->warn("Last error: {$lastError}");
    }

    return self::SUCCESS;
})->purpose('Show Telegram bot webhook status');
