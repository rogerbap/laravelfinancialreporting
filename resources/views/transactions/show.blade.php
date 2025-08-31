@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Transaction Details</h1>
        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Transactions
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Reference Number</h5>
                    <p>{{ $transaction->reference_number }}</p>
                    
                    <h5>Description</h5>
                    <p>{{ $transaction->description }}</p>
                    
                    <h5>Amount</h5>
                    <p class="{{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">
                        {{ $transaction->type === 'income' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                    </p>
                </div>
                <div class="col-md-6">
                    <h5>Date</h5>
                    <p>{{ $transaction->transaction_date->format('M d, Y') }}</p>
                    
                    <h5>Category</h5>
                    <p>{{ $transaction->category->name }}</p>
                    
                    <h5>Status</h5>
                    <p><span class="badge bg-success">{{ ucfirst($transaction->status) }}</span></p>
                    
                    <h5>Created By</h5>
                    <p>{{ $transaction->creator->name }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection