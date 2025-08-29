<?php
// tests/Feature/TransactionControllerTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Transaction, Category};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['created_by' => $this->user->id]);
        
        // Create permissions
        Permission::create(['name' => 'view transactions']);
        Permission::create(['name' => 'create transactions']);
        Permission::create(['name' => 'edit transactions']);
        Permission::create(['name' => 'delete transactions']);
        Permission::create(['name' => 'approve transactions']);
        
        $this->user->givePermissionTo([
            'view transactions',
            'create transactions',
            'edit transactions',
            'delete transactions',
            'approve transactions'
        ]);
    }

    public function test_user_can_view_transactions_index()
    {
        Transaction::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index'));

        $response->assertStatus(200)
            ->assertViewIs('transactions.index')
            ->assertViewHas('transactions');
    }

    public function test_user_can_create_transaction()
    {
        $transactionData = [
            'description' => 'Test transaction',
            'amount' => 100.50,
            'type' => 'expense',
            'transaction_date' => now()->format('Y-m-d'),
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), $transactionData);

        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', [
            'description' => 'Test transaction',
            'amount' => 100.50,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_user_can_update_own_pending_transaction()
    {
        $transaction = Transaction::factory()->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'status' => 'pending'
        ]);

        $updateData = [
            'description' => 'Updated transaction',
            'amount' => 200.00,
            'type' => $transaction->type,
            'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('transactions.update', $transaction), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'description' => 'Updated transaction',
            'amount' => 200.00,
        ]);
    }

    public function test_user_cannot_update_approved_transaction_without_permission()
    {
        $transaction = Transaction::factory()->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'status' => 'approved'
        ]);

        $updateData = [
            'description' => 'Updated transaction',
            'amount' => 200.00,
            'type' => $transaction->type,
            'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('transactions.update', $transaction), $updateData);

        $response->assertStatus(403);
    }

    public function test_user_can_approve_others_transactions()
    {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'category_id' => $this->category->id,
            'created_by' => $otherUser->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('transactions.approve', $transaction));

        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'approved',
            'approved_by' => $this->user->id,
        ]);
    }

    public function test_transaction_validation_rules()
    {
        $invalidData = [
            'description' => '', // Required
            'amount' => -100, // Must be positive
            'type' => 'invalid_type', // Must be valid enum
            'transaction_date' => 'invalid_date', // Must be valid date
            'category_id' => 999, // Must exist
        ];

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), $invalidData);

        $response->assertSessionHasErrors([
            'description',
            'amount',
            'type',
            'transaction_date',
            'category_id'
        ]);
    }
}