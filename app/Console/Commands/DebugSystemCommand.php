<?php
// app/Console/Commands/DebugSystemCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DebugService;

class DebugSystemCommand extends Command
{
    protected $signature = 'debug:system {--clear-cache : Clear all caches}';
    protected $description = 'Debug system requirements and configuration';

    public function handle()
    {
        $this->info('🔍 System Debug Report');
        $this->info('==================');

        if ($this->option('clear-cache')) {
            $this->info('Clearing all caches...');
            DebugService::clearAllCaches();
        }

        $checks = DebugService::validateEnvironment();
        
        foreach ($checks as $check => $result) {
            $status = $result['status'] === 'pass' ? '✅' : '❌';
            $this->line("{$status} {$check}: {$result['message']}");
            
            if ($result['status'] === 'fail') {
                $this->warn("   Requirement: {$result['requirement']}");
            }
        }

        $this->newLine();
        $this->info('Database Information:');
        $this->table(['Setting', 'Value'], [
            ['Database Name', config('database.connections.mysql.database')],
            ['Host', config('database.connections.mysql.host')],
            ['Port', config('database.connections.mysql.port')],
            ['Username', config('database.connections.mysql.username')],
        ]);

        $this->info('Application Settings:');
        $this->table(['Setting', 'Value'], [
            ['Environment', app()->environment()],
            ['Debug Mode', config('app.debug') ? 'ON' : 'OFF'],
            ['Log Level', config('logging.level', 'debug')],
            ['Timezone', config('app.timezone')],
            ['Locale', config('app.locale')],
        ]);

        return Command::SUCCESS;
    }
}