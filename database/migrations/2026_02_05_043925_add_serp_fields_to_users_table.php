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

            $table->string('serp_id')->unique()->after('id');

            $table->text('serp_token')->nullable();

            $table->timestamp('last_login_at')->nullable();

            $table->enum('status',['active','inactive'])
                    ->default('active');

            $table->enum('created_from',['serp','local'])
                ->default('serp');
         });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('users', function (Blueprint $table) {

        $table->dropUnique(['serp_id']);
        $table->dropColumn('serp_id');

        $table->dropColumn('serp_token');
        $table->dropColumn('last_login_at');
        $table->dropColumn('status');
        $table->dropColumn('created_from');

    });
}

};
