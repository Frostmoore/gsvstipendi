<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestione Fogli Orari') }}
        </h2>
    </x-slot>
    <x-std-content>
        <div class="title-container mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Modifica Foglio Orario') }}
            </h2>
        </div>
        <form class="gsv-form" method="POST" action="{{ route('user-timesheets.update', $timesheet) }}">
            @method('PATCH')
            <x-user-timesheets.form
                :months="$months"
                :companies="$companies"
                :years="$years"
                :users="$users"
                :timesheet="$timesheet"
                :userRates="$userRates"
            />
        </form>
    </x-std-content>
</x-app-layout>
