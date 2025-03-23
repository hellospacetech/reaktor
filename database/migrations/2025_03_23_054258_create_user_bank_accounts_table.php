<?php

declare(strict_types=1);

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
        Schema::create('user_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('bank_id')->constrained('banks')->onDelete('restrict');
            $table->string('account_name')->nullable(); // Hesap adı (örn. "Ana İş Hesabım")
            $table->string('account_number')->nullable(); // Hesap numarası
            $table->string('iban'); // IBAN numarası
            $table->string('branch_code')->nullable(); // Şube kodu
            $table->boolean('is_default')->default(false); // Varsayılan hesap mı?
            $table->boolean('is_active')->default(true); // Hesap aktif mi?
            $table->timestamps();
            
            // Endeksler
            $table->index('user_id');
            $table->index('bank_id');
            $table->index('is_default');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bank_accounts');
    }
};
