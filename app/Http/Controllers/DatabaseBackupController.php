<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseBackupController extends Controller
{
    // Tabelle di sistema da escludere dal backup
    private array $excludedTables = [
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function index()
    {
        return view('backup.index');
    }

    public function export()
    {
        $database = config('database.connections.mysql.database');
        $key      = 'Tables_in_' . $database;

        $tables = DB::select('SHOW TABLES');

        $data = [
            'exported_at' => now()->toISOString(),
            'database'    => $database,
            'tables'      => [],
        ];

        foreach ($tables as $tableObj) {
            $tableName = $tableObj->$key;

            if (in_array($tableName, $this->excludedTables)) {
                continue;
            }

            $rows = DB::table($tableName)->get()->map(fn ($row) => (array) $row)->toArray();
            $data['tables'][$tableName] = $rows;
        }

        $json     = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = $database . '_' . now()->format('Y-m-d_His') . '.json';

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:102400'], // 100 MB
        ]);

        $content = file_get_contents($request->file('backup_file')->getRealPath());
        $data    = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->with('error', 'Il file non è un JSON valido: ' . json_last_error_msg());
        }

        if (! isset($data['tables']) || ! is_array($data['tables'])) {
            return back()->with('error', 'Formato non riconosciuto: manca la chiave "tables".');
        }

        $database      = config('database.connections.mysql.database');
        $key           = 'Tables_in_' . $database;
        $existingNames = array_map(fn ($t) => $t->$key, DB::select('SHOW TABLES'));

        $unknownTables = array_diff(array_keys($data['tables']), $existingNames);
        if (! empty($unknownTables)) {
            return back()->with('error', 'Il backup contiene tabelle inesistenti nel database corrente: ' . implode(', ', $unknownTables));
        }

        DB::transaction(function () use ($data) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($data['tables'] as $tableName => $rows) {
                DB::table($tableName)->truncate();

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($tableName)->insert($chunk);
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        return back()->with('success', 'Database ripristinato con successo!');
    }
}
