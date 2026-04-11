<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// database/migrations/xxxx_create_sessions_table.php

    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('session_number');
            $table->string('title')->nullable();
            $table->date('planned_at');
            $table->date('played_at')->nullable();

            $table->text('summary')->nullable();
            $table->text('gm_notes')->nullable();

            $table->string('in_game_date')->nullable();
            $table->string('location')->nullable();

            $table->enum('status', ['planned', 'played', 'skipped'])->default('planned');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
