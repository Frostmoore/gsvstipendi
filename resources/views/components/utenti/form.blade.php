@props(['roles', 'user' => null, 'compensations', 'userRoleRates' => []])

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
    <x-select-input id="role" class="block mt-1 w-full" type="text" name="role" required autofocus autocomplete="role">
        <option value="">Seleziona un ruolo</option>
        @auth
            @if(Auth::user()->role == 'superadmin')
                <option value="admin" {{(Auth::user()->role == 'admin' ? 'selected' : '')}}>Admin</option>
                <option value="user" {{(Auth::user()->role == 'user' ? 'selected' : '')}}>Utente Semplice (Nessun Accesso)</option>
                <option value="superadmin" {{(Auth::user()->role == 'superadmin' ? 'selected' : '')}}>Super Admin</option>
            @else
                <option value="user" {{(Auth::user()->role == 'user' ? 'selected' : '')}}>Utente Semplice (Nessun Accesso)</option>
            @endif
        @endauth
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" {{(old('role') == $role->id ? 'selected' : '')}}>{{ $role->role }}</option>
        @endforeach
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

<div class="mt-4" id="retriFissa" style="display:none">
    <x-input-label for="fissa" :value="__('Retribuzione Fissa (default)')" />
    <x-text-input id="fissa" class="block mt-1 w-full" type="text" name="fissa" :value="old('fissa', $user->fissa ?? '')" autocomplete="fissa" />
</div>

<!-- Compensi Individuali -->
<fieldset class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 mt-6 mb-2">
    <legend class="px-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Compensi Individuali</legend>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
        Lascia vuoto per usare il valore di default del ruolo. Puoi impostare override per qualsiasi ruolo che questo utente potrebbe ricoprire.
    </p>
    <div id="comp_override_fields"></div>
</fieldset>

<div class="flex items-center justify-end mt-4">
    <x-primary-button class="ms-4">
        {{ __('Salva') }}
    </x-primary-button>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const allCompensations = @json($compensations);
        const allRoles = @json($roles->pluck('role'));
        const existingOverrides = {};
        const existingRoleRates = {};

        const retriFissa = document.getElementById("retriFissa");

        document.getElementById("role").addEventListener("change", function() {
            const selectedText = this.options[this.selectedIndex].text;
            retriFissa.style.display = (selectedText === "Autista") ? "block" : "none";
        });

        document.getElementById("fissa").addEventListener("input", function() {
            this.value = this.value.replace(/[^0-9.]/g, "");
        });

        renderCompOverrides(allCompensations, allRoles, existingOverrides, existingRoleRates);
    });

    function renderCompOverrides(allCompensations, allRoles, existingOverrides, existingRoleRates) {
        const container = document.getElementById("comp_override_fields");
        if (!container) return;

        // Group compensations by role_name
        const byRole = {};
        allCompensations.forEach(function(c) {
            const r = c.role_name || 'Altro';
            if (!byRole[r]) byRole[r] = [];
            byRole[r].push(c);
        });

        // Also include roles that have no compensations yet but exist in allRoles
        allRoles.forEach(function(r) {
            if (!byRole[r]) byRole[r] = [];
        });

        let html = '';
        Object.keys(byRole).sort().forEach(function(roleName) {
            const rateData = existingRoleRates[roleName] || {};
            const roleKey = encodeURIComponent(roleName);

            html += '<div class="mb-5">';
            html += '<h4 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2 border-b border-gray-200 dark:border-gray-600 pb-1">' + roleName + '</h4>';
            const inputClass = ' class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md shadow-sm text-sm px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500"';
            const labelClass = '<label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">';

            html += '<div class="grid grid-cols-2 gap-3 sm:grid-cols-3">';

            // Giornata Lavorativa per-ruolo (sempre primo)
            const giornataVal = rateData.giornata !== undefined && rateData.giornata !== null ? rateData.giornata : '';
            html += '<div>' + labelClass + 'Giornata Lavorativa</label>';
            html += '<input type="number" step="0.01" name="role_rates[' + roleName + '][giornata]"'
                  + ' value="' + giornataVal + '" placeholder="es. 60"' + inputClass + '></div>';

            const sabVal = rateData.tariffa_sabato !== undefined && rateData.tariffa_sabato !== null ? rateData.tariffa_sabato : '';
            html += '<div>' + labelClass + 'Tariffa 3° Sabato</label>';
            html += '<input type="number" step="0.01" name="role_rates[' + roleName + '][tariffa_sabato]"'
                  + ' value="' + sabVal + '" placeholder="es. 80"' + inputClass + '></div>';

            // Altre compensazioni (esclusa Giornata Lavorativa, già sopra)
            byRole[roleName].forEach(function(c) {
                if (c.name === 'Giornata Lavorativa') return;
                const val = existingOverrides[c.id] !== undefined ? existingOverrides[c.id] : '';
                html += '<div>' + labelClass + c.name + '</label>';
                html += '<input type="number" step="0.01" name="compensation_overrides[' + c.id + ']"'
                      + ' value="' + val + '" placeholder="' + c.value + '"' + inputClass + '></div>';
            });

            html += '</div></div>';
        });

        container.innerHTML = html || '<p class="text-sm text-gray-400">Nessun compenso configurato.</p>';
    }
</script>
