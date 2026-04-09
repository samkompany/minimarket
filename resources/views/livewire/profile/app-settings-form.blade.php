<?php

use App\Models\AppSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public string $appName = '';
    public $logo = null;

    public function mount(): void
    {
        $this->appName = AppSetting::get('app_name', config('app.name', 'miniMaket'));
    }

    public function save(): void
    {
        $this->validate([
            'appName' => ['required', 'string', 'max:50'],
            'logo'    => ['nullable', 'image', 'max:2048'],
        ]);

        AppSetting::set('app_name', $this->appName);

        if ($this->logo) {
            $old = AppSetting::get('app_logo');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $path = $this->logo->store('logos', 'public');
            AppSetting::set('app_logo', $path);
            $this->logo = null;
        }

        $this->dispatch('app-settings-saved');
    }

    public function removeLogo(): void
    {
        $old = AppSetting::get('app_logo');
        if ($old) {
            Storage::disk('public')->delete($old);
        }
        AppSetting::set('app_logo', null);
    }
}; ?>

<section>
    <header>
        <h2 class="text-base font-semibold text-slate-900">
            Paramètres de l'application
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Modifiez le nom et le logo affiché dans la navigation.
        </p>
    </header>

    <form wire:submit="save" class="mt-6 space-y-5">

        {{-- Nom de l'application --}}
        <div>
            <label for="appName" class="block text-sm font-medium text-slate-700">
                Nom de l'application
            </label>
            <input
                type="text"
                id="appName"
                wire:model="appName"
                class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                placeholder="Ex: MonMarché"
            />
            @error('appName')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Logo actuel --}}
        @php
            $currentLogo = \App\Models\AppSetting::get('app_logo');
        @endphp
        @if ($currentLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($currentLogo))
            <div>
                <p class="mb-1 text-sm font-medium text-slate-700">Logo actuel</p>
                <div class="flex items-center gap-4">
                    <img
                        src="{{ Storage::url($currentLogo) }}"
                        class="h-14 w-14 rounded-xl object-contain ring-1 ring-slate-200"
                        alt="Logo actuel"
                    />
                    <button
                        type="button"
                        wire:click="removeLogo"
                        wire:confirm="Supprimer le logo ?"
                        class="text-xs font-medium text-rose-600 hover:text-rose-700"
                    >
                        Supprimer
                    </button>
                </div>
            </div>
        @endif

        {{-- Upload nouveau logo --}}
        <div>
            <label class="block text-sm font-medium text-slate-700">
                {{ $currentLogo ? 'Changer le logo' : 'Ajouter un logo' }}
            </label>
            <input
                type="file"
                wire:model="logo"
                accept="image/*"
                class="mt-1 block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100"
            />
            @error('logo')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
            @if ($logo)
                <div class="mt-2">
                    <p class="mb-1 text-xs text-slate-500">Aperçu :</p>
                    <img src="{{ $logo->temporaryUrl() }}" class="h-14 w-14 rounded-xl object-contain ring-1 ring-slate-200" />
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500"
            >
                Enregistrer
            </button>

            <x-action-message class="text-sm text-teal-600" on="app-settings-saved">
                Sauvegardé.
            </x-action-message>
        </div>
    </form>
</section>
