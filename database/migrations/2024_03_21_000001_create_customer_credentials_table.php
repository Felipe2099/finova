<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Müşteri Hassas Bilgileri Tablosu
 * 
 * Bu tablo, müşterilere ait hassas bilgileri (domain, hosting, sunucu vb.) yönetir.
 * Özellikler:
 * - Hassas bilgileri şifreli olarak saklama
 * - Bilgi ekleme/düzenleme/silme
 * - Bilgi geçmişi takibi
 * - Kullanıcı bazlı yetkilendirme
 * 
 * @package Database\Migrations
 */
return new class extends Migration
{
    /**
     * Tabloyu oluşturur
     * 
     * @return void
     */
    public function up(): void
    {
        Schema::create('customer_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('value'); 
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Tabloyu siler
     * 
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_credentials');
    }
}; 