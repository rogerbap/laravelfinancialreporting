<?php
// tests/Unit/TransactionTest.php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\{Transaction, Category, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_generates_reference_number()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['created_by' => $user->id]);
        
        $transaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'reference_number' => null
        ]);

        $this->assertNotNull($transaction->reference_number);
        $this->assertStringStartsWith('TXN', $transaction->reference_number);
    }

    public function test_transaction_belongs_to_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['created_by' => $user->id]);
        
        $transaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id
        ]);

        $this->assertInstanceOf(Category::class, $transaction->category);
        $this->assertEquals($category->id, $transaction->category->id);
    }

    public function test_transaction_status_methods()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['created_by' => $user->id]);
        
        $pendingTransaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'status' => 'pending'
        ]);

        $approvedTransaction = Transaction::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'status' => 'approved'
        ]);

        $this->assertTrue($pendingTransaction->isPending());
        $this->assertFalse($pendingTransaction->isApproved());
        
        $this->assertTrue($approvedTransaction->isApproved());
        $this->assertFalse($approvedTransaction->isPending());
    }

    public function test_transaction_scopes()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['created_by' => $user->id]);
        
        Transaction::factory()->count(3)->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'type' => 'income',
            'status' => 'approved'
        ]);

        Transaction::factory()->count(2)->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
            'type' => 'expense',
            'status' => 'pending'
        ]);

        $this->assertEquals(3, Transaction::byType('income')->count());
        $this->assertEquals(2, Transaction::byType('expense')->count());
        $this->assertEquals(3, Transaction::byStatus('approved')->count());
        $this->assertEquals(2, Transaction::byStatus('pending')->count());
    }
}