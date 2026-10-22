<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 40)->default('doku');
            $table->string('name', 120);
            $table->string('environment', 20)->default('sandbox');
            $table->string('client_id', 128);
            $table->text('client_secret');
            $table->text('merchant_private_key')->nullable();
            $table->text('doku_public_key')->nullable();
            $table->text('merchant_public_key')->nullable();
            $table->string('merchant_id', 64)->nullable();
            $table->string('terminal_id', 32)->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->string('channel_id', 16)->default('H2H');
            $table->string('currency', 3)->default('IDR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_accounts');
    }
};
