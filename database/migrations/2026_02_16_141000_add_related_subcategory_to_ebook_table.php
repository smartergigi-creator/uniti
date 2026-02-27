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
        Schema::table('ebook', function (Blueprint $table) {
            $table->foreignId('related_subcategory_id')
                ->nullable()
                ->after('subcategory_id')
                ->constrained('categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebook', function (Blueprint $table) {
            $table->dropConstrainedForeignId('related_subcategory_id');
        });
    }
};
