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
        $table->integer('max_views')->nullable()->after('share_expires_at');
        $table->integer('current_views')->default(0)->after('max_views');
        $table->unsignedBigInteger('shared_by')->nullable()->after('user_id');
    });
}

public function down()
{
    Schema::table('ebook', function (Blueprint $table) {
        $table->dropColumn(['max_views','current_views','shared_by']);
    });
}

};
