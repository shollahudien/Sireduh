<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\LogPruneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogController extends Controller
{
    private const LOG_DIR = 'logs';

    /**
     * List all *.log files with size and modification time.
     */
    public function files(LogPruneService $pruner): JsonResponse
    {
        $dir = storage_path(self::LOG_DIR);
        $files = [];

        foreach (glob($dir.'/*.log') ?: [] as $path) {
            $files[] = [
                'name' => basename($path),
                'size' => filesize($path),
                'size_human' => $this->humanSize((int) filesize($path)),
                'modified_at' => date('c', (int) filemtime($path)),
            ];
        }

        usort($files, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));

        return response()->json([
            'data' => $files,
            'retention_days' => $pruner->retentionDays(),
        ]);
    }

    /**
     * Ubah retensi hapus otomatis untuk file *.log (3 hari / 1 minggu / 1 bulan / 1 tahun / nonaktif).
     */
    public function updateRetention(Request $request, LogPruneService $pruner): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'in:0,3,7,30,365'],
        ]);

        $days = (int) $validated['days'];

        $pruner->setRetention($days);

        $result = $days > 0 ? $pruner->prune($days) : ['deleted_total' => 0, 'results' => []];

        $perFile = $days > 0
            ? collect($result['results'])->map(fn ($row) => $row['file'].': '.($row['skipped'] ? 'skip ('.$row['reason'].')' : $row['removed'].' entri dihapus'))->implode(' · ')
            : '';

        return response()->json([
            'success' => true,
            'retention_days' => $days,
            'deleted' => $result['deleted_total'],
            'per_file' => $perFile,
            'message' => $days > 0
                ? 'Retensi file log: lebih lama dari '.$days.' hari dihapus ('.$result['deleted_total'].' entri dibersihkan sekarang).'
                : 'Hapus otomatis file log dimatikan.',
        ]);
    }

    /**
     * Last N lines of a *.log file (tail-ish), with optional level & search filters.
     */
    public function view(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'regex:/^[A-Za-z0-9_.\-]+\.log$/'],
            'lines' => ['nullable', 'integer', 'min:10', 'max:1000'],
            'level' => ['nullable', 'in:all,error,warning,info,debug'],
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        $path = $this->safePath($validated['file']);
        $linesWanted = (int) ($validated['lines'] ?? 300);
        $level = $validated['level'] ?? 'all';
        $search = $validated['search'] ?? null;

        if (! file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'File log tidak ditemukan.'], 404);
        }

        $size = filesize($path);
        $tailBytes = min((int) $size, 512 * 1024);
        $lines = $this->tailLines($path, $tailBytes, $linesWanted, $level, $search);

        $totalLines = $this->countLines($path);

        return response()->json([
            'success' => true,
            'file' => basename($path),
            'size' => $size,
            'size_human' => $this->humanSize((int) $size),
            'total_lines' => $totalLines,
            'shown' => count($lines),
            'lines' => $lines,
        ]);
    }

    /**
     * Truncate (empty) a *.log file. File stays in place.
     */
    public function clear(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'regex:/^[A-Za-z0-9_.\-]+\.log$/'],
        ]);

        $path = $this->safePath($validated['file']);

        if (! file_exists($path)) {
            return response()->json(['success' => false, 'message' => 'File log tidak ditemukan.'], 404);
        }

        file_put_contents($path, '');

        return response()->json(['success' => true, 'message' => 'Log '.$validated['file'].' berhasil dikosongkan.']);
    }

    /**
     * Download a *.log file as an attachment (admin only).
     */
    public function download(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'regex:/^[A-Za-z0-9_.\-]+\.log$/'],
        ]);

        $path = $this->safePath($validated['file']);

        abort_if(! file_exists($path), 404, 'File log tidak ditemukan.');

        return response()->download($path, 'sireteduh-'.$validated['file']);
    }

    /**
     * Absolute path for a whitelisted *.log file (no traversal possible).
     */
    public function safePath(string $file): string
    {
        $dir = realpath(storage_path(self::LOG_DIR));
        $path = realpath($dir.DIRECTORY_SEPARATOR.$file);

        abort_if($path === false || ! str_starts_with($path, $dir.DIRECTORY_SEPARATOR), 422, 'Nama file log tidak valid.');

        return $path;
    }

    /**
     * @return array<int, array{text:string, level:string}>
     */
    protected function tailLines(string $path, int $tailBytes, int $linesWanted, string $level, ?string $search): array
    {
        $size = filesize($path);

        if ($size === 0) {
            return [];
        }

        $offset = max(0, $size - $tailBytes);

        $fh = fopen($path, 'rb');
        fseek($fh, $offset);
        $buffer = fread($fh, $tailBytes);
        fclose($fh);

        // Jika mulai di tengah baris, buang potongan baris pertama
        if ($offset > 0 && ($pos = strpos($buffer, "\n")) !== false) {
            $buffer = substr($buffer, $pos + 1);
        }

        $raw = preg_split('/\r\n|\n|\r/', $buffer) ?: [];

        $result = [];
        foreach ($raw as $line) {
            if ($line === '') {
                continue;
            }

            $classified = $this->classifyLevel($line);

            if ($level !== 'all' && $classified !== $level) {
                continue;
            }

            if ($search !== null && $search !== '' && stripos($line, $search) === false) {
                continue;
            }

            $result[] = ['text' => $line, 'level' => $classified];
        }

        return array_slice($result, -$linesWanted);
    }

    protected function classifyLevel(string $line): string
    {
        if (preg_match('/\.(emergency|alert|critical|error)\b/i', $line)) {
            return 'error';
        }

        if (preg_match('/\.(warning)\b/i', $line)) {
            return 'warning';
        }

        if (preg_match('/\.(notice|info)\b/i', $line)) {
            return 'info';
        }

        if (preg_match('/\.(debug)\b/i', $line)) {
            return 'debug';
        }

        return 'unknown';
    }

    protected function countLines(string $path): int
    {
        $count = 0;
        $fh = fopen($path, 'rb');

        if ($fh === false) {
            return 0;
        }

        while (fgets($fh) !== false) {
            $count++;
        }

        fclose($fh);

        return $count;
    }

    protected function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        while ($bytes >= 1024 && $unit < 3) {
            $bytes /= 1024;
            $unit++;
        }

        return round($bytes, 1).' '.$units[$unit];
    }

    /**
     * Simpan urutan widget dashboard.
     */
    public function saveWidgetOrder(Request $request): JsonResponse
    {
        $widgetOrder = $request->input('widgetOrder', ['today-activity', 'summary-stats', 'pending-reservations', 'low-stock-alert']);
        $value = json_encode($widgetOrder);
        DB::table('settings')->insertOrUpdate(
            ['key' => 'dashboard_widgets', 'value' => $value]
        );

        return response()->json(['success' => true, 'widgetOrder' => $widgetOrder]);
    }
}
