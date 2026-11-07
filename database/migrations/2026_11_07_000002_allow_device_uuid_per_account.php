<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique('devices_device_uuid_unique');
            $table->unique(
                ['user_id', 'device_uuid'],
                'devices_user_id_device_uuid_unique'
            );
        });
    }

    public function down(): void
    {
        $hasDuplicateUuids = DB::table('devices')
            ->select('device_uuid')
            ->groupBy('device_uuid')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateUuids) {
            throw new RuntimeException(
                'Cannot restore the global device_uuid unique index while the same UUID is registered to multiple accounts.'
            );
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique('devices_user_id_device_uuid_unique');
            $table->unique('device_uuid', 'devices_device_uuid_unique');
        });
    }
};
