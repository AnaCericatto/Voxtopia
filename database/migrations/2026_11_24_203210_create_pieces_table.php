<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('name');
            $table->string('visibility');
            $table->boolean('is_official')->default(false);
            $table->unsignedSmallInteger('size_w')->default(1);
            $table->unsignedSmallInteger('size_h')->default(1);
            $table->unsignedSmallInteger('size_d')->default(1);
            $table->string('base_color', 7);
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE pieces ADD CONSTRAINT pieces_kind_check CHECK (kind IN ('cube', 'shape', 'decor'))");
        DB::statement("ALTER TABLE pieces ADD CONSTRAINT pieces_visibility_check CHECK (visibility IN ('private', 'public'))");
        DB::statement("ALTER TABLE pieces ADD CONSTRAINT pieces_base_color_check CHECK (base_color ~ '^#[0-9A-F]{6}$')");
        DB::statement('ALTER TABLE pieces ADD CONSTRAINT pieces_size_check CHECK (size_w >= 1 AND size_h >= 1 AND size_d >= 1)');
        DB::statement('CREATE UNIQUE INDEX pieces_official_name_unique ON pieces (name) WHERE is_official');
    }

    public function down(): void
    {
        Schema::dropIfExists('pieces');
    }
};
