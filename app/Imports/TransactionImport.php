<?php
// app/Imports/TransactionImport.php

namespace App\Imports;

use App\Models\{Transaction, Category};
use Maatwebsite\Excel\Concerns\{
    ToModel, 
    WithHeadingRow, 
    WithValidation,
    WithChunkReading,
    SkipsEmptyRows,
    SkipsErrors,
    SkipsFailures
};
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TransactionImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsEmptyRows, SkipsErrors, SkipsFailures
{
    use Importable;

    private $errors = [];
    private $rowCount = 0;

    public function model(array $row)
    {
        $this->rowCount++;

        try {
            // Find category by code
            $category = Category::where('code', strtoupper($row['category_code']))->first();
            
            if (!$category) {
                $this->errors[] = "Row {$this->rowCount}: Category code '{$row['category_code']}' not found";
                return null;
            }

            return new Transaction([
                'reference_number' => $row['reference_number'] ?? Transaction::generateReferenceNumber(),
                'description' => $row['description'],
                'amount' => $row['amount'],
                'type' => strtolower($row['type']),
                'transaction_date' => Carbon::parse($row['date']),
                'category_id' => $category->id,
                'created_by' => auth()->id(),
                'status' => 'pending',
            ]);

        } catch (\Exception $e) {
            $this->errors[] = "Row {$this->rowCount}: " . $e->getMessage();
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'category_code' => 'required|string|exists:categories,code',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'date.required' => 'Date is required',
            'amount.numeric' => 'Amount must be a valid number',
            'type.in' => 'Type must be income, expense, or transfer',
            'category_code.exists' => 'Category code does not exist',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getErrors(): array
    {
        return array_merge($this->errors, $this->failures()->toArray());
    }
}