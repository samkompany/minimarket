<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="app-title">Profil</h2>
            <p class="app-subtitle">Informations personnelles et securite du compte.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="mx-auto max-w-6xl space-y-6">
            @if (auth()->user()->isAdmin())
                <div class="app-card p-6">
                    <div class="max-w-xl">
                        <livewire:profile.app-settings-form />
                    </div>
                </div>
            @endif

            <div class="app-card p-6">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="app-card p-6">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="app-card p-6">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
