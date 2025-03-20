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
            <option value="{{ $role->id }}" {{(old('role') == $role->id ? 'selected' : '')}} {{null !== Auth::user()->role ? (Auth::user()->role == $role->id ? 'selected' : '') : ''}}>{{ $role->role }}</option>
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

    <x-text-input id="password" class="block mt-1 w-full"
                    type="password"
                    name="password"
                    required autocomplete="new-password" />

    <x-input-error :messages="$errors->get('password')" class="mt-2" />
</div>

<!-- Confirm Password -->
<div class="mt-4">
    <x-input-label for="password_confirmation" :value="__('Conferma Password')" />

    <x-text-input id="password_confirmation" class="block mt-1 w-full"
                    type="password"
                    name="password_confirmation" required autocomplete="new-password" />

    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
</div>

<div class="mt-4" id="retriFissa" class="hidden">
    <x-input-label for="fissa" :value="__('Retribuzione Fissa')" />
    <x-text-input id="fissa" class="block mt-1 w-full" type="text" name="fissa" :value="old('fissa', $user->fissa ?? '')" autocomplete="fissa" />
</div>

<div class="mt-4" id="fasciaPrezzo" class="hidden">
    <x-input-label for="fascia" :value="__('Fascia Retributiva')" />
    <x-select-input id="fascia" class="block mt-1 w-full" type="text" name="fascia" autocomplete="fascia">
        <option value="0">Nessuna Fascia</option>
        <option value="50">50€</option>
        <option value="55">55€</option>
        <option value="60">60€</option>
        <option value="70">70€</option>
    </x-select-input>
</div>

<div class="mt-4" id="speciale" class="hidden">
    <x-input-label for="special" :value="__('Tariffa Speciale')" />
    <x-text-input id="special" class="block mt-1 w-full" type="text" name="special" :value="old('special', $user->special ?? '')" autocomplete="special" />
</div>

<div class="mt-4" id="trasfFissa" class="hidden">
    <x-input-label for="trasferta" :value="__('Tariffa Trasferte Fissa')" />
    <x-text-input id="trasferta" class="block mt-1 w-full" type="text" name="trasferta" :value="old('trasferta', $user->trasferta ?? '')" autocomplete="trasferta" />
</div>

<div class="mt-4">
    <x-input-label for="incremento" :value="__('Incremento su Tariffa')" />
    <x-text-input id="incremento" class="block mt-1 w-full" type="text" name="incremento" :value="old('incremento', $user->incremento ?? '')" autocomplete="incremento" />
</div>

<div class="flex items-center justify-end mt-4">
    <x-primary-button class="ms-4">
        {{ __('Salva') }}
    </x-primary-button>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let retriFissa = document.getElementById("retriFissa");
        let fasciaPrezzo = document.getElementById("fasciaPrezzo");
        let trasfFissa = document.getElementById("trasfFissa");
        let speciale = document.getElementById("speciale");
        retriFissa.style.display = "none";
        fasciaPrezzo.style.display = "none";
        trasfFissa.style.display = "none";
        speciale.style.display = "none";

        document.getElementById("role").addEventListener("change", function() {
            let selectedText = this.options[this.selectedIndex].text;
            let retriFissa = document.getElementById("retriFissa");
            let fasciaPrezzo = document.getElementById("fasciaPrezzo");
            let trasfFissa = document.getElementById("trasfFissa");
            let speciale = document.getElementById("speciale");

            switch (selectedText) {
                case "Autista":
                    retriFissa.style.display = "block";
                    fasciaPrezzo.style.display = "none";
                    trasfFissa.style.display = "none";
                    speciale.style.display = "none";
                    break;
                case "Facchino":
                    retriFissa.style.display = "none";
                    fasciaPrezzo.style.display = "block";
                    speciale.style.display = "block";
                    trasfFissa.style.display = "none";
                    break;
                default:
                    retriFissa.style.display = "none";
                    fasciaPrezzo.style.display = "none";
                    trasfFissa.style.display = "none";
                    speciale.style.display = "none";
                    break;
            }
        });

        document.querySelectorAll("#fissa, #special, #trasferta, #incremento").forEach(function(input) {
            input.addEventListener("input", function () {
                this.value = this.value.replace(/[^0-9]/g, ""); // Rimuove tutto ciò che non è un numero
            });
        });
    });
</script>
