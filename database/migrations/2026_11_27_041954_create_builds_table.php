<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('builds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100);
            $table->string('visibility', 20)->default('private');
            $table->jsonb('data');
            $table->unsignedInteger('block_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // "My builds": newest first, id as the tie-breaker
            $table->index(['user_id', 'created_at', 'id']);
        });

        DB::statement("ALTER TABLE builds ADD CONSTRAINT builds_visibility_check CHECK (visibility IN ('private', 'unlisted', 'public'))");
        DB::statement('ALTER TABLE builds ADD CONSTRAINT builds_block_count_check CHECK (block_count >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('builds');
    }
};
