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
        Schema::table('tasks', function (Blueprint $table) {
            // Eğer is_done kolonu varsa kaldır
            if (Schema::hasColumn('tasks', 'is_done')) {
                $table->dropColumn('is_done');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Geri alma durumunda is_done kolonunu tekrar ekle
            if (!Schema::hasColumn('tasks', 'is_done')) {
                $table->boolean('is_done')->default(false)->after('name');
            }
        });
    }
};
