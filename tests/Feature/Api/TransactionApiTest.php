<?php
// tests/Feature/Api/TransactionApiTest.php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\{User, Transaction, Category};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['created_by' => $this->user->id]);
        
        // Setup permissions
        $this->setupPermissions();
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_fetch_transactions_via_api()
    {
        Transaction::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'reference_number',
                        'description',
                        'amount',
                        'type',
                        'status',
                        'category',
                        'creator'
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    public function test_can_create_transaction_via_api()
    {
        $transactionData = [
            'description' => 'API Test Transaction',
            'amount' => 150.75,
            'type' => 'expense',
            'transaction_date' => now()->format('Y-m-d'),
            'category_id' => $this->category->id,
        ];

        $response = $this->postJson('/api/transactions', $transactionData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Transaction created successfully'
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'reference_number',
                    'description',
                    'amount'
                ]
            ]);

        $this->assertDatabaseHas('transactions', [
            'description' => 'API Test Transaction',
            'amount' => 150.75
        ]);
    }

    public function test_can_approve_transaction_via_api()
    {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'category_id' => $this->category->id,
            'created_by' => $otherUser->id,
            'status' => 'pending'
        ]);

        $response = $this->patchJson("/api/transactions/{$transaction->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transaction approved successfully'
            ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'approved',
            'approved_by' => $this->user->id
        ]);
    }

    public function test_api_analytics_endpoint()
    {
        Transaction::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'type' => 'income',
            'status' => 'approved',
            'amount' => 1000
        ]);

        Transaction::factory()->count(2)->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
            'type' => 'expense',
            'status' => 'approved',
            'amount' => 500
        ]);

        $response = $this->getJson('/api/transactions/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'total_income',
                    'total_expenses',
                    'transaction_count'
                ],
                'by_category',
                'by_type',
                'by_status'
            ]);

        $this->assertEquals(3000, $response->json('summary.total_income'));
        $this->assertEquals(1000, $response->json('summary.total_expenses'));
    }

    private function setupPermissions()
    {
        // Create and assign permissions
        $permissions = [
            'view transactions',
            'create transactions',
            'edit transactions',
            'delete transactions',
            'approve transactions'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::create(['name' => $permission]);
        }

        $this->user->givePermissionTo($permissions);
    }
}