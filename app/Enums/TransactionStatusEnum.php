<?php

namespace App\Enums;

/**
 * İşlem Durumu Enum Sınıfı
 * 
 * Finansal işlemlerin durumlarını tanımlar.
 * İşlemlerin yaşam döngüsünü takip etmek için kullanılır.
 */
enum TransactionStatusEnum: string
{
    /** Beklemede */
    case PENDING = 'pending';
    /** Tamamlandı */
    case COMPLETED = 'completed';
    /** İptal Edildi */
    case CANCELLED = 'cancelled';
    /** Başarısız */
    case FAILED = 'failed';
} 