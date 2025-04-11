<?php

namespace App\Providers;

use App\Services\AI\Contracts\AIAssistantInterface;
use App\Services\AI\Implementations\OpenAIAssistant;
use Illuminate\Support\ServiceProvider;
use OpenAI;

class OpenAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OpenAI\Client::class, function () {
            return OpenAI::client(config('ai.openai.api_key'));
        });

        $this->app->bind(AIAssistantInterface::class, OpenAIAssistant::class);
    }
} 