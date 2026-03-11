<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_vip')->default(false)->after('membership_level')->index();
            $table->date('date_of_birth')->nullable()->after('is_vip');
            $table->decimal('lifetime_value', 14, 2)->default(0)->after('reward_points');
            $table->timestamp('last_visit_at')->nullable()->after('lifetime_value');
            $table->json('preferences')->nullable()->after('last_visit_at');
        });

        Schema::create('customer_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('channel')->default('store')->index();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('customer_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('source')->default('manual');
            $table->decimal('score', 5, 2)->default(0);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'shown', 'accepted', 'rejected'])->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_recommendations');
        Schema::dropIfExists('customer_visits');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['is_vip', 'date_of_birth', 'lifetime_value', 'last_visit_at', 'preferences']);
        });
    }
};

