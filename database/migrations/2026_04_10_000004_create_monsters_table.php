<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monsters', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->string('size');
            $table->string('type');
            $table->string('subtype')->nullable();
            $table->string('alignment')->nullable();
            $table->json('desc')->nullable();
            $table->string('challenge_rating');
            $table->decimal('cr', 5, 2)->nullable();
            $table->integer('armor_class');
            $table->integer('hit_points');
            $table->string('hit_dice')->nullable();
            $table->json('traits')->nullable();
            $table->json('actions')->nullable();
            $table->json('legendary_actions')->nullable();
            $table->json('reactions')->nullable();
            $table->json('bonus_actions')->nullable();
            $table->json('special_abilities')->nullable();
            $table->json('speed')->nullable();
            $table->integer('strength')->nullable();
            $table->integer('dexterity')->nullable();
            $table->integer('constitution')->nullable();
            $table->integer('intelligence')->nullable();
            $table->integer('wisdom')->nullable();
            $table->integer('charisma')->nullable();
            $table->json('saving_throws')->nullable();
            $table->string('armor_detail')->nullable();
            $table->string('document_slug');
            $table->string('document_title')->nullable();
            $table->string('img_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('challenge_rating');
            $table->index('document_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monsters');
    }
};
