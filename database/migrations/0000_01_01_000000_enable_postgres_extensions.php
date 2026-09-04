<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Generated columns require IMMUTABLE expressions; unaccent() is STABLE.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.f_unaccent(text)
            RETURNS text
            LANGUAGE sql
            IMMUTABLE
            PARALLEL SAFE
            AS 'SELECT public.unaccent(''public.unaccent'', $1)'
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS public.f_unaccent(text)');
    }
};
