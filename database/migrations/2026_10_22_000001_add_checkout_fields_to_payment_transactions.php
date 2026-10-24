<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->text('checkout_url')->nullable()->after('qr_content');
            $table->string('doku_token_id', 128)->nullable()->after('checkout_url');
            $table->string('provider_request_id', 128)->nullable()->after('provider_reference');
            $table->unique(['user_id', 'device_id', 'client_session_id'], 'payment_tx_client_session_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropUnique('payment_tx_client_session_unique');
            $table->dropColumn(['checkout_url', 'doku_token_id', 'provider_request_id']);
        });
    }
};
