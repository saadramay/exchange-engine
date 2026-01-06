<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 10);
            $table->string('side', 10);
            $table->decimal('price', 18, 8);
            $table->decimal('amount', 18, 8);
            $table->string('status', 20)->default('OPEN');

            /**
             * Locking:
             * - BUY: we deduct from users.balance and store locked USD here.
             * - SELL: we move amount to assets.locked_amount; for SELL this will stay 0.
             */
            $table->decimal('locked_usd', 18, 8)->default(0);
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['symbol', 'status']);
            $table->index(['symbol', 'side', 'status', 'price', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
