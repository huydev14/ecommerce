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
            $table->string('receiver_name');
            $table->string('receiver_phone', 20);

            $table->integer('province_id')->comment('id from GHN API');
            $table->integer('district_id')->comment('id from GHN API');
            $table->string('ward_code')->comment('code from GHN API');

            $table->string('province_name');
            $table->string('district_name');
            $table->string('ward_name');

            $table->string('specific_address');

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
