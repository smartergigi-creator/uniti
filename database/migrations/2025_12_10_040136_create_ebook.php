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
    Schema::create('ebook', function (Blueprint $table) {
        $table->id();

        $table->string('title');                 // Flipbook name (PDF name)
        $table->string('pdf_path');              // Storage path of original PDF
        $table->string('folder_path');           // public/flipbooks/{id}/pages
        $table->integer('page_count')->default(0); // Number of images generated

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebook');
    }
};
