<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Stored generated column: immutable wrapper f_unaccent + 'simple' config.
        DB::statement(<<<'SQL'
            ALTER TABLE editions
            ADD COLUMN search_vector tsvector
            GENERATED ALWAYS AS (
                to_tsvector(
                    'simple',
                    public.f_unaccent(
                        coalesce(title, '') || ' ' ||
                        coalesce(subtitle, '') || ' ' ||
                        coalesce(isbn_13, '') || ' ' ||
                        coalesce(isbn_10, '')
                    )
                )
            ) STORED
        SQL);

        DB::statement('CREATE INDEX editions_search_vector_idx ON editions USING GIN (search_vector)');
        DB::statement('CREATE UNIQUE INDEX editions_isbn13_active_idx ON editions (isbn_13) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS editions_isbn13_active_idx');
        DB::statement('DROP INDEX IF EXISTS editions_search_vector_idx');
        DB::statement('ALTER TABLE editions DROP COLUMN IF EXISTS search_vector');
    }
};
