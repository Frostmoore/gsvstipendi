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

<div class="flex items-center justify-end mt-4">
    <x-primary-button class="ms-4">
        {{ __('Salva') }}
    </x-primary-button>
</div>
