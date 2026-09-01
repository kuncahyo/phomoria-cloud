<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {

            $table->id();

            $table->foreignId('photo_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('filename');

            $table->string('original_name');

            $table->boolean('is_result')
                ->default(false);

            $table->integer('sort_order');

            $table->unsignedBigInteger('file_size');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};