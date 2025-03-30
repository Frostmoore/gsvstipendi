<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestione Utenti') }}
        </h2>
    </x-slot>
    <x-std-content>
        <div class="title-container mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Modifica Utente') }}
            </h2>
        </div>
        <form class="gsv-form" method="POST" action="{{ route('utenti.update', $user) }}">
        @method('PATCH')
            <x-utenti.form-edit :roles="$roles" :user="$user"/>
        </form>
    </x-std-content>
</x-app-layout>
