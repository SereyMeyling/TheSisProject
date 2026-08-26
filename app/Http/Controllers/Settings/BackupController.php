<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting\BackupLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Throwable;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    protected string $disk = 'local';
    protected string $backupName;

    public function __construct()
    {
        $this->backupName = config('backup.backup.name', config('app.name'));
    }

    public function index()
    {
        return view('form.settings.backup.backupPage');
    }

    public function list()
    {
        $files = Storage::disk($this->disk)->allFiles($this->backupName);

        $backups = collect($files)
            ->filter(fn($file) => str_ends_with($file, '.zip'))
            ->map(fn($file) => [
                'filename' => basename($file),
                'size' => $this->formatBytes(Storage::disk($this->disk)->size($file)),
                'created_at' => Carbon::createFromTimestamp(
                    Storage::disk($this->disk)->lastModified($file)
                )->format('d-m-Y h:i A'),
            ])
            ->sortByDesc('created_at')
            ->values();

        return response()->json(['data' => $backups]);
    }

    public function store(Request $request)
    {

        set_time_limit(300);

        $config = config('database.connections.' . config('database.default'));

        // Adjust this to match your actual mysqldump.exe location
        $mysqldump = 'C:\wamp64\bin\mysql\mysql9.1.0\bin\mysqldump.exe';

        $backupDir = $this->backupName;
        Storage::disk($this->disk)->makeDirectory($backupDir);

        $sqlFilename = 'backup_' . now()->format('Y-m-d_His') . '.sql';
        $sqlFullPath = Storage::disk($this->disk)->path("$backupDir/$sqlFilename");

        try {
            $process = new \Symfony\Component\Process\Process([
                $mysqldump,
                '--host=' . $config['host'],
                '--port=' . $config['port'],
                '--user=' . $config['username'],
                '--single-transaction',
                '--routines',
                '--triggers',
                $config['database'],
            ]);


            $process->setEnv([
                'MYSQL_PWD' => $config['password'],
                'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
                'windir' => getenv('windir') ?: 'C:\\Windows',
                'PATH' => getenv('PATH'),
            ]);
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('Backup failed', ['error' => $process->getErrorOutput()]);

                return response()->json([
                    'success' => false,
                    'message' => 'Backup failed.',
                    'debug' => config('app.debug') ? $process->getErrorOutput() : null,
                ], 500);
            }

            // Write the raw SQL dump to disk
            file_put_contents($sqlFullPath, $process->getOutput());

            if (!file_exists($sqlFullPath) || filesize($sqlFullPath) === 0) {
                Log::error('Backup produced an empty file', ['path' => $sqlFullPath]);

                return response()->json([
                    'success' => false,
                    'message' => 'Backup failed.',
                    'debug' => config('app.debug') ? 'Dump file was empty.' : null,
                ], 500);
            }


            $zipFilename = str_replace('.sql', '.zip', $sqlFilename);
            $zipFullPath = Storage::disk($this->disk)->path("$backupDir/$zipFilename");

            $zip = new \ZipArchive();

            if ($zip->open($zipFullPath, \ZipArchive::CREATE) !== true) {
                Log::error('Failed to create zip archive', ['path' => $zipFullPath]);

                return response()->json([
                    'success' => false,
                    'message' => 'Backup failed.',
                    'debug' => config('app.debug') ? 'Could not create zip archive.' : null,
                ], 500);
            }

            $zip->addFile($sqlFullPath, $sqlFilename);
            $zip->close();


            unlink($sqlFullPath);

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


            if (file_exists($sqlFullPath)) {
                @unlink($sqlFullPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Backup failed.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    public function download(string $filename)
    {
        $path = $this->backupName . '/' . basename($filename);

        abort_unless(Storage::disk($this->disk)->exists($path), 404);

        return Storage::disk($this->disk)->download($path);
    }

    public function destroy(string $filename)
    {
        $path = $this->backupName . '/' . basename($filename);

        if (!Storage::disk($this->disk)->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Backup file រកមិនឃើញ.'], 404);
        }

        Storage::disk($this->disk)->delete($path);

        BackupLog::create([
            'action' => 'backup_deleted',
            'filename' => basename($filename),
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Backup លុបបានសម្រេច។']);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = min(floor(($bytes ? log($bytes) : 0) / log(1024)), count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:512000',
        ]);

        $config = config('database.connections.' . config('database.default'));
        $mysql = 'C:\wamp64\bin\mysql\mysql9.1.0\bin\mysql.exe';

        try {
            $process = new Process([
                $mysql,
                '--host=' . $config['host'],
                '--port=' . $config['port'],
                '--user=' . $config['username'],
                $config['database'],
            ]);
            $process->setEnv([
                'MYSQL_PWD' => $config['password'],
                'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
                'windir' => getenv('windir') ?: 'C:\\Windows',
                'PATH' => getenv('PATH'),
            ]);
            $process->setInput(fopen($request->file('backup_file')->getRealPath(), 'r'));
            $process->setTimeout(300);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('Restore failed', ['error' => $process->getErrorOutput()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Restore failed.',
                    'debug' => config('app.debug') ? $process->getErrorOutput() : null,
                ], 500);
            }

            BackupLog::create(['action' => 'restore', 'user_id' => auth()->id()]);

            return response()->json(['success' => true, 'message' => 'Restore បានសម្រេច។']);
        } catch (Throwable $e) {
            Log::error('Restore exception', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Restore failed.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
