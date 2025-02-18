<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestione Ruoli') }}
        </h2>
    </x-slot>
    <x-std-content>
        <div class="title-container mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Aggiungi un nuovo Ruolo') }}
            </h2>
        </div>
        <form class="gsv-form" method="POST" action="{{ route('roles.store') }}">
            <x-roles.form />
        </form>
    </x-std-content>
</x-app-layout>
