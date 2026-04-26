<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('monster_spell');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('monster_spell', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spell_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['monster_id', 'spell_id']);
        });
    }
};
