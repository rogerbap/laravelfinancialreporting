<?php
// app/Http/Controllers/ImportExportController.php

namespace App\Http\Controllers;

use App\Imports\TransactionImport;
use App\Exports\TransactionExport;
use App\Models\{Transaction, Category};
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\ImportRequest;
use Illuminate\Support\Facades\Log;

class ImportExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showImportForm()
    {
        $this->authorize('import', Transaction::class);
        
        $categories = Category::active()->orderBy('name')->get();
        $recentImports = $this->getRecentImports();

        return view('import-export.import', compact('categories', 'recentImports'));
    }

    public function import(ImportRequest $request)
    {
        $this->authorize('import', Transaction::class);

        try {
            $file = $request->file('import_file');
            $import = new TransactionImport();
            
            Excel::import($import, $file);

            $importedCount = $import->getRowCount();
            $errors = $import->getErrors();

            if (!empty($errors)) {
                return back()
                    ->with('warning', "Import completed with {$importedCount} records, but some errors occurred.")
                    ->with('import_errors', $errors);
            }

            return back()->with('success', "Successfully imported {$importedCount} transactions.");

        } catch (\Exception $e) {
            Log::error('Import failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'file' => $request->file('import_file')->getClientOriginalName()
            ]);

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $this->authorize('export', Transaction::class);

        $filters = $request->only(['date_from', 'date_to', 'category_id', 'type', 'status']);
        $format = $request->get('format', 'xlsx');

        $filename = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

        try {
            return Excel::download(new TransactionExport($filters), $filename);
        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'filters' => $filters
            ]);

            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Date', 'Description', 'Amount', 'Type', 'Category Code', 'Reference Number'
        ];

        $sampleData = [
            ['2024-01-15', 'Office Supplies Purchase', '150.00', 'expense', 'OFF', 'TXN20240115001'],
            ['2024-01-16', 'Client Payment Received', '2500.00', 'income', 'REV', 'TXN20240116001'],
            ['2024-01-17', 'Marketing Campaign', '800.00', 'expense', 'MKT', 'TXN20240117001'],
        ];

        $export = new class($headers, $sampleData) implements \Maatwebsite\Excel\Concerns\FromArray {
            public function __construct(private array $headers, private array $data) {}
            
            public function array(): array
            {
                return array_merge([$this->headers], $this->data);
            }
        };

        return Excel::download($export, 'transaction_import_template.xlsx');
    }

    private function getRecentImports()
    {
        // This would typically come from an import_logs table
        // For now, return mock data
        return collect([
            [
                'filename' => 'transactions_jan_2024.xlsx',
                'imported_at' => now()->subDays(2),
                'records_count' => 45,
                'status' => 'success',
                'user_name' => 'John Doe'
            ],
            [
                'filename' => 'expenses_dec_2023.csv',
                'imported_at' => now()->subWeek(),
                'records_count' => 23,
                'status' => 'partial',
                'user_name' => 'Jane Smith'
            ]
        ]);
    }
}