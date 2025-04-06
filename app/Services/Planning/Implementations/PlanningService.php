<?php

declare(strict_types=1);

namespace App\Services\Planning\Implementations;

use App\Models\SavingsPlan;
use App\Models\InvestmentPlan;
use App\Services\Planning\Contracts\PlanningServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Planlama servisi implementasyonu
 * 
 * Tasarruf ve yatırım planlarının yönetimi için gerekli metodları içerir.
 * Planların oluşturulması, güncellenmesi ve silinmesi işlemlerini gerçekleştirir.
 */
final class PlanningService implements PlanningServiceInterface
{
    /**
     * Yeni bir tasarruf planı oluşturur
     * 
     * @param array $data Tasarruf planı verileri
     * @return SavingsPlan Oluşturulan tasarruf planı
     */
    public function createSavingsPlan(array $data): SavingsPlan
    {
        return DB::transaction(function () use ($data) {
            $data['user_id'] = auth()->id();
            return SavingsPlan::create($data);
        });
    }

    /**
     * Mevcut bir tasarruf planını günceller
     * 
     * @param SavingsPlan $plan Güncellenecek tasarruf planı
     * @param array $data Güncellenecek veriler
     * @return SavingsPlan Güncellenmiş tasarruf planı
     */
    public function updateSavingsPlan(SavingsPlan $plan, array $data): SavingsPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update($data);
            return $plan->fresh();
        });
    }

    /**
     * Bir tasarruf planını siler
     * 
     * @param SavingsPlan $plan Silinecek tasarruf planı
     */
    public function deleteSavingsPlan(SavingsPlan $plan): void
    {
        DB::transaction(function () use ($plan) {
            $plan->delete();
        });
    }

    /**
     * Yeni bir yatırım planı oluşturur
     * 
     * @param array $data Yatırım planı verileri
     * @return InvestmentPlan Oluşturulan yatırım planı
     */
    public function createInvestmentPlan(array $data): InvestmentPlan
    {
        return DB::transaction(function () use ($data) {
            $data['user_id'] = auth()->id();
            return InvestmentPlan::create($data);
        });
    }

    /**
     * Mevcut bir yatırım planını günceller
     * 
     * @param InvestmentPlan $plan Güncellenecek yatırım planı
     * @param array $data Güncellenecek veriler
     * @return InvestmentPlan Güncellenmiş yatırım planı
     */
    public function updateInvestmentPlan(InvestmentPlan $plan, array $data): InvestmentPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update($data);
            return $plan->fresh();
        });
    }

    /**
     * Bir yatırım planını siler
     * 
     * @param InvestmentPlan $plan Silinecek yatırım planı
     */
    public function deleteInvestmentPlan(InvestmentPlan $plan): void
    {
        DB::transaction(function () use ($plan) {
            $plan->delete();
        });
    }
} 