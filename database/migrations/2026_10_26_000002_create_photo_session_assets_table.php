<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_session_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_session_id')->constrained()->cascadeOnDelete();
            $table->string('asset_key', 100);
            $table->string('asset_type', 30);
            $table->string('filename');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('expected_size')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->enum('status', ['PENDING', 'UPLOADED'])->default('PENDING');
            $table->timestamps();
            $table->unique(['photo_session_id', 'asset_key'], 'session_assets_session_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_session_assets');
    }
};
