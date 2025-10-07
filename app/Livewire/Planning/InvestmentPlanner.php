<?php

declare(strict_types=1);

namespace App\Livewire\Planning;

use App\Models\InvestmentPlan;
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
 * Investment Planner Component
 * 
 * This component provides functionality to manage investment plans.
 * Features:
 * - Investment plan list view
 * - New investment plan creation
 * - Investment plan editing
 * - Investment plan deletion
 * - Investment type tracking
 * - Investment filtering
 * - Bulk action support
 * 
 * @package App\Livewire\Planning
 */
final class InvestmentPlanner extends Component implements Tables\Contracts\HasTable, Forms\Contracts\HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    /** @var PlanningServiceInterface Planning service */
    private PlanningServiceInterface $planningService;

    /**
     * When the component is booted, the planning service is injected
     * 
     * @param PlanningServiceInterface $planningService Planning service
     * @return void
     */
    public function boot(PlanningServiceInterface $planningService): void
    {
        $this->planningService = $planningService;
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
            ->query(InvestmentPlan::query())
            ->emptyStateHeading('Yatırım Planı Yok')
            ->emptyStateDescription('Başlamak için yeni bir yatırım planı oluşturun.')
            ->columns([
                TextColumn::make('investment_name')
                    ->label('Yatırım Adı')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invested_amount')
                    ->label('Yatırılan Tutar')
                    ->money('TRY')
                    ->sortable(),
                TextColumn::make('current_value')
                    ->label('Güncel Değer')
                    ->money('TRY')
                    ->sortable(),
                TextColumn::make('investment_type')
                    ->label('Yatırım Tipi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'stocks' => 'success',
                        'real_estate' => 'info',
                        'crypto' => 'warning',
                        'other' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'stocks' => 'Hisse Senedi',
                        'real_estate' => 'Gayrimenkul',
                        'crypto' => 'Kripto',
                        'other' => 'Diğer',
                    }),
                TextColumn::make('investment_date')
                    ->label('Yatırım Tarihi')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('investment_type')
                    ->label('Yatırım Tipi')
                    ->native(false)
                    ->options([
                        'stocks' => 'Hisse Senedi',
                        'real_estate' => 'Gayrimenkul',
                        'crypto' => 'Kripto',
                        'other' => 'Diğer',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('Düzenle')
                    ->modalHeading('Yatırım Planını Düzenle')
                    ->form([
                        TextInput::make('investment_name')
                            ->label('Yatırım Adı')
                            ->required(),
                        TextInput::make('invested_amount')
                            ->label('Yatırılan Tutar')
                            ->numeric()
                            ->required(),
                        TextInput::make('current_value')
                            ->label('Güncel Değer')
                            ->numeric()
                            ->required(),
                        Select::make('investment_type')
                            ->label('Yatırım Tipi')
                            ->native(false)
                            ->options([
                                'stocks' => 'Hisse Senedi',
                                'real_estate' => 'Gayrimenkul',
                                'crypto' => 'Kripto',
                                'other' => 'Diğer',
                            ])
                            ->required(),
                        DatePicker::make('investment_date')
                            ->label('Yatırım Tarihi')
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function (InvestmentPlan $record, array $data): InvestmentPlan {
                        return $this->planningService->updateInvestmentPlan($record, $data);
                    })
                    ->visible(auth()->user()->can('investments.edit')),
                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Yatırım Planını Sil')
                    ->action(function (InvestmentPlan $record): void {
                        $this->planningService->deleteInvestmentPlan($record);
                    })
                    ->visible(auth()->user()->can('investments.delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(auth()->user()->can('investments.delete')),
                ]),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Yatırım Planı Oluştur')
                    ->modalHeading('Yeni Yatırım Planı')
                    ->form([
                        TextInput::make('investment_name')
                            ->label('Yatırım Adı')
                            ->required(),
                        TextInput::make('invested_amount')
                            ->label('Yatırılan Tutar')
                            ->numeric()
                            ->required(),
                        TextInput::make('current_value')
                            ->label('Güncel Değer')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Select::make('investment_type')
                            ->label('Yatırım Tipi')
                            ->native(false)
                            ->options([
                                'stocks' => 'Hisse Senedi',
                                'real_estate' => 'Gayrimenkul',
                                'crypto' => 'Kripto',
                                'other' => 'Diğer',
                            ])
                            ->required(),
                        DatePicker::make('investment_date')
                            ->label('Yatırım Tarihi')
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function (array $data): InvestmentPlan {
                        return $this->planningService->createInvestmentPlan($data);
                    })
                    ->modalSubmitActionLabel('Kaydet')
                    ->visible(auth()->user()->can('investments.create')),
            ]);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.planning.investment-planner');
    }
} 