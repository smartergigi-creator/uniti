<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ebook', function (Blueprint $table) {
            if (!Schema::hasColumn('ebook', 'shared_by')) {
                return;
            }

            try {
                $table->foreign('shared_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // Ignore if constraint already exists.
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'created_by')) {
                return;
            }

            try {
                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // Ignore if constraint already exists.
            }
        });

        if (Schema::hasTable('ebook_shares')) {
            Schema::table('ebook_shares', function (Blueprint $table) {
                if (Schema::hasColumn('ebook_shares', 'ebook_id')) {
                    try {
                        $table->foreign('ebook_id')
                            ->references('id')
                            ->on('ebook')
                            ->cascadeOnDelete();
                    } catch (\Throwable $e) {
                        // Ignore if constraint already exists.
                    }
                }

                if (Schema::hasColumn('ebook_shares', 'shared_by')) {
                    try {
                        $table->foreign('shared_by')
                            ->references('id')
                            ->on('users')
                            ->cascadeOnDelete();
                    } catch (\Throwable $e) {
                        // Ignore if constraint already exists.
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('ebook', function (Blueprint $table) {
            try {
                $table->dropForeign(['shared_by']);
            } catch (\Throwable $e) {
                // Ignore if missing.
            }
        });

        Schema::table('users', function (Blueprint $table) {
            try {
                $table->dropForeign(['created_by']);
            } catch (\Throwable $e) {
                // Ignore if missing.
            }
        });

        if (Schema::hasTable('ebook_shares')) {
            Schema::table('ebook_shares', function (Blueprint $table) {
                try {
                    $table->dropForeign(['ebook_id']);
                } catch (\Throwable $e) {
                    // Ignore if missing.
                }

                try {
                    $table->dropForeign(['shared_by']);
                } catch (\Throwable $e) {
                    // Ignore if missing.
                }
            });
        }
    }
};
