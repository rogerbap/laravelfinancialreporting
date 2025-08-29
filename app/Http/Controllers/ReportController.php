<?php
// app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use App\Models\{Report, Category, Transaction};
use App\Http\Requests\{StoreReportRequest, UpdateReportRequest};
use App\Services\ReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->middleware('auth');
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $query = Report::with('creator')->latest();

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(15);

        return view('reports.index', compact('reports'));
    }

    public function show(Report $report)
    {
        $this->authorize('view', $report);
        
        $summary = $report->generateSummary();
        $chartData = $this->reportService->generateChartData($report);

        return view('reports.show', compact('report', 'summary', 'chartData'));
    }

    public function create()
    {
        $this->authorize('create', Report::class);
        
        $categories = Category::active()->orderBy('name')->get();

        return view('reports.create', compact('categories'));
    }

    public function store(StoreReportRequest $request)
    {
        $this->authorize('create', Report::class);

        $data = $request->validated();
        $data['created_by'] = auth()->id();

        // Calculate totals based on filters
        $transactionData = $this->reportService->calculateTotals(
            $data['period_start'],
            $data['period_end'],
            $data['filters'] ?? []
        );

        $data['total_amount'] = $transactionData['total_amount'];
        $data['transaction_count'] = $transactionData['transaction_count'];

        $report = Report::create($data);

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report created successfully.');
    }

    public function edit(Report $report)
    {
        $this->authorize('update', $report);
        
        $categories = Category::active()->orderBy('name')->get();

        return view('reports.edit', compact('report', 'categories'));
    }

    public function update(UpdateReportRequest $request, Report $report)
    {
        $this->authorize('update', $report);

        $data = $request->validated();

        // Recalculate totals if period or filters changed
        if ($request->hasAny(['period_start', 'period_end', 'filters'])) {
            $transactionData = $this->reportService->calculateTotals(
                $data['period_start'] ?? $report->period_start,
                $data['period_end'] ?? $report->period_end,
                $data['filters'] ?? $report->filters ?? []
            );

            $data['total_amount'] = $transactionData['total_amount'];
            $data['transaction_count'] = $transactionData['transaction_count'];
        }

        $report->update($data);

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report updated successfully.');
    }

    public function destroy(Report $report)
    {
        $this->authorize('delete', $report);

        $report->delete();

        return redirect()
            ->route('reports.index')
            ->with('success', 'Report deleted successfully.');
    }

    public function publish(Report $report)
    {
        $this->authorize('publish', $report);

        $report->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success', 'Report published successfully.');
    }

    public function download(Report $report, Request $request)
    {
        $this->authorize('view', $report);

        $format = $request->get('format', 'pdf');

        return $this->reportService->downloadReport($report, $format);
    }

    public function generateQuickReport(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $type = $request->get('type', 'summary');

        $reportData = $this->reportService->generateQuickReport($period, $type);

        return view('reports.quick', compact('reportData', 'period', 'type'));
    }
}