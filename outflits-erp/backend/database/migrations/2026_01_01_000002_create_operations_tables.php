<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreign('preferred_supplier_id')->references('id')->on('suppliers')->nullOnDelete();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->date('order_date');
            $table->enum('status', ['draft', 'approved', 'received', 'closed'])->default('draft')->index();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->timestamps();
        });

        Schema::create('store_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->unique(['store_id', 'product_variant_id']);
        });

        Schema::create('warehouse_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code')->index();
            $table->string('zone')->nullable();
            $table->string('rack')->nullable();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->index(['warehouse_code', 'zone', 'rack']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('membership_level')->default('base');
            $table->unsignedInteger('reward_points')->default(0);
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->enum('payment_method', ['cash', 'upi', 'card', 'mixed', 'reward_points']);
            $table->decimal('total_amount', 14, 2);
            $table->timestamps();
            $table->index(['store_id', 'created_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->restrictOnDelete();
            $table->enum('type', ['return', 'exchange']);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('store_credit_note')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->integer('points');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('name');
            $table->string('role');
            $table->date('joining_date');
            $table->timestamps();
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');
            $table->string('status');
            $table->timestamps();
            $table->unique(['staff_id', 'date']);
        });

        Schema::create('payroll', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('month');
            $table->decimal('gross_pay', 12, 2);
            $table->decimal('net_pay', 12, 2);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->timestamps();
        });

        Schema::create('credit_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('franchise_id')->nullable()->constrained('franchises')->nullOnDelete();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->decimal('outstanding', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_control_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('min_stock');
            $table->unsignedInteger('max_stock');
            $table->unsignedInteger('reorder_level');
            $table->unsignedInteger('lead_time_days');
            $table->timestamps();
            $table->unique('product_variant_id');
        });

        Schema::create('ai_inventory_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('sales_velocity', 10, 2);
            $table->unsignedInteger('suggested_reorder_qty');
            $table->unsignedInteger('suggested_min_stock');
            $table->unsignedInteger('suggested_max_stock');
            $table->enum('status', ['pending', 'approved', 'rejected', 'altered_approved'])->default('pending')->index();
            $table->unsignedInteger('approved_reorder_qty')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_inventory_recommendations');
        Schema::dropIfExists('inventory_control_settings');
        Schema::dropIfExists('credit_accounts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payroll');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('warehouse_inventory');
        Schema::dropIfExists('store_inventory');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
