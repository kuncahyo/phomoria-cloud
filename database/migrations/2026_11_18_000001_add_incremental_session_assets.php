<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photo_sessions', function (Blueprint $table) {
            $table->string('client_session_id', 100)->nullable()->unique()->after('session_code');
            $table->json('expected_assets')->nullable()->after('photo_count');
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->string('asset_key', 100)->nullable()->after('photo_session_id');
            $table->string('asset_type', 30)->default('photo')->after('asset_key');
            $table->unique(['photo_session_id', 'asset_key'], 'photos_session_asset_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropUnique('photos_session_asset_key_unique');
            $table->dropColumn(['asset_key', 'asset_type']);
        });

        Schema::table('photo_sessions', function (Blueprint $table) {
            $table->dropUnique(['client_session_id']);
            $table->dropColumn(['client_session_id', 'expected_assets']);
        });
    }
};
