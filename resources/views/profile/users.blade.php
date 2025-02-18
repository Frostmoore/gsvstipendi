<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestione Utenti') }}
        </h2>
    </x-slot>

    <x-std-content>
        <div class="title-container mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Elenco Utenti') }}
            </h2>
        </div>

        <!-- Barra di ricerca con filtraggio dinamico -->
        <div x-data="{ search: '', showModal: false, selectedUser: null }">
            <div class="gsv-row-search">
                <input 
                    type="text" 
                    x-model.debounce.200ms="search"
                    placeholder="Cerca utenti..." 
                    class="px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 w-full mb-4 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 gsv-search"
                />
                <a href="{{ route('profile.users.create') }}" class="btn btn-primary mb-3">
                    <i class="fa-solid fa-plus-circle gsv-add px-2 py-2"></i>
                </a>
            </div>

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="table-auto w-full std-table">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-2">Username</th>
                            <th class="px-4 py-2">Nome</th>
                            <th class="px-4 py-2">Cognome</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Ruolo</th>
                            <th class="px-4 py-2">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 dark:text-gray-200">
                        @foreach($users as $user) 
                            <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600"
                                x-show="search === '' || 
                                        '{{ $user->username }}'.toLowerCase().includes(search.toLowerCase()) || 
                                        '{{ $user->name }}'.toLowerCase().includes(search.toLowerCase()) || 
                                        '{{ $user->surname }}'.toLowerCase().includes(search.toLowerCase()) || 
                                        '{{ $user->email }}'.toLowerCase().includes(search.toLowerCase()) ||
                                        '{{ $user->role }}'.toLowerCase().includes(search.toLowerCase())">
                                <td class="px-4 py-2">{{ $user->username }}</td>
                                <td class="px-4 py-2">{{ $user->name }}</td>
                                <td class="px-4 py-2">{{ $user->surname }}</td>
                                <td class="px-4 py-2">{{ $user->email }}</td>
                                <td class="px-4 py-2">{{ $user->role }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('profile.edit', $user->id) }}" 
                                    class="btn btn-primary">
                                        <i class="fa-solid fa-pen-to-square gsv-edit px-2 py-2"></i>
                                    </a>
                                    <a href="{{ route('profile.destroy', $user->id) }}" 
                                    class="btn btn-primary">
                                        <i class="fa-solid fa-ban gsv-destroy px-2 py-2"></i>
                                    </a>
                                    <a href="{{ route('profile.destroy', $user->id) }}" 
                                    class="btn btn-primary">
                                        <i class="fa-solid fa-lock gsv-secure px-2 py-2"></i>
                                    </a>
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
                    <h2 class="text-xl font-semibold mb-4">Dettagli Utente</h2>
                    
                    <p><strong>ID:</strong> <span x-text="selectedUser?.id"></span></p>
                    <p><strong>Username:</strong> <span x-text="selectedUser?.username"></span></p>
                    <p><strong>Email:</strong> <span x-text="selectedUser?.email"></span></p>
                    
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
