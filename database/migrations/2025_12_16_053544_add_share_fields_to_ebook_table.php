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
            $table->string('share_token', 100)
                  ->nullable()
                  ->unique()
                  ->after('id');

            $table->timestamp('share_expires_at')
                  ->nullable()
                  ->after('share_token');

            $table->boolean('share_enabled')
                  ->default(false)
                  ->after('share_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('ebook', function (Blueprint $table) {
            $table->dropColumn([
                'share_token',
                'share_expires_at',
                'share_enabled'
            ]);
        });
    }
};
