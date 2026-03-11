<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('period_days')->default(30);
            $table->decimal('forecast_qty', 12, 2);
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['product_variant_id', 'created_at']);
        });

        Schema::create('ai_size_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->unsignedInteger('recommended_total_qty')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'altered_approved'])->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'store_id', 'created_at']);
        });

        Schema::create('ai_size_allocation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_size_allocation_id')->constrained('ai_size_allocations')->cascadeOnDelete();
            $table->string('size', 40);
            $table->decimal('demand_percentage', 6, 2);
            $table->unsignedInteger('recommended_qty');
            $table->unsignedInteger('approved_qty')->nullable();
            $table->timestamps();
            $table->unique(['ai_size_allocation_id', 'size']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_size_allocation_items');
        Schema::dropIfExists('ai_size_allocations');
        Schema::dropIfExists('ai_demand_forecasts');
    }
};

