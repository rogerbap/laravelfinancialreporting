<?php
// app/Services/CacheService.php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\{Transaction, Category, Report};

class CacheService
{
    const CACHE_TTL = 3600; // 1 hour

    public static function getCategoryStats($categoryId): array
    {
        return Cache::remember(
            "category_stats_{$categoryId}",
            self::CACHE_TTL,
            function () use ($categoryId) {
                $category = Category::find($categoryId);
                
                return [
                    'total_transactions' => $category->transactions()->count(),
                    'total_amount' => $category->transactions()->sum('amount'),
                    'monthly_average' => $category->transactions()
                        ->where('transaction_date', '>=', now()->subMonths(12))
                        ->avg('amount'),
                    'last_transaction' => $category->transactions()
                        ->latest('transaction_date')
                        ->first()?->transaction_date,
                ];
            }
        );
    }

    public static function getDashboardData($userId, $period): array
    {
        return Cache::remember(
            "dashboard_{$userId}_{$period}",
            300, // 5 minutes for dashboard data
            function () use ($userId, $period) {
                // Dashboard calculation logic here
                return [
                    'summary' => [],
                    'trends' => [],
                    'categories' => [],
                ];
            }
        );
    }

    public static function getReportData($reportId): array
    {
        return Cache::remember(
            "report_data_{$reportId}",
            self::CACHE_TTL,
            function () use ($reportId) {
                $report = Report::find($reportId);
                return [
                    'summary' => $report->generateSummary(),
                    'chart_data' => app(ReportService::class)->generateChartData($report),
                ];
            }
        );
    }

    public static function clearCategoryCache($categoryId): void
    {
        Cache::forget("category_stats_{$categoryId}");
        
        // Clear related dashboard caches
        Cache::tags(['dashboard'])->flush();
    }

    public static function clearUserCache($userId): void
    {
        Cache::forget("dashboard_{$userId}_current_month");
        Cache::forget("dashboard_{$userId}_last_month");
        Cache::forget("dashboard_{$userId}_current_quarter");
        Cache::forget("dashboard_{$userId}_current_year");
    }

    public static function clearReportCache($reportId): void
    {
        Cache::forget("report_data_{$reportId}");
    }
}