<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rent_details')) {
            Schema::create('rent_details', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('product_id');
                $table->text('delivery_option')->nullable();
                $table->text('return_delivery_option')->nullable();
                $table->timestamp('return_date')->nullable();
                $table->text('rental_start_date')->nullable();
                $table->string('shipping_info', 255)->nullable();
                $table->string('return_shipping_info', 255)->nullable();
                $table->text('rental_end_date')->nullable();
                $table->text('street_number')->nullable();
                $table->text('route')->nullable();
                $table->text('address2')->nullable();
                $table->text('address3')->nullable();
                $table->text('city')->nullable();
                $table->text('state')->nullable();
                $table->text('postal_code')->nullable();
                $table->text('country')->nullable();
                $table->text('contact_number')->nullable();
                $table->text('email')->nullable();
                $table->text('description')->nullable();
                $table->text('status')->nullable();
                $table->string('reason', 255)->nullable();
                $table->string('pay_key', 255)->default('');
                $table->double('cart_total', 8, 2)->default(0);
                $table->double('total', 8, 2)->default(0);
                $table->string('rating', 255)->nullable();
                $table->integer('user_review_submitted')->default(0);
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('rent_details_transaction_detail')) {
            Schema::create('rent_details_transaction_detail', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('rented_detail_id');
                $table->integer('user_id');
                $table->double('total_amount', 8, 2)->nullable();
                $table->text('detail')->nullable();
                $table->integer('product_id')->nullable();
                $table->text('payment_info')->nullable();
                $table->string('pay_key', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_details_transaction_detail');
        Schema::dropIfExists('rent_details');
    }
};
