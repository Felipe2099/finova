<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Services\User\Contracts\UserServiceInterface;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Commission;
use App\Services\Commission\CommissionService;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Carbon;
/**
 * Kullanıcı yönetimi için liste ve CRUD işlemleri sağlayan bileşen.
 * 
 * Bu bileşen, kullanıcıların listelenmesi, filtrelenmesi, ve yönetimi için
 * Filament Table API kullanarak bir arayüz sağlar. Temel kullanıcı işlemleri
 * (düzenleme, silme, geri alma) ve toplu işlemler bu bileşen üzerinden yönetilir.
 */
class UserManager extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;
    /**
     * Tablo yapılandırmasını tanımlar
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->with('roles'))
            ->emptyStateHeading('Kullanıcı Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir kullanıcı oluşturun.')
            ->columns([
                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('email')
                    ->label('E-posta')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('roles.name')
                    ->label('Roller')
                    ->formatStateUsing(fn ($state, User $record) => $record->roles->pluck('name')->implode(', '))
                    ->searchable(),
                
                IconColumn::make('status')
                    ->label('Durum')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                TextColumn::make('commission_rate')
                    ->label('Oran (%)')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '-'),
            ])
            ->filters([
                TernaryFilter::make('status')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif')
                    ->native(false)
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', 1),
                        false: fn (Builder $query) => $query->where('status', 0),
                        blank: fn (Builder $query) => $query
                    ),
                SelectFilter::make('roles')
                    ->label('Roller')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),

            ])
            ->actions([
                                

                Action::make('commission_history')
                    ->label('Komisyon Geçmişi')
                    ->icon('heroicon-m-currency-dollar')
                    ->url(fn (User $record) => route('admin.users.commissions', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->visible(fn (User $record) => $record->has_commission),

                
                Action::make('changePassword')
                    ->label('Şifre Değiştir')
                    ->icon('heroicon-m-key')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('password')
                            ->label('Yeni Şifre')
                            ->password()
                            ->required()
                            ->confirmed()
                            ->rule(\Illuminate\Validation\Rules\Password::default()),
                        
                        \Filament\Forms\Components\TextInput::make('password_confirmation')
                            ->label('Yeni Şifre Tekrar')
                            ->password()
                            ->required(),
                    ])
                    ->modalHeading('Şifre Değiştir')
                    ->modalDescription(fn (User $record) => "{$record->name} kullanıcısının şifresini değiştirmek üzeresiniz.")
                    ->action(function (User $record, array $data) {
                        try {
                            app(UserServiceInterface::class)->updatePassword($record, $data['password']);
                            
                            Notification::make('password-changed')
                                ->title('Şifre değiştirildi')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make('password-change-error')
                                ->title('Hata!')
                                ->body('Şifre değiştirilirken bir hata oluştu: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),


                Action::make('edit')
                    ->label('Düzenle')
                    ->url(fn (User $record) => route('admin.users.edit', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->icon('heroicon-m-pencil-square'),

                
                Action::make('delete')
                    ->label('Sil')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Kullanıcıyı Sil')
                    ->modalDescription('Bu kullanıcıyı silmek istediğinizden emin misiniz?')
                    ->action(function (User $record) {
                        try {
                            app(UserServiceInterface::class)->delete($record, true);
                            
                            Notification::make('user-deleted')
                                ->title('Kullanıcı silindi')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make('user-delete-error')
                                ->title('Hata!')
                                ->body('Kullanıcı silinirken bir hata oluştu: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->hidden(fn (User $record) => $record->trashed()),

            ])
            ->headerActions([
                Action::make('create')
                    ->label('Kullanıcı Oluştur')
                    ->extraAttributes(['wire:navigate' => true])
                    ->url(route('admin.users.create')),
            ]);
    }


    /**
     * Bileşenin görünümünü render eder
     */
    public function render()
    {
        return view('livewire.user.user-manager');
    }
} 