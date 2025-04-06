<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Contracts\View\View;

final class SettingsIndex extends Component
{
    public function render(): View
    {
        return view('livewire.settings.index');
    }
} 