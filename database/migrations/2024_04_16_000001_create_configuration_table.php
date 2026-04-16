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
        Schema::create('configuration', function (Blueprint $table) {
            $table->id();
            $table->text('logo');
            $table->text('logo_footer');
            $table->text('name');
            $table->text('email');
            $table->text('copyright');
            $table->text('phone_number');
            $table->text('location');
            $table->double('commision')->default(0);
            $table->text('social_media_links');
            $table->text('paypal_account');
            $table->string('paypal_client_id')->nullable();
            $table->string('paypal_client_secret')->nullable();
            $table->string('paypal_live_client_id')->nullable();
            $table->string('paypal_live_client_secret')->nullable();
            $table->string('paypal_mode')->nullable();
            $table->timestamps();
        });

        // Insert default configuration
        DB::table('configuration')->insert([
            'id' => 1,
            'logo' => 'uploads/others/1496992927__logo.png',
            'logo_footer' => 'uploads/others/1496992927__footer-logo.png',
            'name' => 'Rent a Suit',
            'email' => 'info@rentasuit.ca',
            'copyright' => 'Rent a Suit. All Rights Reserved.',
            'phone_number' => '1 (833) 311 7368 or 1 (833) RENT',
            'location' => '123 connecticut st.',
            'commision' => 1.6,
            'social_media_links' => 'a:3:{s:8:"facebook";s:42:"https://www.facebook.com/rentasuitclothes/";s:9:"instagram";s:43:"https://www.instagram.com/rentasuitclothes/";s:7:"twitter";s:42:"https://www.pinterest.ca/rentasuitclothes/";}',
            'paypal_account' => 'a:7:{s:20:"paypal_test_username";N;s:20:"paypal_test_password";N;s:21:"paypal_test_signature";N;s:20:"paypal_live_username";N;s:20:"paypal_live_password";N;s:21:"paypal_live_signature";N;s:11:"paypal_mode";s:7:"sandbox";}',
            'paypal_client_id' => 'AeyCFjyOYWsn-8Y7DQQ98cvv2KIpYAYRZo6MqWWv_Vrqr5_nSIz7-Xk5K3Lj4cu58VvXH1fO821QYlCC',
            'paypal_client_secret' => 'EJ_HfSAS6d2S1JJxEo_he09SzzDQGuQ2DpJ-BSUSfHKo5mVLRYhmLlzK9HMPPbcFxuiCSt049imkapMH',
            'paypal_live_client_id' => null,
            'paypal_live_client_secret' => null,
            'paypal_mode' => 'sandbox',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuration');
    }
};
