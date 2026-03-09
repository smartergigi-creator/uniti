<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ebook', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('ebook', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};

