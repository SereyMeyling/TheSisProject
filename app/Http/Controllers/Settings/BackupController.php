<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting\BackupLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackupController extends Controller
{
    /** Tables never included in a dump (the backups themselves). */
    protected array $excludedTables = ['database_backups'];

    public function index()
    {
        return view('form.settings.backup.backupPage');
    }

    public function list()
    {
        $backups = DB::table('database_backups')
            ->select('filename', 'size', 'created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn($b) => [
                'filename' => $b->filename,
                'size' => $this->formatBytes($b->size),
                'created_at' => Carbon::parse($b->created_at)->format('d-m-Y h:i A'),
            ])
            ->values();

        return response()->json(['data' => $backups]);
    }

    public function store(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $base = 'backup_' . now()->format('Y-m-d_His');
        $sqlPath = tempnam(sys_get_temp_dir(), 'bksql');
        $zipPath = tempnam(sys_get_temp_dir(), 'bkzip');

        try {
            $this->dumpDatabase($sqlPath);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create zip archive.');
            }
            $zip->addFile($sqlPath, $base . '.sql');
            $zip->close();

            $zipData = file_get_contents($zipPath);

            DB::table('database_backups')->insert([
                'filename' => $base . '.zip',
                'size' => strlen($zipData),
                'data' => base64_encode($zipData),
                'created_at' => now(),
            ]);

            BackupLog::create([
                'action' => 'backup_created',
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Backup បង្កើតដោយជោគជ័យ។',
            ]);

        } catch (Throwable $e) {
            Log::error('Backup creation threw an exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup failed.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        } finally {
            @unlink($sqlPath);
            @unlink($zipPath);
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:512000',
        ]);

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $file = $request->file('backup_file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['sql', 'zip'], true)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['backup_file' => ['សូមជ្រើសរើស File .sql ឬ .zip']],
            ], 422);
        }

        $sqlPath = $file->getRealPath();
        $tempPath = null;

        try {
            if ($ext === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($file->getRealPath()) !== true) {
                    throw new \RuntimeException('Cannot open zip file.');
                }

                $entry = null;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (str_ends_with(strtolower($name), '.sql')) {
                        $entry = $name;
                        break;
                    }
                }
                $zip->close();

                if (!$entry) {
                    throw new \RuntimeException('No .sql file found inside the zip.');
                }

                $tempPath = tempnam(sys_get_temp_dir(), 'rssql');
                copy('zip://' . $file->getRealPath() . '#' . $entry, $tempPath);
                $sqlPath = $tempPath;
            }

            $this->runSqlFile($sqlPath);

            BackupLog::create(['action' => 'restore', 'user_id' => auth()->id()]);

            return response()->json(['success' => true, 'message' => 'Restore បានសម្រេច។']);

        } catch (Throwable $e) {
            Log::error('Restore exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Restore failed.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);

        } finally {
            if ($tempPath) {
                @unlink($tempPath);
            }
        }
    }

    public function download(string $filename)
    {
        $row = DB::table('database_backups')
            ->where('filename', basename($filename))
            ->first();

        abort_unless($row, 404);

        return response(base64_decode($row->data))
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="' . $row->filename . '"');
    }

    public function destroy(string $filename)
    {
        $deleted = DB::table('database_backups')
            ->where('filename', basename($filename))
            ->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Backup file រកមិនឃើញ.'], 404);
        }

        BackupLog::create([
            'action' => 'backup_deleted',
            'filename' => basename($filename),
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Backup លុបបានសម្រេច។']);
    }

    /*
    |--------------------------------------------------------------------------
    | Pure-PHP dump / restore (works on Windows, Linux, any host)
    |--------------------------------------------------------------------------
    | Every statement is written on ONE line ending with ";" (CREATE TABLE
    | is multi-line but its last line ends with ";"). Newlines inside data
    | are escaped by PDO::quote, so the reader below can split safely.
    */

    private function dumpDatabase(string $path): void
    {
        $pdo = DB::connection()->getPdo();
        $h = fopen($path, 'w');

        fwrite($h, "-- Backup generated " . now() . "\n\n");
        fwrite($h, "SET NAMES utf8mb4;\n");
        fwrite($h, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn($r) => array_values((array) $r)[0])
            ->reject(fn($t) => in_array($t, $this->excludedTables, true))
            ->values();

        foreach ($tables as $table) {
            $q = $this->quoteName($table);

            $create = (array) DB::select("SHOW CREATE TABLE $q")[0];
            fwrite($h, "DROP TABLE IF EXISTS $q;\n");
            fwrite($h, $create['Create Table'] . ";\n\n");

            $limit = 500;
            $offset = 0;

            do {
                $rows = DB::select("SELECT * FROM $q LIMIT $limit OFFSET $offset");

                if ($rows) {
                    $cols = implode(',', array_map(
                        fn($c) => $this->quoteName($c),
                        array_keys((array) $rows[0])
                    ));

                    $values = [];
                    foreach ($rows as $row) {
                        $values[] = '(' . implode(',', array_map(
                            fn($v) => $this->sqlValue($pdo, $v),
                            array_values((array) $row)
                        )) . ')';
                    }

                    fwrite($h, "INSERT INTO $q ($cols) VALUES " . implode(',', $values) . ";\n");
                }

                $offset += $limit;
            } while (count($rows) === $limit);

            fwrite($h, "\n");
        }

        fwrite($h, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($h);
    }

    private function runSqlFile(string $path): void
    {
        $h = fopen($path, 'r');
        if (!$h) {
            throw new \RuntimeException('Cannot read SQL file.');
        }

        $buffer = '';

        try {
            DB::unprepared('SET FOREIGN_KEY_CHECKS=0');

            while (($line = fgets($h)) !== false) {
                $trim = rtrim($line);

                if ($buffer === '' && ($trim === '' || str_starts_with($trim, '--'))) {
                    continue;
                }

                $buffer .= $line;

                if (substr($trim, -1) === ';') {
                    DB::unprepared($buffer);
                    $buffer = '';
                }
            }
        } finally {
            fclose($h);
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function quoteName(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    private function sqlValue(\PDO $pdo, $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }
        if (!mb_check_encoding($v, 'UTF-8')) {
            return '0x' . bin2hex($v); // binary / blob columns
        }

        return $pdo->quote($v);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = min(floor(($bytes ? log($bytes) : 0) / log(1024)), count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
