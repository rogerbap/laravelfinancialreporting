<?php

namespace App\Http\Controllers;

use App\Models\{Transaction, Category, Report};
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['category', 'creator'])
            ->latest();

        // Apply basic filters if needed
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $transactions = $query->paginate(20);
        $categories = Category::active()->orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'categories'));
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['category', 'creator']);
        return view('transactions.show', compact('transaction'));
    }

    // Add other basic methods as needed
    public function create()
    {
        return view('transactions.create');
    }

    public function edit(Transaction $transaction)
    {
        return view('transactions.edit', compact('transaction'));
    }
}