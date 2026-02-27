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
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('can_upload')->default(0)->after('role');
        $table->boolean('can_share')->default(1)->after('can_upload');
        $table->unsignedBigInteger('created_by')->nullable()->after('created_from');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['can_upload','can_share','created_by']);
    });
}

};
