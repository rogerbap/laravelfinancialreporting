<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['monthly', 'quarterly', 'annual', 'custom']);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->date('period_start');
            $table->date('period_end');
            $table->json('filters')->nullable(); // Store filter criteria
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'status', 'created_at']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
};