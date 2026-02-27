<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('ebook', function (Blueprint $table) {
        $table->longText('pdf_path')->change();
    });
}

public function down()
{
    Schema::table('ebook', function (Blueprint $table) {
        $table->string('pdf_path')->change();
    });
}


    
};
