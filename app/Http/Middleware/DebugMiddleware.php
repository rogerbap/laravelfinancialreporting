<?php
// app/Http/Middleware/DebugMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\DebugService;

class DebugMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('local') && config('app.debug')) {
            $startTime = microtime(true);
            
            // Log request details
            Log::debug('Request started', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
            ]);

            // Enable query logging
            DebugService::enableQueryLogging();
        }

        $response = $next($request);

        if (app()->environment('local') && config('app.debug')) {
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            Log::debug('Request completed', [
                'status' => $response->getStatusCode(),
                'execution_time' => $executionTime . 'ms',
                'memory_usage' => round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB',
            ]);
        }

        return $response;
    }
}