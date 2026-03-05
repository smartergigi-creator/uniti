<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_deleted')->default(0)->after('parent_id');
        });

        DB::table('categories')
            ->whereIn(DB::raw('LOWER(name)'), [
                'department e-books',
                'department ebooks',
                'handmade manual',
            ])
            ->update(['is_deleted' => 1]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }
};
