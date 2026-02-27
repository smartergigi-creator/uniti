<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('ebook_pages', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('ebook_id');

            $table->integer('page_no');

            $table->string('image_path');

            $table->enum('orientation', ['portrait', 'landscape']);

            $table->integer('width');

            $table->integer('height');

            $table->timestamps();

            // Foreign key (IMPORTANT: table name = ebook)
            $table->foreign('ebook_id')
                  ->references('id')
                  ->on('ebook')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebook_pages');
    }
};
