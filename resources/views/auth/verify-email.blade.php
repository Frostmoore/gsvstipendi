<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Grazie per esserti iscritto! Prima di iniziare, verifica il tuo indirizzo e-mail tramite il link che ti è stato inviato alla casella che hai fornito in fase di registrazione. L\'e-mail non ti è arrivata? Non c\'è problema, clicca sul link qui sotto per fartene spedire un\'altra.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('Un nuovo link di verifica è stato inviato all\'indirizzo e-mail fornito in fase di registrazione.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Invia una nuova e-mail di verifica') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                {{ __('Esci') }}
            </button>
        </form>
    </div>
</x-guest-layout>
