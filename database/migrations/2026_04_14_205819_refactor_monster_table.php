<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monsters', function (Blueprint $table) {

            // Supprimer les colonnes Open5e
            $table->dropIndex(['type']);
            $table->dropIndex(['document_slug']);
            $table->dropColumn([
                'slug',
                'subtype',
                'special_abilities',
                'armor_detail',
                'document_slug',
                'document_title',
                'img_url',
                'last_synced_at',
            ]);

            // Ajouter fingerprint
            $table->string('fingerprint', 32)->nullable()->unique()->after('id');

            // Nouveaux index
            $table->index('type');
            $table->index('size');
            $table->index('alignment');
            $table->index('cr');
        });
    }

    public function down(): void
    {
        Schema::table('monsters', function (Blueprint $table) {

            $table->dropIndex(['type']);
            $table->dropIndex(['size']);
            $table->dropIndex(['alignment']);
            $table->dropIndex(['cr']);
            $table->dropColumn('fingerprint');

            $table->string('slug')->unique();
            $table->string('subtype')->nullable();
            $table->json('special_abilities')->nullable();
            $table->string('armor_detail')->nullable();
            $table->string('document_slug');
            $table->string('document_title')->nullable();
            $table->string('img_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->index('document_slug');
        });
    }
};
