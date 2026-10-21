<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frames', function (Blueprint $table) {
            $table->string('printer_profile', 20)->default('PROFILE_1')->after('category');
        });

        Schema::table('frame_placements', function (Blueprint $table) {
            // Nullable for legacy frames. New editor-created placements must set this.
            $table->unsignedInteger('source_slot')->nullable()->after('slot');
        });
    }

    public function down(): void
    {
        Schema::table('frame_placements', function (Blueprint $table) {
            $table->dropColumn('source_slot');
        });

        Schema::table('frames', function (Blueprint $table) {
            $table->dropColumn('printer_profile');
        });
    }
};
