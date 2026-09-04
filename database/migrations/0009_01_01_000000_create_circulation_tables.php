<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_policies', function (Blueprint $table) {
            $table->id();
            $table->string('loan_type', 24)->unique();
            $table->integer('default_hours');
            $table->integer('min_hours');
            $table->integer('max_hours');
            $table->smallInteger('renewals_allowed')->default(0);
            $table->decimal('special_material_factor', 3, 2)->default(0.50);
            $table->integer('grace_hours')->default(24);
            $table->decimal('daily_fine_amount', 10, 2)->default(0);
            $table->smallInteger('max_active_loans_per_user')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('code', 16)->unique();
            $table->foreignId('copy_id')->constrained('copies')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('checked_out_by_id')->constrained('users');
            $table->foreignId('checked_in_by_id')->nullable()->constrained('users');
            $table->timestamp('checked_out_at');
            $table->timestamp('due_at');
            $table->timestamp('returned_at')->nullable();
            $table->smallInteger('renewals_count')->default(0);
            $table->jsonb('policy_snapshot');
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->string('fine_status', 16)->default('none');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'returned_at']);
        });

        DB::statement('CREATE INDEX loans_due_at_active_idx ON loans (due_at) WHERE returned_at IS NULL');
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX loans_one_active_per_copy
            ON loans (copy_id) WHERE returned_at IS NULL
        SQL);

        Schema::create('copy_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('copy_id')->constrained('copies')->cascadeOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['copy_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copy_status_transitions');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('loan_policies');
    }
};
