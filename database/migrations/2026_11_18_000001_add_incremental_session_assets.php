<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // client_session_id already exists on this installation as CHAR(36),
        // with a unique composite index on (device_id, client_session_id).
        // Preserve that existing UUID column and index.
        if (!Schema::hasColumn('photo_sessions', 'expected_assets')) {
            Schema::table('photo_sessions', function (Blueprint $table) {
                $table->json('expected_assets')->nullable()->after('photo_count');
            });
        }

        if (!Schema::hasColumn('photos', 'asset_key')) {
            Schema::table('photos', function (Blueprint $table) {
                $table->string('asset_key', 100)->nullable()->after('photo_session_id');
            });
        }

        if (!Schema::hasColumn('photos', 'asset_type')) {
            Schema::table('photos', function (Blueprint $table) {
                $table->string('asset_type', 30)->default('photo')->after('asset_key');
            });
        }

        $indexes = collect(DB::select('SHOW INDEX FROM photos'))
            ->pluck('Key_name')
            ->unique();

        if (!$indexes->contains('photos_session_asset_key_unique')) {
            Schema::table('photos', function (Blueprint $table) {
                $table->unique(
                    ['photo_session_id', 'asset_key'],
                    'photos_session_asset_key_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // Only remove structures introduced by this migration. Never remove
        // the pre-existing client_session_id column or its device-scoped index.
        $indexes = collect(DB::select('SHOW INDEX FROM photos'))
            ->pluck('Key_name')
            ->unique();

        if ($indexes->contains('photos_session_asset_key_unique')) {
            Schema::table('photos', function (Blueprint $table) {
                $table->dropUnique('photos_session_asset_key_unique');
            });
        }

        $columnsToDrop = [];
        if (Schema::hasColumn('photos', 'asset_key')) {
            $columnsToDrop[] = 'asset_key';
        }
        if (Schema::hasColumn('photos', 'asset_type')) {
            $columnsToDrop[] = 'asset_type';
        }
        if ($columnsToDrop) {
            Schema::table('photos', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        if (Schema::hasColumn('photo_sessions', 'expected_assets')) {
            Schema::table('photo_sessions', function (Blueprint $table) {
                $table->dropColumn('expected_assets');
            });
        }
    }
};
