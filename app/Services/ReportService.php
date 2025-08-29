<?php
// app/Services/ReportService.php

namespace App\Services;

use App\Models\{Transaction, Report, Category};
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;

class ReportService
{
    public function calculateTotals(string $periodStart, string $periodEnd, array $filters = []): array
    {
        $query = Transaction::where('status', 'approved')
            ->whereBetween('transaction_date', [$periodStart, $periodEnd]);

        // Apply filters
        if (!empty($filters['categories'])) {
            $query->whereIn('category_id', $filters['categories']);
        }

        if (!empty($filters['types'])) {
            $query->whereIn('type', $filters['types']);
        }

        $transactions = $query->get();

        return [
            'total_amount' => $transactions->sum('amount'),
            'transaction_count' => $transactions->count(),
            'total_income' => $transactions->where('type', 'income')->sum('amount'),
            'total_expenses' => $transactions->where('type', 'expense')->sum('amount'),
            'net_amount' => $transactions->where('type', 'income')->sum('amount') - 
                           $transactions->where('type', 'expense')->sum('amount'),
        ];
    }

    public function generateChartData(Report $report): array
    {
        $transactions = $this->getReportTransactions($report);

        return [
            'categoryBreakdown' => $this->generateCategoryBreakdown($transactions),
            'dailyTrends' => $this->generateDailyTrends($transactions, $report),
            'typeComparison' => $this->generateTypeComparison($transactions),
            'monthlyComparison' => $this->generateMonthlyComparison($transactions, $report),
        ];
    }

    public function downloadReport(Report $report, string $format = 'pdf')
    {
        $data = [
            'report' => $report,
            'summary' => $report->generateSummary(),
            'transactions' => $this->getReportTransactions($report),
            'chartData' => $this->generateChartData($report),
        ];

        switch (strtolower($format)) {
            case 'pdf':
                return $this->generatePdfReport($data);
            case 'excel':
            case 'xlsx':
                return $this->generateExcelReport($data);
            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }
    }

    public function generateQuickReport(string $period, string $type): array
    {
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $transactions = Transaction::where('status', 'approved')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['category', 'creator'])
            ->get();

        $data = [
            'period' => $period,
            'type' => $type,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'total_income' => $transactions->where('type', 'income')->sum('amount'),
                'total_expenses' => $transactions->where('type', 'expense')->sum('amount'),
                'transaction_count' => $transactions->count(),
                'categories_count' => $transactions->pluck('category_id')->unique()->count(),
            ],
            'transactions' => $transactions,
            'categoryBreakdown' => $transactions->groupBy('category.name')->map->sum('amount'),
            'dailyTrends' => $this->generateDailyTrendsForPeriod($transactions, $startDate, $endDate),
        ];

        return $data;
    }

    private function getReportTransactions(Report $report)
    {
        $query = Transaction::with(['category', 'creator'])
            ->whereBetween('transaction_date', [$report->period_start, $report->period_end])
            ->where('status', 'approved');

        if ($report->filters) {
            if (isset($report->filters['categories'])) {
                $query->whereIn('category_id', $report->filters['categories']);
            }
            if (isset($report->filters['types'])) {
                $query->whereIn('type', $report->filters['types']);
            }
        }

        return $query->get();
    }

    private function generateCategoryBreakdown($transactions): array
    {
        return $transactions->groupBy('category.name')
            ->map(function ($categoryTransactions, $categoryName) {
                return [
                    'name' => $categoryName,
                    'amount' => $categoryTransactions->sum('amount'),
                    'count' => $categoryTransactions->count(),
                    'color' => $categoryTransactions->first()->category->color,
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->toArray();
    }

    private function generateDailyTrends($transactions, Report $report): array
    {
        $period = Carbon::parse($report->period_start);
        $endPeriod = Carbon::parse($report->period_end);
        $trends = [];

        while ($period->lte($endPeriod)) {
            $dayTransactions = $transactions->where('transaction_date', $period->format('Y-m-d'));
            
            $trends[] = [
                'date' => $period->format('Y-m-d'),
                'income' => $dayTransactions->where('type', 'income')->sum('amount'),
                'expenses' => $dayTransactions->where('type', 'expense')->sum('amount'),
                'count' => $dayTransactions->count(),
            ];
            
            $period->addDay();
        }

        return $trends;
    }

    private function generateTypeComparison($transactions): array
    {
        return $transactions->groupBy('type')
            ->map(function ($typeTransactions, $type) {
                return [
                    'type' => ucfirst($type),
                    'amount' => $typeTransactions->sum('amount'),
                    'count' => $typeTransactions->count(),
                    'avg_amount' => $typeTransactions->avg('amount'),
                ];
            })
            ->values()
            ->toArray();
    }

    private function generateMonthlyComparison($transactions, Report $report): array
    {
        if (Carbon::parse($report->period_start)->diffInMonths(Carbon::parse($report->period_end)) < 1) {
            return [];
        }

        return $transactions->groupBy(function ($transaction) {
            return $transaction->transaction_date->format('Y-m');
        })
        ->map(function ($monthTransactions, $month) {
            return [
                'month' => Carbon::parse($month)->format('M Y'),
                'income' => $monthTransactions->where('type', 'income')->sum('amount'),
                'expenses' => $monthTransactions->where('type', 'expense')->sum('amount'),
                'count' => $monthTransactions->count(),
            ];
        })
        ->values()
        ->toArray();
    }

    private function generatePdfReport(array $data)
    {
        $pdf = PDF::loadView('reports.pdf', $data);
        $filename = 'report_' . $data['report']->id . '_' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    private function generateExcelReport(array $data)
    {
        $filename = 'report_' . $data['report']->id . '_' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new ReportExport($data), $filename);
    }

    private function getPeriodDates(string $period): array
    {
        return match ($period) {
            'current_month' => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
            'last_month' => [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ],
            'current_quarter' => [
                Carbon::now()->startOfQuarter(),
                Carbon::now()->endOfQuarter(),
            ],
            'last_quarter' => [
                Carbon::now()->subQuarter()->startOfQuarter(),
                Carbon::now()->subQuarter()->endOfQuarter(),
            ],
            'current_year' => [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ],
            'last_year' => [
                Carbon::now()->subYear()->startOfYear(),
                Carbon::now()->subYear()->endOfYear(),
            ],
            default => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
        };
    }

    private function generateDailyTrendsForPeriod($transactions, $startDate, $endDate): array
    {
        $period = Carbon::parse($startDate);
        $endPeriod = Carbon::parse($endDate);
        $trends = [];

        while ($period->lte($endPeriod)) {
            $dayTransactions = $transactions->filter(function ($transaction) use ($period) {
                return $transaction->transaction_date->format('Y-m-d') === $period->format('Y-m-d');
            });
            
            $trends[] = [
                'date' => $period->format('Y-m-d'),
                'income' => $dayTransactions->where('type', 'income')->sum('amount'),
                'expenses' => $dayTransactions->where('type', 'expense')->sum('amount'),
                'count' => $dayTransactions->count(),
            ];
            
            $period->addDay();
        }

        return $trends;
    }
}