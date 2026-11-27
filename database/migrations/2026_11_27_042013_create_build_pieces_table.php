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
        Schema::create('build_pieces', function (Blueprint $table) {
            $table->foreignUlid('build_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('piece_id')->constrained(); // no cascade: a piece in use cannot vanish
            $table->unsignedInteger('count');

            $table->primary(['build_id', 'piece_id']);
            $table->index('piece_id');
        });

        DB::statement('ALTER TABLE build_pieces ADD CONSTRAINT build_pieces_count_check CHECK (count >= 1)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('build_pieces');
    }
};
