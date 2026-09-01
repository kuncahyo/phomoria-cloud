<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

            $table->uuid('device_uuid')->unique();

            $table->string('computer_name');

            $table->string('windows_user');

            $table->string('operating_system');

            $table->string('java_version');

            $table->string('app_version');

            $table->enum(
                'status',
                [
                    'ACTIVE',
                    'DISABLED'
                ]
            )->default('ACTIVE');

            $table->timestamp('last_online')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
