<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('order_number')->nullable()->unique()->after('id');
            $table->date('expected_delivery_date')->nullable()->after('order_date');
            $table->enum('destination_type', ['store', 'warehouse'])->default('warehouse')->after('status');
            $table->string('destination_ref')->default('MAIN')->after('destination_type');
            $table->timestamp('approved_at')->nullable()->after('total_amount');
            $table->timestamp('received_at')->nullable()->after('approved_at');
            $table->text('notes')->nullable()->after('received_at');
            $table->index(['destination_type', 'destination_ref']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedInteger('received_quantity')->default(0)->after('quantity');
            $table->unsignedInteger('returned_quantity')->default(0)->after('received_quantity');
        });

        Schema::create('supplier_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('supplier_cost', 12, 2);
            $table->unsignedInteger('supplier_lead_time_days')->default(0);
            $table->unsignedInteger('minimum_order_quantity')->default(1);
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            $table->unique(['supplier_id', 'product_variant_id']);
        });

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('return_number')->unique();
            $table->date('return_date');
            $table->enum('status', ['draft', 'processed'])->default('draft')->index();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('supplier_product_mappings');

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['received_quantity', 'returned_quantity']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['destination_type', 'destination_ref']);
            $table->dropColumn([
                'order_number',
                'expected_delivery_date',
                'destination_type',
                'destination_ref',
                'approved_at',
                'received_at',
                'notes'
            ]);
        });
    }
};

