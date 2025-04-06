<?php

declare(strict_types=1);

namespace App\Livewire\Planning;

use App\Models\SavingsPlan;
use Livewire\Component;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Planning\Contracts\PlanningServiceInterface;

/**
 * Tasarruf Planı Yönetimi Bileşeni
 * 
 * Bu bileşen, tasarruf planlarının yönetimini sağlar.
 * Özellikler:
 * - Tasarruf planı listesi görüntüleme
 * - Yeni tasarruf planı oluşturma
 * - Tasarruf planı düzenleme
 * - Tasarruf planı silme
 * - Tasarruf durumu takibi
 * - Tasarruf filtreleme
 * - Toplu işlem desteği
 * 
 * @package App\Livewire\Planning
 */
final class SavingsPlanner extends Component implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    /** @var PlanningServiceInterface Planlama servisi */
    private PlanningServiceInterface $planningService;

    /**
     * Bileşen başlatılırken planlama servisini enjekte eder
     * 
     * @param PlanningServiceInterface $planningService Planlama servisi
     * @return void
     */
    public function boot(PlanningServiceInterface $planningService): void
    {
        $this->planningService = $planningService;
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
            ->query(SavingsPlan::query())
            ->emptyStateHeading('Tasarruf Planı Yok')
            ->emptyStateDescription('Başlamak için yeni bir tasarruf planı oluşturun.')
            ->columns([
                TextColumn::make('goal_name')
                    ->label('Hedef Adı')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_amount')
                    ->label('Hedef Tutar')
                    ->money('TRY')
                    ->sortable(),
                TextColumn::make('saved_amount')
                    ->label('Biriken Tutar')
                    ->money('TRY')
                    ->sortable(),
                TextColumn::make('target_date')
                    ->label('Hedef Tarihi')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'completed' => 'Tamamlandı',
                        'cancelled' => 'İptal Edildi',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->native(false)
                    ->options([
                        'active' => 'Aktif',
                        'completed' => 'Tamamlandı',
                        'cancelled' => 'İptal Edildi',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('Düzenle')
                    ->modalHeading('Tasarruf Planını Düzenle')
                    ->form([
                        TextInput::make('goal_name')
                            ->label('Hedef Adı')
                            ->required(),
                        TextInput::make('target_amount')
                            ->label('Hedef Tutar')
                            ->numeric()
                            ->required(),
                        TextInput::make('saved_amount')
                            ->label('Biriken Tutar')
                            ->numeric()
                            ->required(),
                        DatePicker::make('target_date')
                            ->label('Hedef Tarihi')
                            ->native(false)
                            ->required(),
                        Select::make('status')
                            ->label('Durum')
                            ->native(false)
                            ->options([
                                'active' => 'Aktif',
                                'completed' => 'Tamamlandı',
                                'cancelled' => 'İptal Edildi',
                            ])
                            ->required(),
                    ])
                    ->action(function (SavingsPlan $record, array $data): SavingsPlan {
                        return $this->planningService->updateSavingsPlan($record, $data);
                    }),
                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Tasarruf Planını Sil')
                    ->action(function (SavingsPlan $record): void {
                        $this->planningService->deleteSavingsPlan($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Tasarruf Planı Oluştur')
                    ->modalHeading('Yeni Tasarruf Planı')
                    ->form([
                        TextInput::make('goal_name')
                            ->label('Hedef Adı')
                            ->required(),
                        TextInput::make('target_amount')
                            ->label('Hedef Tutar')
                            ->numeric()
                            ->required(),
                        TextInput::make('saved_amount')
                            ->label('Biriken Tutar')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        DatePicker::make('target_date')
                            ->label('Hedef Tarihi')
                            ->native(false)
                            ->required(),
                        Select::make('status')
                            ->label('Durum')
                            ->native(false)
                            ->options([
                                'active' => 'Aktif',
                                'completed' => 'Tamamlandı',
                                'cancelled' => 'İptal Edildi',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->action(function (array $data): SavingsPlan {
                        return $this->planningService->createSavingsPlan($data);
                    })
                    ->modalSubmitActionLabel('Kaydet'),
            ]);
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.planning.savings-planner');
    }
} 