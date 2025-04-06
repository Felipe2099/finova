<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Contracts\View\View;

final class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
