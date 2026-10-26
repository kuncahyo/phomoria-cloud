<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photo_sessions', function (Blueprint $table) {
            // Nullable keeps sessions uploaded by older desktop versions compatible.
            $table->uuid('client_session_id')->nullable()->after('device_id');
            $table->unique(
                ['device_id', 'client_session_id'],
                'photo_sessions_device_client_session_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('photo_sessions', function (Blueprint $table) {
            $table->dropUnique('photo_sessions_device_client_session_unique');
            $table->dropColumn('client_session_id');
        });
    }
};
