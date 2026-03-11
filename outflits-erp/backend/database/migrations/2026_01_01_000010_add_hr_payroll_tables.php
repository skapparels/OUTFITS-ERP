<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->decimal('basic_salary', 12, 2)->default(0)->after('role');
            $table->enum('employment_status', ['active', 'inactive', 'terminated'])->default('active')->index()->after('basic_salary');
        });

        Schema::create('shift_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('shift_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('shift_name')->nullable();
            $table->timestamps();
            $table->unique(['staff_id', 'shift_date']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date');
            $table->string('leave_type');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending')->index();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        Schema::create('overtime_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date')->index();
            $table->decimal('hours', 5, 2);
            $table->decimal('rate_per_hour', 10, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_entries');
        Schema::dropIfExists('staff_tasks');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('shift_schedules');

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['employee_code', 'basic_salary', 'employment_status']);
        });
    }
};

