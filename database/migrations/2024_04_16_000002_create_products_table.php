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
        if (Schema::hasTable('products')) {
            return;
        }
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('name');
            $table->text('description');
            $table->double('price');
            $table->text('color');
            $table->text('size');
            $table->text('season');
            $table->text('picture');
            $table->text('seo_url');
            $table->double('retail_price')->default(0);
            $table->enum('alteration', ['Yes', 'No'])->default('No');
            $table->string('condition', 30)->default('Like New');
            $table->text('designer');
            $table->text('cancellation');
            $table->double('cleaning_price')->nullable();
            $table->integer('is_deleted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
