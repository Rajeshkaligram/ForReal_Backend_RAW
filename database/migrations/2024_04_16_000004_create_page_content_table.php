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
        Schema::create('page_content', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('section_id');
            $table->text('field_type')->comment('text, textarea, image, file, wysiwyg_basic, wysiwyg_full, repeater');
            $table->text('field_name');
            $table->text('repeater_fields')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
        });

        // Insert default page content for home page
        DB::table('page_content')->insert([
            // SEO Section (section_id=1)
            ['page_id' => 1, 'section_id' => 1, 'field_type' => 'text', 'field_name' => 'Meta Title', 'content' => 'Home', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 1, 'field_type' => 'textarea', 'field_name' => 'Meta Description', 'content' => 'Home', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 1, 'field_type' => 'text', 'field_name' => 'Meta Keys', 'content' => 'Home', 'created_at' => now(), 'updated_at' => now()],
            // First Section (section_id=2)
            ['page_id' => 1, 'section_id' => 2, 'field_type' => 'text', 'field_name' => 'Title Text', 'content' => 'IMPRESS THEM ALL,', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 2, 'field_type' => 'image', 'field_name' => 'Image 1', 'content' => 'uploads/cms/1510268908__rentasuit.png', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 2, 'field_type' => 'text', 'field_name' => 'Text 1', 'content' => 'QUALITY. STYLISH. ACCESSIBLE. PROFESSIONAL.', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 2, 'field_type' => 'text', 'field_name' => 'Button Text 1', 'content' => 'I want to RENT', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 2, 'field_type' => 'text', 'field_name' => 'Button Text 2', 'content' => 'I want to POST', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 2, 'field_type' => 'text', 'field_name' => 'Subtitle', 'content' => 'Start your journey today', 'created_at' => now(), 'updated_at' => now()],
            // Second Section (section_id=3)
            ['page_id' => 1, 'section_id' => 3, 'field_type' => 'image', 'field_name' => 'Image 1', 'content' => 'uploads/cms/1510269790__section-2-img-wrapper.png', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 3, 'field_type' => 'image', 'field_name' => 'Image 2', 'content' => 'uploads/cms/1510269790__section-2-img.png', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 3, 'field_type' => 'text', 'field_name' => 'Background Text', 'content' => 'welcome', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 3, 'field_type' => 'text', 'field_name' => 'Title Text 1', 'content' => 'renting a suit', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 3, 'field_type' => 'text', 'field_name' => 'Title Text 2', 'content' => 'at an affordable price', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 3, 'field_type' => 'text', 'field_name' => 'Text 1', 'content' => 'Now you can rent or post the latest brand and garments for less.', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 3, 'field_type' => 'text', 'field_name' => 'Text 2', 'content' => 'Rent used quality clothes for business, special occasions or events.', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 3, 'field_type' => 'text', 'field_name' => 'Text 3', 'content' => 'Why not be part of a supportive community while making someone else happy at the same time!', 'created_at' => now(), 'updated_at' => now()],
            // Third Section (section_id=4)
            ['page_id' => 1, 'section_id' => 4, 'field_type' => 'text', 'field_name' => 'Background Text', 'content' => 'The Process', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 4, 'field_type' => 'text', 'field_name' => 'Title Text 1', 'content' => 'rentals that suit you', 'created_at' => now(), 'updated_at' => now()],
            ['page_id' => 1, 'section_id' => 4, 'field_type' => 'text', 'field_name' => 'Title Text 2', 'content' => 'is finally simple', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_content');
    }
};
