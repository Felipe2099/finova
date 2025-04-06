<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;

final class Login extends Component
{
    public string $email = '';
    public string $password = '';

    // validate
    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:8',
    ];

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect(route('admin.dashboard'), navigate: true);
        }
    }

    public function submit(): void
    {
        // Authenticate the user
        $credentials = $this->validate();

        // check if user is authenticated
        if (Auth::attempt($credentials)) {
            session()->regenerate();
            Notification::make()
                ->title(__('auth.login_success'))
                ->success()
                ->send();
            $this->redirect(route('admin.dashboard'), navigate: true);
        }

        Notification::make()
            ->title(__('auth.login_failed'))
            ->danger()
            ->send();
        $this->resetPasswordField();
    }

    // reset password field
    public function resetPasswordField(): void
    {
        $this->password = '';
        // Ensure email remains unchanged
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
} 