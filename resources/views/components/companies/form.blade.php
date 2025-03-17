@csrf
<div class="mb-4">
    <x-input-label for="name" :value="__('Nome Azienda')" />
    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $companies->name ?? '')" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="flex items-center justify-end mt-4">
    <x-primary-button class="ms-3">
        {{ __('Salva') }}
    </x-primary-button>
</div>