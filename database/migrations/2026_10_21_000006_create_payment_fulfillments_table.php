<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('PENDING');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_fulfillments');
    }
};
