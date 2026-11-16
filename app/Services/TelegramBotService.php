<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected function baseUrl(): ?string
    {
        $token = config('services.telegram.bot_token');

        if (blank($token)) {
            return null;
        }

        return "https://api.telegram.org/bot{$token}";
    }

    protected function http(int $timeout): PendingRequest
    {
        return Http::retry(3, 200)->timeout($timeout)->withOptions(['force_ip_resolve' => 'v4']);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.chat_id'));
    }

    /**
     * Send a plain text message to the seller chat.
     *
     * @return array<string, mixed>|null
     */
    public function sendMessage(string $text, ?array $replyMarkup = null): ?array
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return null;
        }

        $payload = [
            'chat_id' => config('services.telegram.chat_id'),
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $response = $this->http(15)->post("{$base}/sendMessage", $payload);

            if (! $response->successful()) {
                Log::warning('Telegram sendMessage failed', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            return $response->json('result');
        } catch (\Throwable $e) {
            Log::warning('Telegram sendMessage exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Send a photo (file bytes) with caption + inline buttons to the seller chat.
     *
     * @return array<string, mixed>|null
     */
    public function sendPhoto(string $photoBytes, string $filename, string $caption, ?array $replyMarkup = null): ?array
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return null;
        }

        try {
            $request = $this->http(30)->attach('photo', $photoBytes, $filename);

            $payload = [
                'chat_id' => config('services.telegram.chat_id'),
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup !== null) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = $request->post("{$base}/sendPhoto", $payload);

            if (! $response->successful()) {
                Log::warning('Telegram sendPhoto failed', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            return $response->json('result');
        } catch (\Throwable $e) {
            Log::warning('Telegram sendPhoto exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Answer a callback query (removes the loading spinner on the button).
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): void
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return;
        }

        try {
            $this->http(10)->post("{$base}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram answerCallbackQuery exception', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Edit the text/caption of an existing message.
     */
    public function editMessageText(int $chatId, int $messageId, string $text, ?array $replyMarkup = null): void
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $this->http(15)->post("{$base}/editMessageText", $payload);
        } catch (\Throwable $e) {
            Log::warning('Telegram editMessageText exception', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Edit the caption of an existing photo message.
     */
    public function editMessageCaption(int $chatId, int $messageId, string $caption, ?array $replyMarkup = null): void
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            $this->http(15)->post("{$base}/editMessageCaption", $payload);
        } catch (\Throwable $e) {
            Log::warning('Telegram editMessageCaption exception', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Set the webhook URL for this bot.
     */
    public function setWebhook(string $url, ?string $secret = null): bool
    {
        $base = $this->baseUrl();

        if ($base === null) {
            return false;
        }

        $payload = ['url' => $url];

        if (filled($secret)) {
            $payload['secret_token'] = $secret;
        }

        try {
            $response = $this->http(15)->post("{$base}/setWebhook", $payload);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Telegram setWebhook exception', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
