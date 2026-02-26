<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestione Fogli Orari') }}
        </h2>
    </x-slot>
    <x-std-content>
        <div class="title-container mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Vedi Foglio Orario') }}
            </h2>
        </div>
        {{-- @dd($timesheet_one); --}}
        {{-- @dd(get_defined_vars()); --}}
        <x-user-timesheets.show-timesheet-table :userTimesheet="$timesheet_one" :users="$users" :months="$months" />
    </x-std-content>
</x-app-layout>
