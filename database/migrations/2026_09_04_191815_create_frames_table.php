<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frames', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->string('category')->nullable();

            $table->string('image_path');

            $table->unsignedInteger('version')->default(1);

            $table->string('sha256', 64)->nullable();

            $table->unsignedInteger('width')->nullable();

            $table->unsignedInteger('height')->nullable();

            $table->enum(
                'status',
                [
                    'ACTIVE',
                    'DISABLED'
                ]
            )->default('ACTIVE');

            $table->timestamps();

            $table->index('category');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frames');
    }
};