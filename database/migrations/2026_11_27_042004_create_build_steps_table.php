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
        Schema::create('build_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('build_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('title', 100);
            $table->text('note')->nullable();

            $table->unique(['build_id', 'position']);
        });

        DB::statement('ALTER TABLE build_steps ADD CONSTRAINT build_steps_position_check CHECK (position >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('build_steps');
    }
};
