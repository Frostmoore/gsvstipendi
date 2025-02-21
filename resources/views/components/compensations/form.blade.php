@csrf
<div class="mb-4">
    <x-input-label for="name" :value="__('Causale')" />
    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $compensation->name ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>
<div class="mb-4">
    <x-input-label for="role" :value="__('Tipo Operatore')" />
    <x-select-input id="role" class="block mt-1 w-full" type="text" name="role" :value="old('role', $compensation->role_name ?? '')" autofocus>
        <option value="tutti">Seleziona un Ruolo</option>
        @foreach($roles as $role)
            <option value="{{$role->id}}" {{(old('role') == $role->id ? 'selected' : '')}} {{isset($compensation) ? ($compensation->role == $role->id ? 'selected' : '') : ''}}>{{$role->role}}</option>
        @endforeach
    </x-select-input>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>
<div class="mb-4">
    <x-input-label for="value" :value="__('Compenso')" />
    <x-number-input id="value" class="block mt-1 w-full" type="text" name="value" :value="old('value', $compensation->value ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('value')" class="mt-2" />
</div>
<div class="mb-4">
    <x-input-label for="type" :value="__('Compenso')" />
    <x-select-input id="type" class="block mt-1 w-full" type="text" name="type" :value="old('type', $compensation->type ?? '')">
        <option value='text' {{(old('type') == 'text' ? 'selected' : '')}} {{isset($compensation) ? ($compensation->type == 'text' ? 'selected' : '') : ''}}>Testo</option>
        <option value='number' {{(old('type') == 'number' ? 'selected' : '')}} {{isset($compensation) ? ($compensation->type == 'number' ? 'selected' : '') : ''}}>Numero</option>
        <option value="checkbox" {{(old('type') == 'checkbox' ? 'selected' : '')}} {{isset($compensation) ? ($compensation->type == 'checkbox' ? 'selected' : '') : ''}}>Checkbox</option>
    </x-select-input>
    <x-input-error :messages="$errors->get('type')" class="mt-2" />
</div>

<div class="flex items-center justify-end mt-4">
    <x-primary-button class="ms-3">
        {{ __('Salva') }}
    </x-primary-button>
</div>