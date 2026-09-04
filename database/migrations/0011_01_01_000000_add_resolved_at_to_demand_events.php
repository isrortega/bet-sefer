<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_events', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('demand_events', function (Blueprint $table) {
            $table->dropColumn('resolved_at');
        });
    }
};
