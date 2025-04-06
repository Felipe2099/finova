<?php

declare(strict_types=1);

namespace App\Services\Debt\Implementations;

use App\Models\Debt;
use App\Services\Debt\Contracts\DebtServiceInterface;
use App\DTOs\Debt\DebtData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\Payment\Implementations\PaymentService;

/**
 * Borç/Alacak servisi implementasyonu
 * 
 * Borç ve alacak işlemlerinin yönetimi için gerekli metodları içerir.
 * Borç/alacak kayıtlarının oluşturulması, güncellenmesi ve silinmesi işlemlerini gerçekleştirir.
 */
final class DebtService implements DebtServiceInterface
{
    private PaymentService $paymentService;

    /**
     * @param PaymentService $paymentService Ödeme servisi
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Yeni bir borç/alacak kaydı oluşturur
     * 
     * @param DebtData $data Borç/Alacak verileri
     * @return Debt Oluşturulan borç/alacak kaydı
     */
    public function create(DebtData $data): Debt
    {
        return DB::transaction(function () use ($data) {
            $debt = Debt::create([
                'user_id' => $data->user_id,
                'customer_id' => $data->type === 'debt_payment' ? $data->customer_id : null,
                'supplier_id' => $data->type === 'loan_payment' ? $data->supplier_id : null,
                'type' => $data->type,
                'description' => $data->description,
                'amount' => $data->amount,
                'currency' => $data->currency,
                'buy_price' => $data->buy_price,
                'due_date' => $data->due_date,
                'status' => $data->status,
                'notes' => $data->notes,
                'date' => $data->date,
            ]);

            $this->updateStatus($debt);
            $this->scheduleReminder($debt);
            return $debt;
        });
    }

    /**
     * Mevcut bir borç/alacak kaydını günceller
     * 
     * @param Debt $debt Güncellenecek borç/alacak kaydı
     * @param DebtData $data Yeni borç/alacak verileri
     * @return Debt Güncellenmiş borç/alacak kaydı
     */
    public function update(Debt $debt, DebtData $data): Debt
    {
        return DB::transaction(function () use ($debt, $data) {
            $debt->update([
                'customer_id' => $data->type === 'debt_payment' ? $data->customer_id : null,
                'supplier_id' => $data->type === 'loan_payment' ? $data->supplier_id : null,
                'type' => $data->type,
                'description' => $data->description,
                'amount' => $data->amount,
                'currency' => $data->currency,
                'buy_price' => $data->buy_price,
                'due_date' => $data->due_date,
                'status' => $data->status,
                'notes' => $data->notes,
                'date' => $data->date,
            ]);

            $this->updateStatus($debt);
            $this->scheduleReminder($debt);
            return $debt->fresh();
        });
    }

    /**
     * Borç/Alacak kaydını siler
     * 
     * @param Debt $debt Silinecek borç/alacak kaydı
     */
    public function delete(Debt $debt): void
    {
        DB::transaction(function () use ($debt) {
            $debt->delete();
        });
    }

    /**
     * Borç/Alacak kaydının durumunu günceller
     * 
     * Vade tarihi geçmiş kayıtların durumunu 'overdue' olarak günceller.
     * 
     * @param Debt $debt Güncellenecek borç/alacak kaydı
     */
    public function updateStatus(Debt $debt): void
    {
        if ($debt->due_date && Carbon::parse($debt->due_date)->startOfDay() < Carbon::now()->startOfDay() && $debt->status === 'pending') {
            $debt->update(['status' => 'overdue']);
        }
    }

    /**
     * Borç/Alacak kayıtları için hatırlatma planlar
     * 
     * Vade tarihinden 3 gün önce hatırlatma bildirimi gönderir.
     * 
     * @param Debt $debt Hatırlatma eklenecek borç/alacak kaydı
     */
    /**
    private function scheduleReminder(Debt $debt): void
    {
        if ($debt->due_date) {
            $reminderDate = Carbon::parse($debt->due_date)->subDays(3);
            if ($reminderDate->isFuture()) {
                Notification::make()
                    ->title('Borç/Alacak Hatırlatma')
                    ->body("Borç/Alacak #{$debt->id} vade tarihi yaklaşıyor: {$debt->due_date->format('d.m.Y')}")
                    ->send();
            }
        }
    }
    **/

    /**
     * Borç/Alacak kayıtlarını sıralı şekilde getirir
     * 
     * @param string $sortBy Sıralama alanı
     * @param string $direction Sıralama yönü
     * @return \Illuminate\Database\Eloquent\Collection Sıralanmış borç/alacak kayıtları
     */
    public function getSortedDebts(string $sortBy = 'due_date', string $direction = 'asc'): \Illuminate\Database\Eloquent\Collection
    {
        return Debt::whereIn('type', ['loan_payment', 'debt_payment'])
            ->orderBy($sortBy, $direction)
            ->get();
    }

    /*
     * Borç/Alacak kaydına ödeme ekler
     * 
     * Kıymetli madenler için gram bazında, diğerleri için birim bazında kar/zarar hesaplar.
     * 
     * @param Debt $debt Ödeme eklenecek borç/alacak kaydı
     * @param array $data Ödeme verileri
     */
    /*
    public function addPayment(Debt $debt, array $data): void
    {
        DB::transaction(function () use ($debt, $data) {
            // Satış fiyatı ve kar/zarar hesaplaması
            $sellPrice = $data['sell_price'] ?? null;
            $profitLoss = null;

            if ($sellPrice && $debt->buy_price) {
                // Kıymetli madenler için gram bazında, diğerleri için birim bazında kar/zarar hesaplama
                if (in_array($debt->currency, ['XAU', 'XAG'])) {
                    $profitLoss = ($sellPrice - $debt->buy_price) * $debt->amount; // Gram başına kar/zarar
                } else {
                    $profitLoss = ($sellPrice - $debt->buy_price) * $debt->amount; // Birim başına kar/zarar
                }
            }

            // Borç/alacak kaydını güncelle
            $debt->update([
                'sell_price' => $sellPrice,
                'profit_loss' => $profitLoss,
                'status' => 'completed',
            ]);

            // Ödeme işlemini gerçekleştir
            $this->paymentService->processPayment($debt, $data, $data['payment_method']);
        });
    }
    */
}