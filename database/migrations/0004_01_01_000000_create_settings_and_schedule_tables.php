<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->jsonb('value');
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            $table->boolean('is_recurring')->default(false);
        });

        // weekday: 0 = Monday ... 6 = Sunday. null opens/closes + is_closed => closed.
        Schema::create('library_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday')->unique();
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_hours');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('settings');
    }
};
