<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bnpl_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Provider name: Tabby, MICB, etc.'); // Tamara commented out
            $table->string('logo_path')->nullable()->comment('Path to provider logo');
            $table->decimal('fee_percentage', 5, 2)->default(0.00)->comment('Percentage fee on top of price + VAT');
            $table->integer('installment_count')->default(4)->comment('Number of installments');
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable()->comment('Provider-specific configuration');
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bnpl_providers');
    }
};

