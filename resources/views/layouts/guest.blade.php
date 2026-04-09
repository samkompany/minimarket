<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php $appName = \App\Models\AppSetting::get('app_name', config('app.name', 'miniMaket')); @endphp
        <title>{{ $appName }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:300,400,500,600,700|sora:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="app-auth-shell flex min-h-screen items-center justify-center px-4 py-12">
            <div class="flex w-full max-w-5xl flex-col items-center gap-10 lg:flex-row lg:items-stretch">
                <div class="flex w-full flex-col justify-center text-left text-white lg:w-1/2">
                    <div class="flex items-center gap-3">
                        <x-application-logo class="h-16 w-16 fill-current text-white/90" />
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-widest text-white/70">Plateforme</div>
                            <div class="text-3xl font-semibold">{{ $appName }}</div>
                        </div>
                    </div>
                    <p class="mt-6 max-w-md text-sm text-white/70">
                        Pilotez ventes, stocks et achats depuis un espace clair, rapide et fiable.
                        Connectez-vous pour acceder au tableau de bord.
                    </p>
                    <div class="mt-8 grid gap-4 text-sm text-white/70">
                        <div class="flex items-center gap-3">
                            <span class="h-2 w-2 rounded-full bg-teal-300"></span>
                            Suivi quotidien des ventes et marges.
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-2 w-2 rounded-full bg-amber-300"></span>
                            Controle du stock et alertes rapides.
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="h-2 w-2 rounded-full bg-sky-300"></span>
                            Achats et fournisseurs centralises.
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-1/2">
                    <div class="app-auth-card">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
