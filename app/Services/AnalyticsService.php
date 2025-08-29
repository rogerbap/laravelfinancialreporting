<?php
// app/Services/AnalyticsService.php

namespace App\Services;

use App\Models\{Transaction, Category, Report};
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function getMonthlyTrends(int $months = 12): Collection
    {
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        
        $transactions = Transaction::where('transaction_date', '>=', $startDate)
            ->where('status', 'approved')
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->transaction_date->format('Y-m');
            });

        $trends = collect();
        
        for ($i = 0; $i < $months; $i++) {
            $month = Carbon::now()->subMonths($months - 1 - $i);
            $key = $month->format('Y-m');
            $monthTransactions = $transactions->get($key, collect());
            
            $trends->push([
                'month' => $month->format('M Y'),
                'income' => $monthTransactions->where('type', 'income')->sum('amount'),
                'expenses' => $monthTransactions->where('type', 'expense')->sum('amount'),
                'net' => $monthTransactions->where('type', 'income')->sum('amount') - 
                        $monthTransactions->where('type', 'expense')->sum('amount'),
                'transaction_count' => $monthTransactions->count(),
            ]);
        }

        return $trends;
    }

    public function getCategoryAnalytics($dateFrom = null, $dateTo = null): Collection
    {
        $query = Transaction::with('category')
            ->where('status', 'approved');

        if ($dateFrom) {
            $query->where('transaction_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('transaction_date', '<=', $dateTo);
        }

        return $query->get()
            ->groupBy('category.name')
            ->map(function ($transactions, $categoryName) {
                $category = $transactions->first()->category;
                return [
                    'name' => $categoryName,
                    'color' => $category->color,
                    'total_amount' => $transactions->sum('amount'),
                    'transaction_count' => $transactions->count(),
                    'avg_amount' => $transactions->avg('amount'),
                    'by_type' => [
                        'income' => $transactions->where('type', 'income')->sum('amount'),
                        'expense' => $transactions->where('type', 'expense')->sum('amount'),
                    ]
                ];
            })
            ->sortByDesc('total_amount')
            ->values();
    }

    public function getTopSpendingCategories(int $limit = 5, $dateFrom = null, $dateTo = null): Collection
    {
        return $this->getCategoryAnalytics($dateFrom, $dateTo)
            ->where('by_type.expense', '>', 0)
            ->sortByDesc('by_type.expense')
            ->take($limit)
            ->values();
    }

    public function getBudgetAnalysis($budgets = []): array
    {
        $currentMonth = Carbon::now();
        $monthlyExpenses = Transaction::where('type', 'expense')
            ->where('status', 'approved')
            ->whereMonth('transaction_date', $currentMonth->month)
            ->whereYear('transaction_date', $currentMonth->year)
            ->with('category')
            ->get()
            ->groupBy('category.name')
            ->map->sum('amount');

        $analysis = [];
        foreach ($budgets as $categoryName => $budgetAmount) {
            $spent = $monthlyExpenses->get($categoryName, 0);
            $remaining = $budgetAmount - $spent;
            $percentage = $budgetAmount > 0 ? ($spent / $budgetAmount) * 100 : 0;

            $analysis[$categoryName] = [
                'budget' => $budgetAmount,
                'spent' => $spent,
                'remaining' => $remaining,
                'percentage' => round($percentage, 2),
                'status' => $this->getBudgetStatus($percentage)
            ];
        }

        return $analysis;
    }

    private function getBudgetStatus(float $percentage): string
    {
        if ($percentage <= 50) return 'good';
        if ($percentage <= 80) return 'warning';
        if ($percentage <= 100) return 'danger';
        return 'over-budget';
    }

    public function generateInsights($dateFrom = null, $dateTo = null): array
    {
        $transactions = Transaction::where('status', 'approved');
        
        if ($dateFrom) $transactions->where('transaction_date', '>=', $dateFrom);
        if ($dateTo) $transactions->where('transaction_date', '<=', $dateTo);
        
        $data = $transactions->get();
        
        return [
            'total_income' => $data->where('type', 'income')->sum('amount'),
            'total_expenses' => $data->where('type', 'expense')->sum('amount'),
            'net_income' => $data->where('type', 'income')->sum('amount') - $data->where('type', 'expense')->sum('amount'),
            'avg_transaction_amount' => $data->avg('amount'),
            'largest_expense' => $data->where('type', 'expense')->max('amount'),
            'largest_income' => $data->where('type', 'income')->max('amount'),
            'most_active_day' => $data->groupBy(function($t) { 
                return $t->transaction_date->format('l'); 
            })->map->count()->sortDesc()->keys()->first(),
            'expense_trend' => $this->calculateTrend($data->where('type', 'expense')),
        ];
    }

    private function calculateTrend(Collection $transactions): string
    {
        if ($transactions->count() < 2) return 'stable';
        
        $grouped = $transactions->groupBy(function ($t) {
            return $t->transaction_date->format('Y-m');
        })->map->sum('amount');
        
        if ($grouped->count() < 2) return 'stable';
        
        $values = $grouped->values();
        $recent = $values->take(-2);
        
        if ($recent->count() < 2) return 'stable';
        
        $change = (($recent->last() - $recent->first()) / $recent->first()) * 100;
        
        if ($change > 10) return 'increasing';
        if ($change < -10) return 'decreasing';
        return 'stable';
    }
}