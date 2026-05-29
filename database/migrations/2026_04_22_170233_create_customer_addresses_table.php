<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            $table->string('receiver_name', 255);
            $table->string('receiver_phone', 20);

            $table->string('specific_address', 255);

            $table->string('ward_code', 30);
            $table->string('ward_name', 100);

            $table->integer('district_id');
            $table->string('district_name', 100);

            $table->integer('province_id');
            $table->string('province_name', 100);

            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
