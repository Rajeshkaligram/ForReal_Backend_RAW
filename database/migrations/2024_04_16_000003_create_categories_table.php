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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->tinyInteger('status')->default(0)->comment('0 = blog | 1 = product');
            $table->text('picture')->nullable();
            $table->double('shipping_fee_local')->nullable();
            $table->double('shipping_fee_nationwide')->nullable();
            $table->text('seo_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
