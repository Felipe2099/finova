<?php

namespace App\Livewire\Proposal;

use App\Models\ProposalTemplate;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

/**
 * Teklif Şablonu Yönetimi Bileşeni
 * 
 * Bu bileşen, teklif şablonlarının yönetimini sağlar.
 * Özellikler:
 * - Teklif şablonu listesi görüntüleme
 * - Yeni teklif şablonu oluşturma
 * - Teklif şablonu düzenleme
 * - Teklif şablonu silme
 * - Teklif durumu takibi
 * - PDF çıktısı alma
 * - E-posta ile gönderme
 * 
 * @package App\Livewire\Proposal
 */
class ProposalTemplateManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * Tablo sorgusunu oluşturur
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getTableQuery()
    {
        return ProposalTemplate::query()
            ->with(['customer', 'items'])
            ->latest();
    }

    /**
     * Tablo boş durum başlığını döndürür
     * 
     * @return string|null
     */
    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Teklif Bulunamadı';
    }

    /**
     * Tablo boş durum açıklamasını döndürür
     * 
     * @return string|null
     */
    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Başlamak için yeni bir teklif oluşturun.';
    }

    /**
     * Tablo boş durum ikonunu döndürür
     * 
     * @return string|null
     */
    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    /**
     * Tablo sütunlarını oluşturur
     * 
     * @return array
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('number')
                ->label('Teklif No')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('customer.name')
                ->label('Müşteri')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('items')
                ->label('Toplam Tutar')
                ->money('TRY')
                ->getStateUsing(fn (ProposalTemplate $record): float => $record->getTotalAmount())
                ->alignEnd(),
            Tables\Columns\TextColumn::make('valid_until')
                ->label('Geçerlilik')
                ->date()
                ->sortable()
                ->description(fn (ProposalTemplate $record): string => $record->isExpired() ? 'Süresi Dolmuş' : 'Aktif'),
            Tables\Columns\TextColumn::make('status')
                ->label('Durum')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'sent' => 'info',
                    'accepted' => 'success',
                    'rejected' => 'danger',
                    'expired' => 'warning',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'draft' => 'Taslak',
                    'sent' => 'Gönderildi',
                    'accepted' => 'Kabul Edildi',
                    'rejected' => 'Reddedildi',
                    'expired' => 'Süresi Doldu',
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('creator.name')
                ->label('Oluşturan')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Oluşturma Tarihi')
                ->dateTime()
                ->sortable(),
        ];
    }

    /**
     * Tablo işlemlerini oluşturur
     * 
     * @return array
     */
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('edit')
                ->url(fn (ProposalTemplate $record): string => route('admin.proposals.edit', ['template' => $record->id]))
                ->icon('heroicon-m-pencil-square'),
            Tables\Actions\Action::make('pdf')
                ->label('PDF')
                ->url(fn (ProposalTemplate $record): string => route('admin.proposals.pdf', ['proposal' => $record->id]))
                ->icon('heroicon-m-document-arrow-down')
                ->openUrlInNewTab()
                ->color('success'),
            Tables\Actions\Action::make('send')
                ->label('Gönder')
                ->icon('heroicon-m-paper-airplane')
                ->action(function (ProposalTemplate $record) {
                    $record->update(['status' => 'sent']);
                })
                ->requiresConfirmation()
                ->visible(fn (ProposalTemplate $record): bool => $record->status === 'draft'),
            Tables\Actions\DeleteAction::make(),
        ];
    }

    /**
     * Tablo başlık işlemlerini oluşturur
     * 
     * @return array
     */
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label('Teklif Oluştur')
                ->url(route('admin.proposals.create'))
                ->icon('heroicon-m-plus')
                ->extraAttributes(['wire:navigate' => true]),
        ];
    }

    /**
     * Bileşenin görünümünü render eder
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        return view('livewire.proposal.manager');
    }
}
