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
        if (Schema::hasColumn('users', 'username')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->text('username')->nullable()->after('name');
            $table->text('first_name')->nullable()->after('username');
            $table->text('last_name')->nullable()->after('first_name');
            $table->text('contact_number')->nullable()->after('last_name');
            $table->text('location')->nullable()->after('contact_number');
            $table->text('country')->nullable()->after('location');
            $table->double('longitude')->default(0)->after('country');
            $table->double('latitude')->default(0)->after('longitude');
            $table->date('birthday')->nullable()->after('latitude');
            $table->string('size', 50)->default('')->comment('0 = Extra Small | 1 = Small | 2 = Medium | 3 = Large | 4 = Extra Large')->after('birthday');
            $table->text('height')->nullable()->after('size');
            $table->text('breast')->nullable()->after('height');
            $table->text('waist')->nullable()->after('breast');
            $table->text('hips')->nullable()->after('waist');
            $table->tinyInteger('body_type')->default(1)->comment('1 - 5 image')->after('hips');
            $table->double('shipping_fee_local')->default(0)->after('body_type');
            $table->double('shipping_fee_nationwide')->default(0)->after('shipping_fee_local');
            $table->double('cleaning_price', 10, 2)->nullable()->after('shipping_fee_nationwide');
            $table->tinyInteger('privilege')->default(1)->comment('0 = admin | 1 = user')->after('cleaning_price');
            $table->tinyInteger('status')->default(0)->comment('0 = not verify | 1 = verified')->after('privilege');
            $table->text('profile_picture')->nullable()->after('status');
            $table->text('profile_picture_custom_size')->nullable()->after('profile_picture');
            $table->text('facebook_id')->nullable()->after('profile_picture_custom_size');
            $table->text('twitter_id')->nullable()->after('facebook_id');
            $table->text('crypted_password')->nullable()->after('password');
            $table->string('verification_code', 10)->default('')->after('remember_token');
            $table->string('paypal_email_address')->default('')->after('verification_code');
            $table->integer('verify_paypal_email')->default(0)->after('paypal_email_address');
            $table->string('api_token')->default('')->after('verify_paypal_email');
            $table->tinyInteger('is_deleted')->default(0)->after('api_token');
            $table->string('firebase_id')->default('')->after('is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'first_name', 'last_name', 'contact_number', 'location', 'country',
                'longitude', 'latitude', 'birthday', 'size', 'height', 'breast', 'waist', 'hips',
                'body_type', 'shipping_fee_local', 'shipping_fee_nationwide', 'cleaning_price',
                'privilege', 'status', 'profile_picture', 'profile_picture_custom_size',
                'facebook_id', 'twitter_id', 'crypted_password', 'verification_code',
                'paypal_email_address', 'verify_paypal_email', 'api_token', 'is_deleted', 'firebase_id'
            ]);
        });
    }
};
