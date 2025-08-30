<?php
// app/Services/DebugService.php

namespace App\Services;

use Illuminate\Support\Facades\{Log, DB, Cache};
use Illuminate\Database\Events\QueryExecuted;

class DebugService
{
    public static function enableQueryLogging(): void
    {
        DB::listen(function (QueryExecuted $query) {
            Log::channel('queries')->info('Query executed', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'connection' => $query->connectionName,
            ]);
        });
    }

    public static function logPerformance(string $operation, callable $callback)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $result = $callback();

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        Log::info("Performance metrics for {$operation}", [
            'execution_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
            'memory_used' => round(($endMemory - $startMemory) / 1024 / 1024, 2) . 'MB',
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB',
        ]);

        return $result;
    }

    public static function dumpSql($query): void
    {
        if (app()->environment('local')) {
            dump($query->toSql(), $query->getBindings());
        }
    }

    public static function logImportProgress(string $filename, int $processed, int $total, array $errors = []): void
    {
        Log::info('Import progress', [
            'filename' => $filename,
            'processed' => $processed,
            'total' => $total,
            'percentage' => round(($processed / $total) * 100, 2),
            'errors_count' => count($errors),
            'errors' => array_slice($errors, 0, 5) // Log only first 5 errors
        ]);
    }

    public static function validateEnvironment(): array
    {
        $checks = [];

        // PHP version check
        $checks['php_version'] = [
            'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'pass' : 'fail',
            'message' => 'PHP ' . PHP_VERSION,
            'requirement' => 'PHP >= 8.1.0'
        ];

        // Extensions check
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
        foreach ($requiredExtensions as $extension) {
            $checks["ext_{$extension}"] = [
                'status' => extension_loaded($extension) ? 'pass' : 'fail',
                'message' => extension_loaded($extension) ? 'Loaded' : 'Missing',
                'requirement' => "Extension {$extension} required"
            ];
        }

        // Database connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'pass',
                'message' => 'Connected to ' . DB::connection()->getDatabaseName(),
                'requirement' => 'Database connection required'
            ];
        } catch (\Exception $e) {
            $checks['database'] = [
                'status' => 'fail',
                'message' => $e->getMessage(),
                'requirement' => 'Database connection required'
            ];
        }

        // Storage permissions
        $storagePath = storage_path();
        $checks['storage_writable'] = [
            'status' => is_writable($storagePath) ? 'pass' : 'fail',
            'message' => is_writable($storagePath) ? 'Writable' : 'Not writable',
            'requirement' => 'Storage directory must be writable'
        ];

        return $checks;
    }

    public static function clearAllCaches(): void
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            
            if (app()->environment('production')) {
                \Artisan::call('config:cache');
                \Artisan::call('route:cache');
                \Artisan::call('view:cache');
            }

            Log::info('All caches cleared successfully');
        } catch (\Exception $e) {
            Log::error('Failed to clear caches: ' . $e->getMessage());
        }
    }
}