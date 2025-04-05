<?php

use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('status')->default(TaskStatus::Active)->after('name');
        });

        // Mevcut kayıtları güncelle
        DB::table('tasks')->whereNull('done_at')->update(['status' => TaskStatus::Active]);
        DB::table('tasks')->whereNotNull('done_at')->update(['status' => TaskStatus::Done]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
