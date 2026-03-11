<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('styles', function (Blueprint $table) {
            $table->string('style_code')->nullable()->unique()->after('collection_id');
            $table->timestamp('status_changed_at')->nullable()->after('status');
            $table->timestamp('clearance_at')->nullable()->after('status_changed_at');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('barcode')->nullable()->unique()->after('sku');
            $table->boolean('is_active')->default(true)->after('mrp');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'is_active']);
        });

        Schema::table('styles', function (Blueprint $table) {
            $table->dropColumn(['style_code', 'status_changed_at', 'clearance_at']);
        });
    }
};
