<?php

namespace App\Livewire\Dashboard;

use App\Models\Transaction;
use Livewire\Component;

class UpcomingSubscriptionsWidget extends Component
{
    public function render()
    {
        $upcomingSubscriptions = Transaction::where('is_subscription', true)
            ->where('next_payment_date', '<=', now()->addDays(30))
            ->orderBy('next_payment_date')
            ->take(5)
            ->get();
        
        return view('livewire.dashboard.upcoming-subscriptions-widget', [
            'subscriptions' => $upcomingSubscriptions
        ]);
    }
} 