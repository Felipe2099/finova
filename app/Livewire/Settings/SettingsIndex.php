<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Contracts\View\View;

final class SettingsIndex extends Component
{
    public string $activeTab = 'site'; // Varsayılan sekme

    // Kullanıcının istediği yeni sekmeler
    public array $tabs = [];

    public function mount()
    {
        $availableTabs = [
            'site' => ['name' => 'Site Ayarları', 'permission' => 'settings.site'],
            'notification' => ['name' => 'Bildirim Ayarları', 'permission' => 'settings.notification'],
            'telegram' => ['name' => 'Telegram Yapılandırması', 'permission' => 'settings.telegram'], // Yeni sekme
        ];

        foreach ($availableTabs as $key => $tabInfo) {
            if (auth()->user()->can($tabInfo['permission'])) {
                $this->tabs[$key] = $tabInfo['name'];
            }
        }

        // If the default tab is not available, set the active tab to the first available one
        if (!empty($this->tabs) && !array_key_exists($this->activeTab, $this->tabs)) {
            $this->activeTab = array_key_first($this->tabs);
        }
        // If no tabs are available, handle appropriately (e.g., set activeTab to null or show an error)
        elseif (empty($this->tabs)) {
             $this->activeTab = ''; // Or handle as needed
        }
    }

    // Aktif sekmeyi değiştiren metot
    public function setActiveTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }

    // İlgili Livewire bileşeninin adını döndürür
    public function getActiveComponent(): string
    {
        // Özel durum: 'telegram' için 'telegram-settings' bileşenini kullan
        if ($this->activeTab === 'telegram') {
            return 'settings.telegram-settings';
        }
        // Diğerleri için standart formatı kullan ('site' -> 'site-settings', 'notification' -> 'notification-settings')
        return 'settings.' . str_replace('_', '-', $this->activeTab) . '-settings';
    }

    public function render(): View
    {
        return view('livewire.settings.index');
    }
}