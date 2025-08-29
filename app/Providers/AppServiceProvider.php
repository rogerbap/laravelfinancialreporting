<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\{Transaction, Report, Category};
use App\Observers\AuditObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register observers
        Transaction::observe(AuditObserver::class);
        Report::observe(AuditObserver::class);
        Category::observe(AuditObserver::class);
    }
}