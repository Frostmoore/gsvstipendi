<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestione Fogli Orari') }}
        </h2>
    </x-slot>
    <x-std-content>
        <div class="title-container mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Aggiungi un nuovo Foglio Orario') }}
            </h2>
        </div>
        <form class="gsv-form" method="POST" action="{{ route('timesheets.store') }}">
            <x-timesheets.form :months="$months" :years="$years" :roles="$roles" :compensations="$compensations" :users="$users" />
        </form>
    </x-std-content>
</x-app-layout>
