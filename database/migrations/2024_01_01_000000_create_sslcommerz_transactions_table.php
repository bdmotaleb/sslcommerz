<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sslcommerz_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('tran_id')->unique()->index();
            $table->string('session_key')->nullable()->index();
            $table->string('val_id')->nullable();
            $table->string('bank_tran_id')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->decimal('store_amount', 12, 2)->nullable();
            $table->string('currency', 10)->default('BDT');
            $table->string('status', 20)->default('INITIATED')->index();
            $table->string('card_type')->nullable();
            $table->string('card_no')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_issuer')->nullable();
            $table->tinyInteger('risk_level')->default(0);
            $table->text('gateway_page_url')->nullable();
            $table->json('callback_payload')->nullable();
            $table->json('validation_payload')->nullable();
            $table->string('value_a')->nullable();
            $table->string('value_b')->nullable();
            $table->string('value_c')->nullable();
            $table->string('value_d')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sslcommerz_transactions');
    }
};
