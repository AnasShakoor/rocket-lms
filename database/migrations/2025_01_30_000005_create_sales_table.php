<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('buyer_id');
                $table->unsignedInteger('webinar_id')->nullable();
                $table->unsignedInteger('bundle_id')->nullable();
                $table->string('order_number')->unique();
                $table->decimal('amount', 10, 2);
                $table->decimal('vat_amount', 10, 2)->default(0);
                $table->decimal('bnpl_fee', 10, 2)->default(0);
                $table->string('bnpl_provider')->nullable();
                $table->integer('installments')->default(1);
                $table->enum('payment_method', ['credit_card', 'bank_transfer', 'bnpl'])->default('credit_card');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
                $table->timestamp('purchased_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('payment_details')->nullable();
                $table->timestamps();

                $table->index(['buyer_id', 'status']);
                $table->index(['webinar_id', 'status']);
                $table->index(['bundle_id', 'status']);
                $table->index('status');
                $table->index('order_number');

                $table->foreign('buyer_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('webinar_id')->references('id')->on('webinars')->onDelete('set null');
                $table->foreign('bundle_id')->references('id')->on('bundles')->onDelete('set null');
            });
        }
    }


    public function down()
    {
        Schema::dropIfExists('sales');
    }
};
