<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Backup & Ripristino Database') }}
        </h2>
    </x-slot>

    <x-std-content>

        <div class="title-container mb-6">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Backup & Ripristino Database') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Esporta tutti i dati in formato JSON o ripristina il database da un file di backup.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- ===== ESPORTAZIONE ===== --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900">
                        <i class="fa-solid fa-file-arrow-down text-blue-600 dark:text-blue-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Esporta Database</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Scarica un file JSON con tutti i dati</p>
                    </div>
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-300 mb-5 space-y-1">
                    <p><i class="fa-solid fa-circle-check text-green-500 me-1"></i> Tutti i dati applicativi</p>
                    <p><i class="fa-solid fa-circle-xmark text-gray-400 me-1"></i> Sessioni, cache e job (esclusi)</p>
                    <p><i class="fa-solid fa-circle-info text-blue-400 me-1"></i> Il file includerà data e ora nel nome</p>
                </div>

                <a href="{{ route('backup.export') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fa-solid fa-download"></i>
                    Scarica Backup JSON
                </a>
            </div>

            {{-- ===== RIPRISTINO ===== --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6"
                 x-data="{ fileName: null, dragging: false }">

                <div class="flex items-center gap-3 mb-4">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900">
                        <i class="fa-solid fa-file-arrow-up text-amber-600 dark:text-amber-300"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Ripristina Database</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Carica un file JSON di backup</p>
                    </div>
                </div>

                {{-- Avviso --}}
                <div class="flex items-start gap-2 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-3 mb-5">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 mt-0.5"></i>
                    <p class="text-xs text-red-700 dark:text-red-300">
                        <strong>Attenzione:</strong> questa operazione sovrascrive <em>tutti</em> i dati esistenti
                        con quelli contenuti nel file. L'operazione è irreversibile.
                    </p>
                </div>

                <form method="POST"
                      action="{{ route('backup.restore') }}"
                      enctype="multipart/form-data"
                      x-data="restoreForm()"
                      @submit.prevent="confirmRestore">

                    @csrf

                    {{-- Drop zone --}}
                    <label for="backup_file"
                           class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer transition"
                           :class="dragging
                               ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                               : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600'"
                           @dragover.prevent="dragging = true"
                           @dragleave.prevent="dragging = false"
                           @drop.prevent="handleDrop($event)">

                        <template x-if="!fileName">
                            <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                <i class="fa-solid fa-cloud-arrow-up text-2xl mb-1"></i>
                                <p class="text-sm">Trascina qui il file o <span class="text-blue-500 underline">sfoglia</span></p>
                                <p class="text-xs mt-1">Solo file .json</p>
                            </div>
                        </template>
                        <template x-if="fileName">
                            <div class="flex flex-col items-center text-green-600 dark:text-green-400">
                                <i class="fa-solid fa-file-code text-2xl mb-1"></i>
                                <p class="text-sm font-medium" x-text="fileName"></p>
                            </div>
                        </template>

                        <input id="backup_file"
                               name="backup_file"
                               type="file"
                               accept=".json,application/json"
                               class="hidden"
                               @change="handleFile($event)" />
                    </label>

                    @error('backup_file')
                        <p class="text-sm text-red-600 dark:text-red-400 mt-2">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                            :disabled="!fileName"
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition w-full justify-center"
                            :class="fileName
                                ? 'bg-amber-600 hover:bg-amber-700 text-white cursor-pointer'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'">
                        <i class="fa-solid fa-rotate-left"></i>
                        Ripristina Database
                    </button>
                </form>
            </div>

        </div>

    </x-std-content>

    {{-- Dialog di conferma --}}
    <div id="confirm-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60"
         x-data
         x-ref="modal">
    </div>

    @push('scripts')
    <script>
        function restoreForm() {
            return {
                fileName: null,
                dragging: false,
                form: null,

                init() {
                    this.form = this.$el;
                },

                handleFile(event) {
                    const file = event.target.files[0];
                    if (file) this.fileName = file.name;
                },

                handleDrop(event) {
                    this.dragging = false;
                    const file = event.dataTransfer.files[0];
                    if (file) {
                        this.fileName = file.name;
                        // Assegna il file all'input
                        const input = this.form.querySelector('#backup_file');
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                    }
                },

                confirmRestore() {
                    if (!this.fileName) return;

                    const ok = window.confirm(
                        '⚠️ Sei sicuro di voler ripristinare il database?\n\n' +
                        'Tutti i dati attuali verranno sovrascritti con quelli del file:\n' +
                        this.fileName + '\n\n' +
                        'Questa operazione è irreversibile.'
                    );

                    if (ok) this.form.submit();
                },
            };
        }
    </script>
    @endpush

</x-app-layout>
