<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestione Compensi') }}
        </h2>
    </x-slot>
        
        
    <x-std-content>
        <div class="title-container mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Elenco Compensi') }}
            </h2>
        </div>

        <!-- Barra di ricerca con filtraggio dinamico -->
        <div x-data="{ search: '', showModal: false, selectedRole: null }">
        <div class="gsv-row-search">
            <input 
                type="text" 
                x-model.debounce.200ms="search"
                placeholder="Cerca compensi..." 
                class="px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 w-full mb-4 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 gsv-search"
            />
                <a href="{{ route('compensations.create') }}" class="btn btn-primary mb-3">
                    <i class="fa-solid fa-plus-circle gsv-add px-2 py-2"></i>
                </a>
        </div>

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="table-auto w-full std-table">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-2">Causale</th>
                            <th class="px-4 py-2">Tipo Operatore</th>
                            <th class="px-4 py-2">Compenso</th>
                            <th class="px-4 py-2">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 dark:text-gray-200">
                        @foreach($compensations as $compensation) 
                            <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600"
                                x-show="search === '' || 
                                        '{{ $compensation->name }}'.toLowerCase().includes(search.toLowerCase()) || 
                                        '{{ $compensation->role_name }}'.toLowerCase().includes(search.toLowerCase()) || 
                                        '{{ $compensation->value }}'.toLowerCase().includes(search.toLowerCase())">
                                <td class="px-4 py-2">{{ $compensation->name }}</td>
                                <td class="px-4 py-2">{{ $compensation->role_name == '' ? 'Tutti' : $compensation->role_name }}</td>
                                <td class="px-4 py-2">{{ $compensation->value }} €</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('compensations.edit', $compensation) }}" 
                                    class="btn btn-primary">
                                        <i class="fa-solid fa-pen-to-square gsv-edit px-2 py-2"></i>
                                    </a>
                                    <form action="{{ route('compensations.destroy', $compensation) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fa-solid fa-ban px-2 py-2 gsv-destroy"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Modale -->
            <div x-show="showModal" 
                x-transition 
                class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                <div class="bg-white p-6 rounded-lg shadow-lg w-1/3">
                    <h2 class="text-xl font-semibold mb-4">Dettagli Compensi</h2>
                    
                    <p><strong>ID:</strong> <span x-text="selectedRole?.id"></span></p>
                    <p><strong>Nome:</strong> <span x-text="selectedRole?.role"></span></p>
                    <p><strong>Campi:</strong> <span x-text="selectedRole?.fields"></span></p>
                    
                    <div class="mt-4">
                        <button @click="showModal = false" class="px-4 py-2 bg-red-500 text-white rounded-lg">
                            Chiudi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-std-content>
</x-app-layout>
