<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @filamentStyles
    @livewireStyles

    <style>
        /* Tüm toggle sütunlarını küçültme */
        .fi-ta-col-toggle .fi-ta-toggle {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }

        /* Toggle düğmesinin kendisini küçültme */
        .fi-toggle {
            --toggle-width: 2.5rem !important;
            --toggle-height: 1.25rem !important;
        }

        /* Toggle içindeki yuvarlak düğmeyi küçültme */
        .fi-toggle-dot {
            --toggle-dot-size: 0.875rem !important;
        }
    </style>

</head>
<body class="bg-gray-100">
    @auth
        @include('components.partials.header')
        @include('components.partials.sidebar')
        
        <main class="lg:ml-64 pt-16">
            <div class="p-4">
                <div class="w-full overflow-hidden">
                    {{ $slot }}
                </div>
            </div>
        </main>
    @else
        {{ $slot }}
    @endauth
    
    @livewireScripts
    @filamentScripts
   
    @livewire('notifications')

    @stack('scripts')
</body>
</html>
