<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            $table->char('isbn_13', 13)->nullable();
            $table->char('isbn_10', 10)->nullable();
            $table->string('title', 500);
            $table->string('subtitle', 500)->nullable();
            $table->string('edition_statement', 120)->nullable();
            $table->foreignId('publisher_id')->nullable()->constrained('publishers')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->smallInteger('published_year')->nullable();
            $table->string('language', 5)->default('en');
            $table->integer('page_count')->nullable();
            $table->string('format', 24)->default('paperback');
            $table->smallInteger('height_mm')->nullable();
            $table->smallInteger('width_mm')->nullable();
            $table->smallInteger('depth_mm')->nullable();
            $table->text('summary')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_source', 32)->nullable();
            $table->string('loan_type', 24)->default('general');
            $table->boolean('special_material')->default(false);
            $table->boolean('loan_restricted_default')->default(false);
            $table->text('internal_notes')->nullable();
            $table->string('metadata_source', 24)->default('manual');
            $table->timestamp('ai_classified_at')->nullable();
            $table->string('ai_model', 64)->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['isbn_13', 'isbn_10']);
            $table->index('title');
        });

        Schema::create('edition_author', function (Blueprint $table) {
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('authors')->restrictOnDelete();
            $table->string('role', 16)->default('author');
            $table->smallInteger('position')->default(0);

            $table->primary(['edition_id', 'author_id', 'role']);
        });

        Schema::create('edition_tag', function (Blueprint $table) {
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();

            $table->primary(['edition_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edition_tag');
        Schema::dropIfExists('edition_author');
        Schema::dropIfExists('editions');
    }
};
