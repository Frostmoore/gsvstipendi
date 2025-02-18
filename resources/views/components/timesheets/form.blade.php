@csrf
<div class="mb-4">
    <x-input-label for="role" :value="__('Nome Ruolo')" />
    <x-text-input id="role" class="block mt-1 w-full" type="text" name="role" :value="old('role', $role->role ?? '')" required autofocus autocomplete="username" />
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>
<div class="mb-4">
    <x-input-label for="fields" :value="__('Campi')" />
    <x-text-input id="fields" class="block mt-1 w-full" type="text" name="fields" :value="old('fields', $role->fields ?? '')" required autofocus autocomplete="username" />
    <x-input-error :messages="$errors->get('fields')" class="mt-2" />
</div>

<div class="flex items-center justify-end mt-4">
    <x-primary-button class="ms-3">
        {{ __('Salva') }}
    </x-primary-button>
</div>