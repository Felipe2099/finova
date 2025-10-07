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
 * Component for managing users.
 * 
 * This component provides a list and CRUD interface for managing users
 * using the Filament Table API. Basic user operations
 * (edit, delete, restore) and bulk operations are managed through this component.
 */ 
class UserManager extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;
    /**
     * Defines the table configuration
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
                    ->visible(fn (User $record) => $record->has_commission && auth()->user()->can('users.commissions')),

                
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
                    })
                    ->disabled(fn () => config('app.app_demo_mode', false))
                    ->tooltip(fn () => config('app.app_demo_mode', false) ? 'Demo modunda şifre değiştirilemez' : null)
                    ->visible(auth()->user()->can('users.change_password')),

                Action::make('edit')
                    ->label('Düzenle')
                    ->url(fn (User $record) => route('admin.users.edit', $record))
                    ->extraAttributes(['wire:navigate' => true])
                    ->icon('heroicon-m-pencil-square')
                    ->visible(auth()->user()->can('users.edit')),

                
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
                    ->hidden(fn (User $record) => $record->trashed())
                    ->visible(auth()->user()->can('users.delete')),

            ])
            ->headerActions([
                Action::make('create')
                    ->label('Kullanıcı Oluştur')
                    ->extraAttributes(['wire:navigate' => true])
                    ->url(route('admin.users.create'))
                    ->visible(auth()->user()->can('users.create')),
            ]);
    }


    /**
     * Renders the component view
     */
    public function render()
    {
        return view('livewire.user.user-manager');
    }
} 