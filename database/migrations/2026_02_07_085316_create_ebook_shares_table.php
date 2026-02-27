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
    Schema::create('ebook_shares', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('ebook_id');
        $table->unsignedBigInteger('shared_by');
        $table->string('token',100)->unique();
        $table->timestamp('expires_at')->nullable();
        $table->integer('max_views')->nullable();
        $table->integer('current_views')->default(0);
        $table->boolean('is_active')->default(1);
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('ebook_shares');
}

};
