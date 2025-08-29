<?php
// app/Http/Controllers/Api/TransactionApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Http\Resources\{TransactionResource, TransactionCollection};
use App\Http\Requests\{StoreTransactionRequest, UpdateTransactionRequest};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): TransactionCollection
    {
        $query = Transaction::with(['category', 'creator'])
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

        $transactions = $query->paginate($request->get('per_page', 15));

        return new TransactionCollection($transactions);
    }

    public function show(Transaction $transaction): TransactionResource
    {
        $this->authorize('view', $transaction);
        
        return new TransactionResource(
            $transaction->load(['category', 'creator', 'approver', 'report'])
        );
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', Transaction::class);

        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $transaction = Transaction::create($data);
        $transaction->load(['category', 'creator']);

        return response()->json([
            'message' => 'Transaction created successfully',
            'data' => new TransactionResource($transaction)
        ], 201);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);

        $transaction->update($request->validated());
        $transaction->load(['category', 'creator', 'approver']);

        return response()->json([
            'message' => 'Transaction updated successfully',
            'data' => new TransactionResource($transaction)
        ]);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return response()->json([
            'message' => 'Transaction deleted successfully'
        ]);
    }

    public function approve(Transaction $transaction): JsonResponse
    {
        $this->authorize('approve', $transaction);

        $transaction->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Transaction approved successfully',
            'data' => new TransactionResource($transaction->fresh(['category', 'creator', 'approver']))
        ]);
    }

    public function analytics(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $transactions = Transaction::byDateRange($dateFrom, $dateTo);

        $analytics = [
            'summary' => [
                'total_income' => $transactions->byType('income')->sum('amount'),
                'total_expenses' => $transactions->byType('expense')->sum('amount'),
                'transaction_count' => $transactions->count(),
            ],
            'by_category' => $transactions->with('category')
                ->get()
                ->groupBy('category.name')
                ->map->sum('amount'),
            'by_type' => $transactions->get()
                ->groupBy('type')
                ->map->sum('amount'),
            'by_status' => $transactions->get()
                ->groupBy('status')
                ->map->count(),
        ];

        return response()->json($analytics);
    }
}