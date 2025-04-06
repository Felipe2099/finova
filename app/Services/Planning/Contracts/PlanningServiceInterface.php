<?php

declare(strict_types=1);

namespace App\Services\Planning\Contracts;

use App\Models\SavingsPlan;
use App\Models\InvestmentPlan;

/**
 * Planlama servisi arayüzü
 * 
 * Tasarruf ve yatırım planlarının yönetimi için gerekli metodları tanımlar.
 * Planların oluşturulması, güncellenmesi ve silinmesi işlemlerini içerir.
 */
interface PlanningServiceInterface
{
    /**
     * Yeni bir tasarruf planı oluşturur
     * 
     * @param array $data Tasarruf planı verileri
     * @return SavingsPlan Oluşturulan tasarruf planı
     */
    public function createSavingsPlan(array $data): SavingsPlan;

    /**
     * Mevcut bir tasarruf planını günceller
     * 
     * @param SavingsPlan $plan Güncellenecek tasarruf planı
     * @param array $data Güncellenecek veriler
     * @return SavingsPlan Güncellenmiş tasarruf planı
     */
    public function updateSavingsPlan(SavingsPlan $plan, array $data): SavingsPlan;

    /**
     * Bir tasarruf planını siler
     * 
     * @param SavingsPlan $plan Silinecek tasarruf planı
     */
    public function deleteSavingsPlan(SavingsPlan $plan): void;

    /**
     * Yeni bir yatırım planı oluşturur
     * 
     * @param array $data Yatırım planı verileri
     * @return InvestmentPlan Oluşturulan yatırım planı
     */
    public function createInvestmentPlan(array $data): InvestmentPlan;

    /**
     * Mevcut bir yatırım planını günceller
     * 
     * @param InvestmentPlan $plan Güncellenecek yatırım planı
     * @param array $data Güncellenecek veriler
     * @return InvestmentPlan Güncellenmiş yatırım planı
     */
    public function updateInvestmentPlan(InvestmentPlan $plan, array $data): InvestmentPlan;

    /**
     * Bir yatırım planını siler
     * 
     * @param InvestmentPlan $plan Silinecek yatırım planı
     */
    public function deleteInvestmentPlan(InvestmentPlan $plan): void;
} 