<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\{Transaction, Category, Report};
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $dateRange = $this->getDateRange($period);

        $data = [
            'summary' => $this->getSummaryData($dateRange),
            'recentTransactions' => $this->getRecentTransactions(),
            'categoryBreakdown' => $this->getCategoryBreakdown($dateRange),
            'monthlyTrends' => $this->getMonthlyTrends(),
            'topCategories' => $this->getTopCategories($dateRange),
        ];

        return view('dashboard.index', compact('data', 'period'));
    }

    private function getSummaryData($dateRange)
    {
        $transactions = Transaction::byDateRange($dateRange['start'], $dateRange['end']);

        return [
            'total_income' => $transactions->byType('income')->sum('amount'),
            'total_expenses' => $transactions->byType('expense')->sum('amount'),
            'total_transactions' => $transactions->count(),
            'pending_approvals' => Transaction::byStatus('pending')->count(),
        ];
    }

    private function getRecentTransactions()
    {
        return Transaction::with(['category', 'creator'])
            ->latest()
            ->take(10)
            ->get();
    }

    private function getCategoryBreakdown($dateRange)
    {
        return Transaction::with('category')
            ->byDateRange($dateRange['start'], $dateRange['end'])
            ->get()
            ->groupBy('category.name')
            ->map->sum('amount')
            ->sortDesc();
    }

    private function getMonthlyTrends()
    {
        return $this->analyticsService->getMonthlyTrends(12);
    }

    private function getTopCategories($dateRange)
    {
        return Category::withSum(['transactions' => function ($query) use ($dateRange) {
            $query->byDateRange($dateRange['start'], $dateRange['end']);
        }], 'amount')
            ->orderByDesc('transactions_sum_amount')
            ->take(5)
            ->get();
    }

    private function getDateRange($period)
    {
        return match ($period) {
            'current_month' => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],
            'last_month' => [
                'start' => Carbon::now()->subMonth()->startOfMonth(),
                'end' => Carbon::now()->subMonth()->endOfMonth(),
            ],
            'current_quarter' => [
                'start' => Carbon::now()->startOfQuarter(),
                'end' => Carbon::now()->endOfQuarter(),
            ],
            'current_year' => [
                'start' => Carbon::now()->startOfYear(),
                'end' => Carbon::now()->endOfYear(),
            ],
            default => [
                'start' => Carbon::now()->startOfMonth(),
                'end' => Carbon::now()->endOfMonth(),
            ],
        };
    }
}