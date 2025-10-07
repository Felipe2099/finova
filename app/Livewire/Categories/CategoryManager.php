<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

/**
 * Category Management Component
 * 
 * This component manages the categories of income and expense.
 * Features:
 * - Category list view
 * - Create new category
 * - Edit category
 * - Delete category
 * - Category filtering (income/expense)
 * - Category status management
 * 
 * @package App\Livewire\Categories
 */
final class CategoryManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * Creates the table configuration
     * 
     * @param Tables\Table $table Table object
     * @return Tables\Table Configured table
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Category::query())
            ->defaultGroup(
                Tables\Grouping\Group::make('type')
                    ->label('Tip')
                    ->getTitleFromRecordUsing(fn (Category $record): string => match ($record->type) {
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                        default => ucfirst($record->type),
                    })
            )
            ->emptyStateHeading('Kategori Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir kategori ekleyin.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kategori Adı')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tip')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                    })
                    , // groupable() kaldırıldı
                Tables\Columns\ColorColumn::make('color')
                    ->label('Renk'),
                Tables\Columns\IconColumn::make('status')
                    ->label('Durum')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tip')
                    ->options([
                        'income' => 'Gelir',
                        'expense' => 'Gider',
                    ])
                    ->placeholder('Tüm Tipler')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Kategori Düzenle')
                    ->modalSubmitActionLabel('Güncelle')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Kategori güncellendi')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Kategori Adı')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Tip')
                            ->options([
                                'income' => 'Gelir',
                                'expense' => 'Gider',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Renk'),
                        Forms\Components\Toggle::make('status')
                            ->label('Durum')
                            ->default(true),
                    ])
                    ->visible(auth()->user()->can('categories.edit')),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Kategori Sil')
                    ->modalDescription('Bu kategoriyi silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Kategori silindi')
                    ->visible(auth()->user()->can('categories.delete')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Kategori Ekle')
                    ->modalHeading('Yeni Kategori')
                    ->modalSubmitActionLabel('Kaydet')
                    ->modalCancelActionLabel('İptal')
                    ->createAnother(false)
                    ->successNotificationTitle('Kategori eklendi')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Kategori Adı')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Tip')
                            ->options([
                                'income' => 'Gelir',
                                'expense' => 'Gider',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Renk'),
                        Forms\Components\Toggle::make('status')
                            ->label('Durum')
                            ->default(true),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->visible(auth()->user()->can('categories.create')),
            ]);
    }

    /**
     * Renders the component view
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.categories.category-manager');
    }
} 