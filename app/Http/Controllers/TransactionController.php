<?php
// app/Http/Controllers/TransactionController.php

namespace App\Http\Controllers;

use App\Models\{Transaction, Category, Report};
use App\Http\Requests\{StoreTransactionRequest, UpdateTransactionRequest};
use App\Services\FileUploadService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->middleware('auth');
        $this->fileUploadService = $fileUploadService;
    }

    public function index(Request $request)
    {
        $query = Transaction::with(['category', 'creator', 'approver'])
            ->latest();

        // Apply filters
        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        $transactions = $query->paginate(20);
        $categories = Category::active()->orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'categories'));
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);
        
        $transaction->load(['category', 'creator', 'approver', 'report']);
        $auditLogs = $transaction->auditLogs()->with('user')->latest()->get();

        return view('transactions.show', compact('transaction', 'auditLogs'));
    }

    public function create()
    {
        $this->authorize('create', Transaction::class);
        
        $categories = Category::active()->orderBy('name')->get();
        $reports = Report::where('status', '!=', 'archived')->orderBy('title')->get();

        return view('transactions.create', compact('categories', 'reports'));
    }

    public function store(StoreTransactionRequest $request)
    {
        $this->authorize('create', Transaction::class);

        $data = $request->validated();
        $data['created_by'] = auth()->id();

        // Handle file upload
        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $this->fileUploadService->upload(
                $request->file('receipt'),
                'receipts'
            );
        }

        $transaction = Transaction::create($data);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaction created successfully.');
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('update', $transaction);
        
        $categories = Category::active()->orderBy('name')->get();
        $reports = Report::where('status', '!=', 'archived')->orderBy('title')->get();

        return view('transactions.edit', compact('transaction', 'categories', 'reports'));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $data = $request->validated();

        // Handle file upload
        if ($request->hasFile('receipt')) {
            // Delete old receipt if exists
            if ($transaction->receipt_path) {
                $this->fileUploadService->delete($transaction->receipt_path);
            }
            
            $data['receipt_path'] = $this->fileUploadService->upload(
                $request->file('receipt'),
                'receipts'
            );
        }

        $transaction->update($data);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        // Delete associated file if exists
        if ($transaction->receipt_path) {
            $this->fileUploadService->delete($transaction->receipt_path);
        }

        $transaction->delete();

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }

    public function approve(Transaction $transaction)
    {
        $this->authorize('approve', $transaction);

        $transaction->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Transaction approved successfully.');
    }

    public function reject(Transaction $transaction)
    {
        $this->authorize('approve', $transaction);

        $transaction->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Transaction rejected successfully.');
    }
}