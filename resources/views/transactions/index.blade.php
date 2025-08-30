@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Transactions</h1>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                            <td>{{ $transaction->description }}</td>
                            <td>{{ $transaction->category->name }}</td>
                            <td class="{{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->type === 'income' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                            </td>
                            <td>{{ ucfirst($transaction->status) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">No transactions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection