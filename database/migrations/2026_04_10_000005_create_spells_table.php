<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spells', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->integer('level_int');
            $table->string('school');
            $table->string('casting_time');
            $table->string('range');
            $table->string('duration');
            $table->boolean('requires_concentration')->default(false);
            $table->boolean('can_be_cast_as_ritual')->default(false);
            $table->string('components')->nullable();
            $table->json('desc');
            $table->json('higher_level')->nullable();
            $table->string('dnd_class')->nullable();
            $table->string('document_slug');
            $table->string('document_title')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('level_int');
            $table->index('school');
            $table->index('document_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spells');
    }
};
