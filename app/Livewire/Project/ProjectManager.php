<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Services\Project\Contracts\ProjectServiceInterface;
use App\DTOs\Project\ProjectData;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

/**
 * Proje Yönetimi Bileşeni
 * 
 * Bu bileşen, projelerin yönetimini sağlar.
 * Özellikler:
 * - Proje listesi görüntüleme
 * - Yeni proje oluşturma
 * - Proje düzenleme
 * - Proje silme
 * - Proje durumu takibi
 * - Proje filtreleme
 * - Proje panosu yönetimi
 * 
 * @package App\Livewire\Project
 */
class ProjectManager extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    /** @var ProjectServiceInterface Proje servisi */
    private ProjectServiceInterface $projectService;

    /**
     * Bileşen başlatılırken proje servisini enjekte eder
     * 
     * @param ProjectServiceInterface $projectService Proje servisi
     * @return void
     */
    public function boot(ProjectServiceInterface $projectService): void 
    {
        $this->projectService = $projectService;
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
            ->query(Project::query())
            ->emptyStateHeading('Proje Bulunamadı')
            ->emptyStateDescription('Başlamak için yeni bir proje oluşturun.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Proje Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Oluşturan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'completed' => 'Tamamlandı',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'active' => 'Aktif',
                        'completed' => 'Tamamlandı',
                    ])
                    ->native(false)
            ])
            ->actions([
                Tables\Actions\Action::make('boards')
                    ->label('Proje Yönetimi')
                    ->url(fn (Project $record): string => route('admin.projects.boards', $record))
                    ->icon('heroicon-m-squares-2x2'),
                Tables\Actions\EditAction::make()
                    ->label('Düzenle')
                    ->modalHeading('Proje Düzenle')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Proje Adı')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Açıklama'),
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options([
                                'active' => 'Aktif',
                                'completed' => 'Tamamlandı',
                            ])
                            ->native(false)
                            ->required(),
                    ])
                    ->using(function (Project $record, array $data): Project {
                        return $this->projectService->update($record, ProjectData::fromArray($data));
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Sil')
                    ->using(function (Project $record): void {
                        $this->projectService->delete($record);
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Proje Oluştur')
                    ->modalHeading('Yeni Proje')
                    ->createAnother(false)
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Proje Adı')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Açıklama'),
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options([
                                'active' => 'Aktif',
                                'completed' => 'Tamamlandı',
                            ])
                            ->default('active')
                            ->native(false)
                            ->required(),
                    ])
                    ->using(function (array $data): Project {
                        return $this->projectService->create(ProjectData::fromArray([
                            ...$data,
                            'created_by' => auth()->id(),
                        ]));
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
        return view('livewire.project.project-manager');
    }
} 