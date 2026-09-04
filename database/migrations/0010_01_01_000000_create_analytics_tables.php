<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demand_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->foreignId('edition_id')->nullable()->constrained('editions')->cascadeOnDelete();
            $table->char('isbn', 13)->nullable();
            $table->string('query_text', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['type', 'created_at']);
        });

        Schema::create('metadata_lookups', function (Blueprint $table) {
            $table->id();
            $table->char('isbn_13', 13);
            $table->string('provider', 32);
            $table->string('status', 16);
            $table->jsonb('payload')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamp('expires_at')->nullable();

            $table->unique(['isbn_13', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata_lookups');
        Schema::dropIfExists('demand_events');
    }
};
