<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('frames', 'user_id')) {
            Schema::table('frames', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            });
        }

        $ownerId = DB::table('users')
            ->where('email', 'owner@phomoria.com')
            ->value('id');

        if ($ownerId === null) {
            throw new RuntimeException(
                'User owner@phomoria.com tidak ditemukan.'
            );
        }

        DB::table('frames')
            ->whereNull('user_id')
            ->update([
                'user_id' => $ownerId,
            ]);

        Schema::table('frames', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('frames', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable(false)
                ->change()
                ->constrained('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('frames', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};