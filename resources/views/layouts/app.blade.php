<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Models\AppSetting::get('app_name', config('app.name', 'miniMaket')) }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:300,400,500,600,700|sora:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="app-shell">
            <div class="app-frame">
                <livewire:layout.navigation />

                <div class="app-content">
                    <!-- Page Heading -->
                    @if (isset($header))
                        <header class="app-header">
                            <div class="app-header-inner">
                                <div class="flex-1">
                                    {{ $header }}
                                </div>
                            </div>
                        </header>
                    @endif

                    <!-- Page Content -->
                    <main class="app-main">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
