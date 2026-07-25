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
            $table->string('order_number', 50)->nullable()->unique();

            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name', 255);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 255)->nullable();
            $table->text('shipping_address');
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_fee', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2);

            $table->string('status', 30)->default('pending')->comment('pending,processing, shipping, completed, cancelled');

            $table->string('payment_method', 50)->default('cod')->comment('cod, bank_transfer');
            $table->string('payment_status', 30)->default('unpaid')->comment('unpaid, paid');
            $table->timestamps();
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
