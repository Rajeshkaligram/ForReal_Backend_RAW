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
        // Add missing sections to page_section table if they don't exist
        if (!DB::table('page_section')->where('id', 5)->exists()) {
            DB::table('page_section')->insert([
                ['id' => 5, 'name' => 'Fourth', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        if (!DB::table('page_section')->where('id', 6)->exists()) {
            DB::table('page_section')->insert([
                ['id' => 6, 'name' => 'Fifth', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Add missing content for section_second (section_id=3) - needs index 7
        $sectionSecondCount = DB::table('page_content')->where('page_id', 1)->where('section_id', 3)->count();
        if ($sectionSecondCount < 8) {
            DB::table('page_content')->insert([
                ['page_id' => 1, 'section_id' => 3, 'field_type' => 'text', 'field_name' => 'Text 4', 'content' => 'Start your journey today and discover amazing rental options.', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Add missing content for section_third (section_id=4) - needs index 3
        $sectionThirdCount = DB::table('page_content')->where('page_id', 1)->where('section_id', 4)->count();
        if ($sectionThirdCount < 4) {
            DB::table('page_content')->insert([
                ['page_id' => 1, 'section_id' => 4, 'field_type' => 'text', 'field_name' => 'Process Repeater', 'content' => base64_encode(serialize([[['field_type' => 'image', 'field_name' => 'Step Image', 'field_value' => 'uploads/cms/process-step1.jpg'], ['field_type' => 'text', 'field_name' => 'Step Title', 'field_value' => 'Browse'], ['field_type' => 'text', 'field_name' => 'Step Description', 'field_value' => 'Browse through our collection']], [['field_type' => 'image', 'field_name' => 'Step Image', 'field_value' => 'uploads/cms/process-step2.jpg'], ['field_type' => 'text', 'field_name' => 'Step Title', 'field_value' => 'Select'], ['field_type' => 'text', 'field_name' => 'Step Description', 'field_value' => 'Select your favorite items']], [['field_type' => 'image', 'field_name' => 'Step Image', 'field_value' => 'uploads/cms/process-step3.jpg'], ['field_type' => 'text', 'field_name' => 'Step Title', 'field_value' => 'Rent'], ['field_type' => 'text', 'field_name' => 'Step Description', 'field_value' => 'Rent and enjoy']]])), 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Add content for section_fourth (section_id=5) if it doesn't exist
        $sectionFourthCount = DB::table('page_content')->where('page_id', 1)->where('section_id', 5)->count();
        if ($sectionFourthCount < 3) {
            DB::table('page_content')->insert([
                ['page_id' => 1, 'section_id' => 5, 'field_type' => 'text', 'field_name' => 'Background Text', 'content' => 'Categories', 'created_at' => now(), 'updated_at' => now()],
                ['page_id' => 1, 'section_id' => 5, 'field_type' => 'text', 'field_name' => 'Title Text 1', 'content' => 'Browse by', 'created_at' => now(), 'updated_at' => now()],
                ['page_id' => 1, 'section_id' => 5, 'field_type' => 'text', 'field_name' => 'Title Text 2', 'content' => 'Category', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // Add content for section_fifth (section_id=6) if it doesn't exist
        $sectionFifthCount = DB::table('page_content')->where('page_id', 1)->where('section_id', 6)->count();
        if ($sectionFifthCount < 3) {
            DB::table('page_content')->insert([
                ['page_id' => 1, 'section_id' => 6, 'field_type' => 'text', 'field_name' => 'Background Text', 'content' => 'Latest Rentals', 'created_at' => now(), 'updated_at' => now()],
                ['page_id' => 1, 'section_id' => 6, 'field_type' => 'text', 'field_name' => 'Title Text 1', 'content' => 'Featured', 'created_at' => now(), 'updated_at' => now()],
                ['page_id' => 1, 'section_id' => 6, 'field_type' => 'text', 'field_name' => 'Title Text 2', 'content' => 'Products', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove content for section_fifth
        DB::table('page_content')->where('page_id', 1)->where('section_id', 6)->delete();
        
        // Remove content for section_fourth
        DB::table('page_content')->where('page_id', 1)->where('section_id', 5)->delete();
        
        // Remove the extra content for section_third
        DB::table('page_content')->where('page_id', 1)->where('section_id', 4)->where('field_name', 'Process Repeater')->delete();
        
        // Remove the extra content for section_second
        DB::table('page_content')->where('page_id', 1)->where('section_id', 3)->where('field_name', 'Text 4')->delete();
        
        // Remove the sections
        DB::table('page_section')->whereIn('id', [5, 6])->delete();
    }
};
