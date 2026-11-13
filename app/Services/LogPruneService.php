<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Throwable;

class LogPruneService
{
    public const RETENTION_KEY = 'log_retention_days';

    public const DEFAULT_DAYS = 30;

    public const OPTIONS = [0, 3, 7, 30, 365];

    public const MAX_FILE_BYTES = 20 * 1024 * 1024;

    /**
     * @param  string|null  $directory  Override the log directory (defaults to storage/logs). Mainly for testing.
     */
    public function __construct(private readonly ?string $directory = null) {}

    public function resolveDirectory(): string
    {
        return $this->directory ?? storage_path('logs');
    }

    public function retentionDays(): int
    {
        return (int) Setting::get(self::RETENTION_KEY, (string) self::DEFAULT_DAYS);
    }

    public function setRetention(int $days): void
    {
        Setting::set(self::RETENTION_KEY, (string) $days);
    }

    /**
     * Delete log entries older than $days from every *.log file in storage/logs.
     * Lines are grouped into entries (a timestamped line + its continuation lines),
     * so multi-line stack traces are removed together with their parent entry.
     *
     * @return array{results: array<int, array{file:string, kept:int, removed:int, skipped:bool, reason?:string}>, deleted_total:int}
     */
    public function prune(?int $days = null): array
    {
        $days ??= $this->retentionDays();
        $cutoff = $days > 0 ? now()->subDays($days) : null;

        $results = [];
        $deletedTotal = 0;

        foreach (glob($this->resolveDirectory().'/*.log') ?: [] as $path) {
            $result = ['file' => basename($path), 'kept' => 0, 'removed' => 0, 'skipped' => false];

            if ($cutoff === null) {
                $result['skipped'] = true;
                $result['reason'] = 'nonaktif';
                $results[] = $result;

                continue;
            }

            if (($size = filesize($path)) > self::MAX_FILE_BYTES) {
                $result['skipped'] = true;
                $result['reason'] = 'terlalu besar (>20 MB)';
                $results[] = $result;

                continue;
            }

            [$kept, $removed] = $this->pruneFile($path, $cutoff);

            $result['kept'] = $kept;
            $result['removed'] = $removed;
            $deletedTotal += $removed;
            $results[] = $result;
        }

        return ['results' => $results, 'deleted_total' => $deletedTotal];
    }

    /**
     * @return array{0: int, 1: int} [entries kept, entries removed]
     */
    protected function pruneFile(string $path, Carbon $cutoff): array
    {
        $entries = $this->parseEntries($path);
        $keepLines = [];
        $kept = 0;
        $removed = 0;

        foreach ($entries as $entry) {
            if ($entry['ts'] !== null && $entry['ts']->lessThan($cutoff)) {
                $removed++;

                continue;
            }

            $kept++;

            foreach ($entry['lines'] as $line) {
                $keepLines[] = $line;
            }
        }

        $tmp = $path.'.tmp-'.uniqid('', true);

        file_put_contents($tmp, $keepLines === [] ? '' : implode(PHP_EOL, $keepLines).PHP_EOL);
        rename($tmp, $path);

        return [$kept, $removed];
    }

    /**
     * @return array<int, array{ts: ?Carbon, lines: array<int, string>}>
     */
    protected function parseEntries(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        return $this->parseEntryLines($lines);
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array{ts: ?Carbon, lines: array<int, string>}>
     */
    protected function parseEntryLines(array $lines): array
    {
        $entries = [];
        $current = ['ts' => null, 'lines' => []];

        foreach ($lines as $line) {
            $ts = $this->extractTimestamp($line);

            if ($ts !== null) {
                if ($current['lines'] !== []) {
                    $entries[] = $current;
                }

                $current = ['ts' => $ts, 'lines' => [$line]];

                continue;
            }

            $current['lines'][] = $line;
        }

        if ($current['lines'] !== []) {
            $entries[] = $current;
        }

        return $entries;
    }

    protected function extractTimestamp(string $line): ?Carbon
    {
        if (! preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $m[1]);
        } catch (Throwable) {
            return null;
        }
    }
}
