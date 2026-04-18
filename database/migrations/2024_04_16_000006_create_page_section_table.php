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
        if (Schema::hasTable('page_section')) {
            return;
        }
        Schema::create('page_section', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->timestamps();
        });

        // Insert default sections
        DB::table('page_section')->insert([
            ['id' => 1, 'name' => 'SEO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'First', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Second', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Third', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Fourth', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'Fifth', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_section');
    }
};
