<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('race')->nullable();
            $table->string('class')->nullable();
            $table->integer('level')->default(1);
            $table->integer('max_hp')->nullable();
            $table->integer('current_hp')->nullable();
            $table->integer('armor_class')->nullable();
            $table->integer('strength')->nullable();
            $table->integer('dexterity')->nullable();
            $table->integer('constitution')->nullable();
            $table->integer('intelligence')->nullable();
            $table->integer('wisdom')->nullable();
            $table->integer('charisma')->nullable();
            $table->json('extra_data')->nullable();
            $table->text('backstory')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'campaign_id', 'name']);
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
