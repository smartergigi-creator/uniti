<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ebook_issue_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ebook_id');
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedInteger('page');
            $table->text('description');
            $table->timestamps();

            $table->foreign('ebook_id')->references('id')->on('ebook')->cascadeOnDelete();
            $table->foreign('reported_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('recipient_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['ebook_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebook_issue_reports');
    }
};
