<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
class Register extends Component
{
    public function mount()
    {
        if (Auth::check()) {
            return $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
} 