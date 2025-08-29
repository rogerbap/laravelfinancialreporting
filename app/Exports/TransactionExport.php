<?php
// app/Exports/TransactionExport.php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\{
    FromQuery,
    Exportable,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    ShouldAutoSize
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths, ShouldAutoSize
{
    use Exportable;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Transaction::with(['category', 'creator'])
            ->orderBy('transaction_date', 'desc');

        // Apply filters
        if (!empty($this->filters['date_from'])) {
            $query->where('transaction_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('transaction_date', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Reference Number',
            'Date',
            'Description',
            'Amount',
            'Type',
            'Category',
            'Category Code',
            'Status',
            'Created By',
            'Created At',
            'Approved By',
            'Approved At'
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->reference_number,
            $transaction->transaction_date->format('Y-m-d'),
            $transaction->description,
            $transaction->amount,
            ucfirst($transaction->type),
            $transaction->category->name,
            $transaction->category->code,
            ucfirst($transaction->status),
            $transaction->creator->name,
            $transaction->created_at->format('Y-m-d H:i:s'),
            $transaction->approver->name ?? '',
            $transaction->approved_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Reference Number
            'B' => 12, // Date
            'C' => 40, // Description
            'D' => 15, // Amount
            'E' => 10, // Type
            'F' => 20, // Category
            'G' => 15, // Category Code
            'H' => 12, // Status
            'I' => 20, // Created By
            'J' => 20, // Created At
            'K' => 20, // Approved By
            'L' => 20, // Approved At
        ];
    }
}