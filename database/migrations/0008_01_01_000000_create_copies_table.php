<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copies', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('code', 16)->unique();
            $table->foreignId('edition_id')->constrained('editions')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('status', 24)->default('available');
            $table->string('condition', 16)->default('good');
            $table->boolean('loan_restricted')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 10, 2)->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['edition_id', 'status']);
            $table->index(['status', 'status_changed_at']);
            $table->index('location_id');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE copies ADD CONSTRAINT copies_status_check
            CHECK (status IN ('available', 'on_loan', 'reserved', 'in_repair', 'lost', 'at_reception', 'in_transit'))
        SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE copies ADD CONSTRAINT copies_condition_check
            CHECK (condition IN ('new', 'good', 'fair', 'poor'))
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('copies');
    }
};
