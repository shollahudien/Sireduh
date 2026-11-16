<?php

namespace App\Services;

use App\Models\ChatbotMessage;

class ChatbotMessageService
{
    public function log(string $sessionId, string $role, string $content, ?string $userName = null, array $meta = []): ?ChatbotMessage
    {
        $content = trim(mb_substr($content, 0, 2000));
        if ($sessionId === '' || $content === '' || ! in_array($role, ['user', 'bot'], true)) {
            return null;
        }

        return ChatbotMessage::create([
            'session_id' => $sessionId,
            'role' => $role,
            'content' => $content,
            'user_name' => $userName ? mb_substr($userName, 0, 80) : null,
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * @return array<int, array{role:string,text:string}>
     */
    public function recentHistory(string $sessionId, int $limit = 12): array
    {
        if ($sessionId === '') {
            return [];
        }

        return ChatbotMessage::query()
            ->where('session_id', $sessionId)
            ->latest('id')
            ->limit($limit)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn (ChatbotMessage $m) => ['role' => $m->role, 'text' => $m->content])
            ->values()
            ->all();
    }

    /**
     * Contoh Q&A relevan dengan pertanyaan saat ini dari data yang sudah dipelajari.
     */
    public function relevantExamples(string $question, int $pairs = 5): string
    {
        $tokens = $this->tokenize($question);
        $messages = ChatbotMessage::query()
            ->latest('id')
            ->limit(300)
            ->get(['id', 'session_id', 'role', 'content', 'created_at']);

        $scored = [];
        $bySession = $messages->groupBy('session_id');

        foreach ($bySession as $sessionMessages) {
            $ordered = $sessionMessages->sortBy('id')->values();
            $pendingUser = null;
            $pendingScore = 0;
            foreach ($ordered as $m) {
                if ($m->role === 'user') {
                    $pendingUser = (string) $m->content;
                    $pendingScore = $this->score($tokens, $pendingUser);
                } elseif ($m->role === 'bot' && $pendingUser !== null) {
                    if ($pendingScore > 0) {
                        $scored[] = [
                            'score' => $pendingScore,
                            'id' => $m->id,
                            'q' => $pendingUser,
                            'a' => (string) $m->content,
                        ];
                    }
                    $pendingUser = null;
                    $pendingScore = 0;
                }
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score'] ?: $b['id'] <=> $a['id']);

        $lines = [];
        $seen = [];
        foreach ($scored as $item) {
            $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', mb_substr($item['q'], 0, 80))));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $q = trim(preg_replace('/\s+/', ' ', $item['q']));
            $a = trim(preg_replace('/\s+/', ' ', $item['a']));
            $lines[] = 'Tamu: '.mb_substr($q, 0, 180)."\nBot: ".mb_substr($a, 0, 220);
            if (count($lines) >= $pairs) {
                break;
            }
        }

        if (count($lines) < $pairs) {
            $recent = $this->learningExamples(max(0, $pairs - count($lines)));
            if ($recent !== '') {
                $lines[] = $recent;
            }
        }

        return implode("\n---\n", array_filter($lines));
    }

    /**
     * Contoh Q&A terdahulu sebagai media pembelajaran (ringkas).
     */
    public function learningExamples(int $pairs = 4): string
    {
        if ($pairs <= 0) {
            return '';
        }

        $messages = ChatbotMessage::query()
            ->latest('id')
            ->limit(160)
            ->get(['role', 'content'])
            ->reverse()
            ->values();

        $lines = [];
        $pendingUser = null;
        foreach ($messages as $m) {
            if ($m->role === 'user') {
                $pendingUser = trim(preg_replace('/\s+/', ' ', (string) $m->content));
            } elseif ($m->role === 'bot' && $pendingUser !== null) {
                $bot = trim(preg_replace('/\s+/', ' ', (string) $m->content));
                $lines[] = 'Tamu: '.mb_substr($pendingUser, 0, 140)."\nBot: ".mb_substr($bot, 0, 180);
                $pendingUser = null;
                if (count($lines) >= $pairs) {
                    break;
                }
            }
        }

        return $lines ? implode("\n---\n", $lines) : '';
    }

    /**
     * @return list<string>
     */
    protected function tokenize(string $text): array
    {
        $text = mb_strtolower(strip_tags($text));
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = ['yang', 'dan', 'di', 'ke', 'dari', 'apa', 'ini', 'itu', 'saya', 'kamu', 'ada', 'untuk', 'dengan', 'berapa', 'bisa', 'mau', 'dong', 'ya', 'kah', 'kok', 'gimana', 'bagaimana', 'tolong'];
        $tokens = [];
        foreach ($parts as $p) {
            if (mb_strlen($p) < 3 || in_array($p, $stop, true)) {
                continue;
            }
            $tokens[$p] = true;
        }

        return array_keys($tokens);
    }

    /**
     * @param  list<string>  $tokens
     */
    protected function score(array $tokens, string $content): int
    {
        if ($tokens === []) {
            return 0;
        }
        $hay = mb_strtolower($content);
        $score = 0;
        foreach ($tokens as $t) {
            if (str_contains($hay, $t)) {
                $score++;
            }
        }

        return $score;
    }
}
