<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Rimuovi Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Una volta eseguita questa operazione, tutte le informazioni e le risorse collegate al tuo account saranno rimosse in modo permanente, e non ci sarà alcuna via per recuperarle. Se desideri assicurarti di mantenere le informazioni che hai inserito su quest\'app, ricordati di salvarle prima di procedere.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Rimuovi Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Sicuro di voler rimuovere il tuo account? Questa operazione è IRREVERSIBILE.') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Una volta che il tuo account sarà stato rimosso, tutte le informazioni e le risorse associate a quest\'ultimo andranno perse per sempre. Inserisci la tua password per confermare.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Annulla') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Rimuovi Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
