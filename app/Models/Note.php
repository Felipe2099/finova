<?php

namespace App\Models;

/**
 * Not arayüzü
 * 
 * Not modellerinin uygulaması gereken temel metotları tanımlar.
 * Her not bir müşteriye ve kullanıcıya ait olmalıdır.
 */
interface Note
{
    /**
     * Notun ait olduğu müşteri
     * 
     * @return BelongsTo
     */
    public function customer();

    /**
     * Notu oluşturan kullanıcı
     * 
     * @return BelongsTo
     */
    public function user();
}