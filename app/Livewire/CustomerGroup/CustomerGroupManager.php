<?php

namespace App\Livewire\CustomerGroup;

use App\Models\CustomerGroup;
use App\Services\CustomerGroup\Contracts\CustomerGroupServiceInterface;
use App\DTOs\CustomerGroup\CustomerGroupData;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;

/**
 * Müşteri Grubu Yönetimi Bileşeni
 * 
 * Bu bileşen, müşteri gruplarının yönetimini sağlar.
 * Özellikler:
 * - Müşteri grubu listesi görüntüleme
 * - Yeni müşteri grubu ekleme
 * - Müşteri grubu düzenleme
 * - Müşteri grubu silme
 * - Müşteri grubu durumu yönetimi
 * 
 * @package App\Livewire\CustomerGroup
 */
final class CustomerGroupManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var CustomerGroupServiceInterface Müşteri grubu servisi */
    private CustomerGroupServiceInterface $customerGroupService;

    /**
     * Bileşen başlatılırken müşteri grubu servisini enjekte eder
     * 
     * @param CustomerGroupServiceInterface $customerGroupService Müşteri grubu servisi
     * @return void
     */
    public function boot(CustomerGroupServiceInterface $customerGroupService): void 
    {
        $this->customerGroupService = $customerGroupService;
    }

    /**
     * Tablo yapılandırmasını oluşturur
     * 
     * @param Tables\Table $table Tablo nesnesi
     * @return Tables\Table Yapılandırılmış tablo
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(CustomerGroup::query())
            ->emptyStateHeading('Müşteri Grubu Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir müşteri grubu oluşturun.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Grup Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Açıklama')
                    ->limit(50),
                Tables\Columns\IconColumn::make('status')
                    ->label('Durum')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Grubu Düzenle')
                    ->modalSubmitActionLabel('Güncelle')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Müşteri grubu düzenlendi')
                    ->using(function (CustomerGroup $record, array $data): CustomerGroup {
                        $groupData = CustomerGroupData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                        ]);
                        return $this->customerGroupService->update($record, $groupData);
                    })
                    ->form($this->getCustomerGroupForm()),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Grubu Sil')
                    ->modalDescription('Bu grubu silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Müşteri grubu silindi')
                    ->using(function (CustomerGroup $record): void {
                        $this->customerGroupService->delete($record);
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Müşteri Grubu Oluştur')
                    ->modalHeading('Yeni Müşteri Grubu')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal')
                    ->createAnother(false)
                    ->successNotificationTitle('Müşteri grubu oluşturuldu')
                    ->using(function (array $data): CustomerGroup {
                        $groupData = CustomerGroupData::fromArray([
                            ...$data,
                            'user_id' => auth()->id(),
                        ]);
                        return $this->customerGroupService->create($groupData);
                    })
                    ->form($this->getCustomerGroupForm()),
            ]);
    }

    /**
     * Müşteri grubu formunu oluşturur
     * 
     * @return array Form bileşenleri
     */
    protected function getCustomerGroupForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Grup Adı')
                ->required(),
            Forms\Components\Textarea::make('description')
                ->label('Açıklama')
                ->rows(3),
            Forms\Components\Toggle::make('status')
                ->label('Durum')
                ->default(true),
        ];
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.customer-group.customer-group-manager');
    }
} 