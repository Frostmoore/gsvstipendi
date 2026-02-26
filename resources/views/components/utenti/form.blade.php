@props(['user' => null])

@csrf

<!-- Name -->
<div class="mt-4">
    <x-input-label for="name" :value="__('Nome')" />
    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $user->name ?? '')" required autofocus autocomplete="name" />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="surname" :value="__('Cognome')" />
    <x-text-input id="surname" class="block mt-1 w-full" type="text" name="surname" :value="old('surname', $user->surname ?? '')" required autofocus autocomplete="surname" />
    <x-input-error :messages="$errors->get('surname')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="role" :value="__('Tipo Operatore')" />
    <x-select-input id="role" class="block mt-1 w-full" name="role" required>
        <option value="">Seleziona un ruolo</option>
        @auth
            @if(Auth::user()->role == 'superadmin')
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="user" {{ old('role', 'user') == 'user' ? 'selected' : '' }}>Operatore</option>
                <option value="superadmin" {{ old('role') == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
            @else
                <option value="user" selected>Operatore</option>
            @endif
        @endauth
    </x-select-input>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="username" :value="__('User Name')" />
    <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username', $user->username ?? '')" required autofocus autocomplete="username" />
    <x-input-error :messages="$errors->get('username')" class="mt-2" />
</div>

<!-- Email Address -->
<div class="mt-4">
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email ?? '')" required autocomplete="email" />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<!-- Password -->
<div class="mt-4">
    <x-input-label for="password" :value="__('Password')" />
    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
    <x-input-error :messages="$errors->get('password')" class="mt-2" />
</div>

<!-- Confirm Password -->
<div class="mt-4">
    <x-input-label for="password_confirmation" :value="__('Conferma Password')" />
    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
</div>

<!-- Retribuzione Fissa -->
<div class="mt-4">
    <x-input-label for="fissa" :value="__('Retribuzione Mensile Fissa (€) — lascia vuoto se non applicabile')" />
    <x-text-input id="fissa" class="block mt-1 w-full" type="number" step="0.01" name="fissa" :value="old('fissa', $user->fissa ?? '')" autocomplete="off" />
</div>

<!-- Tariffe Individuali -->
<fieldset class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 mt-6 mb-2">
    <legend class="px-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Tariffe Individuali</legend>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Lascia vuoto per usare il valore di default.</p>

    @php
        $rateFields = [
            'giornata'        => 'Feriale Italia (€)',
            'feriale_estero'  => 'Feriale Estero (€)',
            'festivo'         => 'Festivo Italia (€)',
            'festivo_estero'  => 'Festivo Estero (€)',
            'straordinari'    => 'Straordinari (€/ora)',
            'trasferta'       => 'Trasferta Breve (€)',
            'trasferta_lunga' => 'Trasferta Lunga (€)',
            'pernotto'        => 'Pernotto (€)',
            'presidio'        => 'Presidio (€)',
            'tariffa_sabato'  => 'Tariffa 3° Sabato (€)',
        ];
        $ic = 'w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm text-sm px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    @endphp

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ($rateFields as $key => $label)
        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ $label }}</label>
            <input type="number" step="0.01" name="rates[{{ $key }}]" value="{{ old("rates.$key", '') }}" class="{{ $ic }}">
        </div>
        @endforeach
    </div>
</fieldset>

<div class="flex items-center justify-end mt-4">
    <x-primary-button class="ms-4">
        {{ __('Salva') }}
    </x-primary-button>
</div>
