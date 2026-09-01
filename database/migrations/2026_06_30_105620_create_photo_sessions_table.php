<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_sessions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('device_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('session_code')->unique();

            $table->string('frame_name');

            $table->integer('photo_count');

            $table->enum(
                'status',
                [
                    'UPLOADING',
                    'COMPLETED',
                    'FAILED'
                ]
            )->default('UPLOADING');

            $table->timestamp('expired_at')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_sessions');
    }
};