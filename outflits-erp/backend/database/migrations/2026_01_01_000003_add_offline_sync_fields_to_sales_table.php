<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('offline_reference')->nullable()->unique()->after('id');
            $table->timestamp('sold_at')->nullable()->after('total_amount');
            $table->boolean('is_offline_sale')->default(false)->after('sold_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['offline_reference', 'sold_at', 'is_offline_sale']);
        });
    }
};
