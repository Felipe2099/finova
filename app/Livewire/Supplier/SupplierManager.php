<?php

namespace App\Livewire\Supplier;

use App\Models\Supplier;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Services\Supplier\Contracts\SupplierServiceInterface;
use App\DTOs\Supplier\SupplierData;

/**
 * Supplier Manager Component
 * 
 * This component provides functionality to manage suppliers.
 * Features:
 * - Supplier list view
 * - New supplier creation
 * - Supplier editing
 * - Supplier deletion
 * - Supplier status tracking
 * - Supplier filtering
 * 
 * @package App\Livewire\Supplier
 */
class SupplierManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var SupplierServiceInterface Supplier service */
    private SupplierServiceInterface $supplierService;

    /**
     * When the component is booted, the supplier service is injected
     * 
     * @param SupplierServiceInterface $supplierService Supplier service
     * @return void
     */
    public function boot(SupplierServiceInterface $supplierService): void
    {
        $this->supplierService = $supplierService;
    }

    /**
     * Creates the table configuration
     * 
     * @param Tables\Table $table Table object
     * @return Tables\Table Configured table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Supplier::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tedarikçi Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('İletişim Kişisi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Durum')
                    ->boolean()
                    ->sortable(),
            ])
            ->emptyStateHeading('Tedarikçi Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir tedarikçi ekleyin.')
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Tedarikçi Düzenle')
                    ->modalSubmitActionLabel('Güncelle')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Tedarikçi güncellendi')
                    ->label('Düzenle')
                    ->form($this->getSupplierForm())
                    ->using(function (Supplier $record, array $data): Supplier {
                        return $this->supplierService->update($record, SupplierData::fromArray($data));
                    })
                    ->visible(auth()->user()->can('suppliers.edit')),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Tedarikçi Sil')
                    ->modalDescription('Bu tedarikçiyi silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Tedarikçi silindi')
                    ->label('Sil')
                    ->using(function (Supplier $record): void {
                        $this->supplierService->delete($record);
                    })
                    ->visible(auth()->user()->can('suppliers.delete')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tedarikçi Oluştur')
                    ->modalHeading('Yeni Tedarikçi')
                    ->modalSubmitActionLabel('Oluştur')
                    ->modalCancelActionLabel('İptal')
                    ->createAnother(false)
                    ->successNotificationTitle('Tedarikçi oluşturuldu')
                    ->form($this->getSupplierForm())
                    ->using(function (array $data): Supplier {
                        return $this->supplierService->create(SupplierData::fromArray($data));
                    })
                    ->visible(auth()->user()->can('suppliers.create')),
            ]);
    }

    /**
     * Creates the supplier form configuration
     * 
     * @return array Form components
     */
    protected function getSupplierForm(): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Tedarikçi Adı')
                        ->required(),
                    Forms\Components\TextInput::make('contact_name')
                        ->label('İletişim Kişisi'),
                    Forms\Components\TextInput::make('phone')
                        ->label('Telefon')
                        ->tel(),
                    Forms\Components\TextInput::make('email')
                        ->label('E-posta')
                        ->email()
                        ->unique(Supplier::class, 'email', ignoreRecord: true),
                ]),
            Forms\Components\Textarea::make('address')
                ->label('Adres')
                ->rows(3),
            Forms\Components\Textarea::make('notes')
                ->label('Notlar')
                ->rows(3),
            Forms\Components\Toggle::make('status')
                ->label('Durum')
                ->default(true),
        ];
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.supplier.supplier-manager');
    }
}