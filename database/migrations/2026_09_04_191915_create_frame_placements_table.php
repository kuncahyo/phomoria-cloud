<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frame_placements', function (Blueprint $table) {

            $table->id();

            $table->foreignId('frame_id')
                ->constrained('frames')
                ->cascadeOnDelete();

            $table->unsignedInteger('slot');

            $table->integer('x');

            $table->integer('y');

            $table->unsignedInteger('width');

            $table->unsignedInteger('height');

            $table->decimal('rotation', 8, 2)->default(0);

            $table->timestamps();

            $table->unique([
                'frame_id',
                'slot'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frame_placements');
    }
};