<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_id', 128)->nullable()->index();
            $table->string('event_key', 191)->nullable()->unique();
            $table->boolean('signature_valid')->default(false);
            $table->string('processing_status', 32)->default('RECEIVED');
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};
