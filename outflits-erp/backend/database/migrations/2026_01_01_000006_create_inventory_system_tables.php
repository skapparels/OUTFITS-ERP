<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->enum('location_type', ['store', 'warehouse'])->index();
            $table->string('location_ref')->index();
            $table->enum('movement_type', ['in', 'out', 'adjustment_in', 'adjustment_out', 'transfer_in', 'transfer_out', 'sale', 'return']);
            $table->integer('quantity_change');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['product_variant_id', 'created_at']);
        });

        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->enum('from_type', ['store', 'warehouse']);
            $table->string('from_ref');
            $table->enum('to_type', ['store', 'warehouse']);
            $table->string('to_ref');
            $table->unsignedInteger('quantity');
            $table->enum('status', ['completed', 'failed'])->default('completed')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
        Schema::dropIfExists('stock_movements');
    }
};
