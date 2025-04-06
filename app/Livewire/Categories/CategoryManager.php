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
 * Kategori Yönetimi Bileşeni
 * 
 * Bu bileşen, gelir ve gider kategorilerinin yönetimini sağlar.
 * Özellikler:
 * - Kategori listesi görüntüleme
 * - Yeni kategori ekleme
 * - Kategori düzenleme
 * - Kategori silme
 * - Kategori filtreleme (gelir/gider)
 * - Kategori durumu yönetimi
 * 
 * @package App\Livewire\Categories
 */
final class CategoryManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * Tablo yapılandırmasını oluşturur
     * 
     * @param Tables\Table $table Tablo nesnesi
     * @return Tables\Table Yapılandırılmış tablo
     */
    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(Category::query())
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
                    }),
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
                    ]),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Kategori Sil')
                    ->modalDescription('Bu kategoriyi silmek istediğinize emin misiniz?')
                    ->modalSubmitActionLabel('Sil')
                    ->modalCancelActionLabel('İptal')
                    ->successNotificationTitle('Kategori silindi'),
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
                    }),
            ]);
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.categories.category-manager');
    }
} 