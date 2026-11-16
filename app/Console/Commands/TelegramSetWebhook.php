<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {url? : Public HTTPS URL of the webhook endpoint}';

    protected $description = 'Register the Telegram bot webhook for order verification callbacks.';

    public function handle(TelegramBotService $telegram): int
    {
        $url = $this->argument('url');

        if (blank($url)) {
            $url = URL::to('/api/telegram/webhook');
        }

        $secret = config('services.telegram.webhook_secret');

        if ($telegram->setWebhook($url, $secret)) {
            $this->info("Webhook set to: {$url}");

            return self::SUCCESS;
        }

        $this->error('Gagal mendaftarkan webhook. Periksa TELEGRAM_BOT_TOKEN.');

        return self::FAILURE;
    }
}
