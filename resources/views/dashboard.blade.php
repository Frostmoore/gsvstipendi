<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Benvenuto nel tuo Pannello di Controllo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Ciao {{ Auth::user()->name }}, benvenuto! 👋
                </h3>
                <p class="text-gray-700 dark:text-gray-300 mb-6">
                    Da qui puoi inviare il tuo <strong>foglio orario</strong> per il calcolo dello stipendio direttamente alla sede. 
                    Assicurati di compilarlo con attenzione per evitare errori!
                </p>

                <div class="flex justify-center">
                    <a href="{{ route('user-timesheets.index') }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg text-lg flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                        </svg>
                        Vai ai Fogli Orari
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
