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

    protected $messages = [
        'email.required' => 'Email alanı zorunludur.',
        'email.email' => 'Geçerli bir email adresi giriniz.',
        'password.required' => 'Şifre alanı zorunludur.',
        'password.min' => 'Şifre en az 8 karakter olmalıdır.',
    ];

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect(route('admin.dashboard'));
        }
    }

    public function submit()
    {
        $credentials = $this->validate();

        if (Auth::attempt($credentials)) {
            session()->regenerate();
            
            $this->dispatch('loginSuccess');
            
            Notification::make()
                ->title(__('Giriş Başarılı'))
                ->success()
                ->send();
                
            return redirect()->intended(route('admin.dashboard'));
        }
        
        Notification::make()
            ->title(__('Giriş Başarısız'))
            ->danger()
            ->send();
            
        $this->resetPasswordField();
    }

    public function resetPasswordField(): void
    {
        $this->password = '';
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
} 