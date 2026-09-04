<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->string('name', 160);
            $table->string('email', 190)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('google_id', 64)->nullable()->unique();
            $table->string('avatar_url')->nullable();
            $table->string('status', 24)->default('pending_email');
            $table->string('member_code', 16)->unique();
            $table->string('document_type', 16)->nullable();
            $table->text('document_number')->nullable();
            $table->char('document_hash', 64)->nullable()->unique();
            $table->text('phone')->nullable();
            $table->timestamp('identity_verified_at')->nullable();
            $table->foreignId('identity_verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('blocked_until')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->string('locale', 5)->default('en');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
