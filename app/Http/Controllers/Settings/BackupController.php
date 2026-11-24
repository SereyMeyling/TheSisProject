<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting\BackupLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            'current_password' => 'required|string',
        ], [
            'current_password.required' => 'សូមបញ្ចូលពាក្យសម្ងាត់បច្ចុប្បន្ន / Please enter your current password.',
        ]);

        // Re-confirm the admin's password: a restore replaces the whole
        // database (all patient records), so a stolen session alone must
        // not be enough.
        if (!Hash::check($request->input('current_password'), $request->user()->password)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['current_password' => ['ពាក្យសម្ងាត់បច្ចុប្បន្នមិនត្រឹមត្រូវទេ / Current password is incorrect.']],
            ], 422);
        }

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
        } catch (Throwable $e) {
            Log::warning('Restore: could not read uploaded backup', ['message' => $e->getMessage()]);

            if ($tempPath) {
                @unlink($tempPath);
            }

            return $this->invalidBackupResponse();
        }

        try {
            // Stage 1: validate the WHOLE file before touching the database.
            if (!$this->looksLikeSqlDump($sqlPath)) {
                return $this->invalidBackupResponse();
            }

            // Stage 2: run it.
            $statementNo = 0;

            try {
                $this->runSqlFile($sqlPath, $statementNo);
            } catch (Throwable $e) {
                // Log details server-side only; never return raw SQL / DB errors.
                Log::error('Restore failed while executing statement', [
                    'statement_no' => $statementNo,
                    'error_code' => $e->getCode(),
                    'message' => mb_substr($e->getMessage(), 0, 500),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Restore បរាជ័យនៅ statement #{$statementNo}។ ទិន្នន័យអាចត្រូវបាន Restore ត្រឹមតែមួយផ្នែក — សូម Restore ពី Backup ដែលល្អម្ដងទៀត។ / "
                        . "Restore failed at statement #{$statementNo}. The database may be partially restored — please restore again from a known-good backup.",
                ], 500);
            }

            BackupLog::create(['action' => 'restore', 'user_id' => auth()->id()]);

            return response()->json(['success' => true, 'message' => 'Restore បានសម្រេច។']);

        } catch (Throwable $e) {
            Log::error('Restore exception', [
                'message' => mb_substr($e->getMessage(), 0, 500),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Restore មិនជោគជ័យ។ / Restore failed.',
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

    /**
     * Statement prefixes a restorable dump may contain (our own dumpDatabase()
     * output, plus the usual mysqldump header/footer lines). Anything else
     * (GRANT, CREATE USER, LOAD DATA, SELECT ... INTO OUTFILE, DELIMITER,
     * triggers/procedures, ...) makes the whole file rejected.
     * This is a sanity check that the upload is a backup dump, not a
     * sandbox: only password-confirmed admins can reach restore at all.
     */
    private const ALLOWED_STATEMENT = '/^(?:'
        . 'SET\s'
        . '|DROP\s+TABLE\s'
        . '|CREATE\s+TABLE\s'
        . '|INSERT\s+INTO\s'
        . '|LOCK\s+TABLES\s'
        . '|UNLOCK\s+TABLES\b'
        . '|ALTER\s+TABLE\s+`[^`]+`\s+(?:DISABLE|ENABLE)\s+KEYS\b'
        . '|\/\*!\d+\s+(?:SET\s|ALTER\s+TABLE\s+`[^`]+`\s+(?:DISABLE|ENABLE)\s+KEYS\b)'
        . ')/i';

    /**
     * Yields each SQL statement of the file (same splitting rules as the
     * dump writer above: a statement ends on a line ending with ";").
     */
    private function readStatements(string $path): \Generator
    {
        $h = fopen($path, 'r');
        if (!$h) {
            throw new \RuntimeException('Cannot read SQL file.');
        }

        $buffer = '';

        try {
            while (($line = fgets($h)) !== false) {
                $trim = rtrim($line);

                if ($buffer === '' && ($trim === '' || str_starts_with($trim, '--'))) {
                    continue;
                }

                $buffer .= $line;

                if (substr($trim, -1) === ';') {
                    yield $buffer;
                    $buffer = '';
                }
            }

            if (trim($buffer) !== '') {
                yield $buffer; // trailing statement without ";"
            }
        } finally {
            fclose($h);
        }
    }

    /**
     * Checks the uploaded file really is a table/data SQL dump before we
     * run anything: readable text, contains at least one CREATE TABLE or
     * INSERT, and every statement is on the allow-list.
     */
    private function looksLikeSqlDump(string $path): bool
    {
        $head = @file_get_contents($path, false, null, 0, 8192);
        if ($head === false || trim($head) === '' || str_contains($head, "\0")) {
            return false; // empty or binary
        }

        $hasData = false;

        foreach ($this->readStatements($path) as $sql) {
            $stmt = ltrim($sql);

            if (substr(rtrim($stmt), -1) !== ';' || !preg_match(self::ALLOWED_STATEMENT, $stmt)) {
                return false;
            }

            if (preg_match('/^(CREATE\s+TABLE|INSERT\s+INTO)\s/i', $stmt)) {
                $hasData = true;
            }
        }

        return $hasData;
    }

    private function invalidBackupResponse()
    {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => ['backup_file' => [
                'File នេះមិនមែនជា Backup SQL ត្រឹមត្រូវទេ។ / This file is not a valid SQL backup.',
            ]],
        ], 422);
    }

    /**
     * Executes the dump statement by statement.
     *
     * NOTE: this cannot be wrapped in a DB transaction. Every dump starts
     * with DROP TABLE / CREATE TABLE, and in MySQL DDL statements cause an
     * implicit COMMIT, so a ROLLBACK could never undo a half-finished
     * restore. Instead the file is fully validated first (looksLikeSqlDump)
     * and a failure reports which statement failed so the admin can restore
     * again from a known-good backup.
     *
     * Foreign key checks are disabled for the duration of the run (tables
     * are dropped/recreated in arbitrary order) and always re-enabled.
     */
    private function runSqlFile(string $path, int &$statementNo = 0): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->readStatements($path) as $sql) {
                $statementNo++;
                DB::unprepared($sql);
            }
        } finally {
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
