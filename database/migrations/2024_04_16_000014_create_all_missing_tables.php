<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // blog
        if (!Schema::hasTable('blog')) {
            Schema::create('blog', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->text('title');
                $table->text('description');
                $table->text('picture')->nullable();
                $table->text('picture_custom_size')->nullable();
                $table->text('seo_url');
                $table->timestamps();
            });
        }

        // blog_categories
        if (!Schema::hasTable('blog_categories')) {
            Schema::create('blog_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('blog_id');
                $table->unsignedInteger('category_id');
                $table->timestamps();
            });
        }

        // cleaner
        if (!Schema::hasTable('cleaner')) {
            Schema::create('cleaner', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 255)->default('');
                $table->string('shop_name', 255)->default('');
                $table->text('location');
                $table->double('latitude')->default(0);
                $table->double('longitude')->default(0);
                $table->string('mobile_number', 20)->default('');
                $table->timestamps();
            });
        }

        // countries
        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->string('Code', 3)->default('');
                $table->string('Name', 52)->default('');
                $table->enum('Continent', ['Asia','Europe','North America','Africa','Oceania','Antarctica','South America'])->default('Asia');
                $table->string('Region', 26)->default('');
                $table->float('SurfaceArea', 10, 2)->default(0);
                $table->smallInteger('IndepYear')->nullable();
                $table->integer('Population')->default(0);
                $table->float('LifeExpectancy', 3, 1)->nullable();
                $table->float('GNP', 10, 2)->nullable();
                $table->float('GNPOld', 10, 2)->nullable();
                $table->string('LocalName', 45)->default('');
                $table->string('GovernmentForm', 45)->default('');
                $table->string('HeadOfState', 60)->nullable();
                $table->integer('Capital')->nullable();
                $table->string('Code2', 2)->default('');
            });
        }

        // dropzone
        if (!Schema::hasTable('dropzone')) {
            Schema::create('dropzone', function (Blueprint $table) {
                $table->increments('id');
                $table->text('ip');
                $table->text('label_name');
                $table->text('file');
                $table->text('size');
                $table->integer('rotate')->nullable();
                $table->timestamps();
            });
        }

        // faqs
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('section', 255)->nullable();
                $table->string('category', 255)->nullable();
                $table->string('question', 255)->default('');
                $table->text('answer');
                $table->timestamps();
            });
        }

        // messages
        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('room_id');
                $table->unsignedInteger('from_user_id')->comment('sender');
                $table->unsignedInteger('to_user_id')->comment('reciever');
                $table->text('content');
                $table->integer('status')->comment('0 = unread | 1 = read');
                $table->timestamps();
            });
        }

        // messages_room
        if (!Schema::hasTable('messages_room')) {
            Schema::create('messages_room', function (Blueprint $table) {
                $table->increments('id');
                $table->text('md5_id');
                $table->unsignedInteger('creator_id');
                $table->timestamps();
            });
        }

        // news_latter
        if (!Schema::hasTable('news_latter')) {
            Schema::create('news_latter', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 255);
                $table->string('email', 255);
                $table->timestamps();
            });
        }

        // notification
        if (!Schema::hasTable('notification')) {
            Schema::create('notification', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('for_user');
                $table->unsignedInteger('from_user');
                $table->unsignedInteger('rent_id');
                $table->text('title');
                $table->text('message');
                $table->text('type');
                $table->text('response')->nullable();
                $table->tinyInteger('read')->nullable();
                $table->unsignedInteger('status')->comment('0 = unread | 1 = read');
                $table->timestamps();
            });
        }

        // pages
        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->increments('id');
                $table->text('name');
                $table->timestamps();
            });
        }

        // product_photos
        if (!Schema::hasTable('product_photos')) {
            Schema::create('product_photos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('product_id');
                $table->text('sub_photo');
                $table->tinyInteger('type')->default(0)->comment('0 = image');
                $table->integer('size')->default(0);
                $table->integer('rotate')->nullable();
                $table->timestamps();
            });
        }

        // rattings
        if (!Schema::hasTable('rattings')) {
            Schema::create('rattings', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->integer('product_id');
                $table->string('delivery_rat', 50);
                $table->string('time_rat', 50);
                $table->string('cleanliness_rat', 50);
                $table->string('accuracy_rat', 50);
                $table->string('communication_rat', 50);
                $table->string('quality_rat', 50);
                $table->string('condition_rat', 50);
                $table->string('overall_rat', 50);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // user_device_token
        if (!Schema::hasTable('user_device_token')) {
            Schema::create('user_device_token', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->default(0);
                $table->enum('device_type', ['Android','IOS','',''])->default('Android');
                $table->text('device_token');
                $table->timestamps();
            });
        }

        // wishlist
        if (!Schema::hasTable('wishlist')) {
            Schema::create('wishlist', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('product_id');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist');
        Schema::dropIfExists('user_device_token');
        Schema::dropIfExists('rattings');
        Schema::dropIfExists('product_photos');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('notification');
        Schema::dropIfExists('news_latter');
        Schema::dropIfExists('messages_room');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('dropzone');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('cleaner');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('blog');
    }
};
