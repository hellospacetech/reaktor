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
        Schema::create('banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                  // Banka Adı
            $table->string('short_name')->nullable(); // Kısa adı
            $table->string('address')->nullable();    // Adres
            $table->string('phone')->nullable();      // Telefon
            $table->string('fax')->nullable();        // Fax
            $table->string('website')->nullable();    // Web Adresi
            $table->string('telex')->nullable();      // Teleks
            $table->string('eft_code')->nullable();   // EFT Kodu
            $table->string('swift_code')->nullable(); // Swift
            $table->string('logo_path')->nullable();  // Logo dosya yolu
            $table->string('country_code')->default('TR'); // Ülke kodu
            $table->boolean('is_active')->default(true); // Aktif mi?
            $table->timestamps();
            
            // Endeksler
            $table->index('name');
            $table->index('eft_code');
            $table->index('swift_code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
