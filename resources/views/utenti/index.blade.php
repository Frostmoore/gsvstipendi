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
        <div x-data="{ search: '' }">
        <div class="gsv-row-search">
            <input 
                type="text" 
                x-model.debounce.200ms="search"
                placeholder="Cerca utenti..." 
                class="px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 w-full mb-4 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 gsv-search"
            />
            <a href="{{ route('utenti.create') }}" class="btn btn-primary mb-3">
                <i class="fa-solid fa-plus-circle gsv-add px-2 py-2"></i>
            </a>
        </div>

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="table-auto w-full std-table">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-2">User Name</th>
                            <th class="px-4 py-2">Cognome</th>
                            <th class="px-4 py-2">Nome</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Ruolo</th>
                            <th class="px-4 py-2">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 dark:text-gray-200">
                        @foreach($users as $utente) 
                            @if($utente->role == 'superadmin' && Auth::user()->role != 'superadmin')
                                @continue
                            @elseif($utente->role == 'admin' && (Auth::user()->role != 'superadmin' && Auth::user()->role != 'admin'))
                                @continue
                            @else
                                <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600"
                                    x-show="search === '' || 
                                            '{{ $utente->username }}'.toLowerCase().includes(search.toLowerCase()) || 
                                            '{{ $utente->name }}'.toLowerCase().includes(search.toLowerCase()) || 
                                            '{{ $utente->surname }}'.toLowerCase().includes(search.toLowerCase()) ||
                                            '{{ $utente->role }}'.toLowerCase().includes(search.toLowerCase()) || 
                                            '{{ $utente->email }}'.toLowerCase().includes(search.toLowerCase())">
                                    <td class="px-4 py-2">{{ $utente->username }}</td>
                                    <td class="px-4 py-2">{{ $utente->surname }}</td>
                                    <td class="px-4 py-2">{{ $utente->name }}</td>
                                    <td class="px-4 py-2">{{ $utente->email }}</td>
                                    <td class="px-4 py-2">{{ $utente->role }}</td>
                                    <td class="px-4 py-2">
                                        <a href="{{ route('utenti.edit', $utente) }}" 
                                        class="btn btn-primary">
                                            <i class="fa-solid fa-pen-to-square gsv-edit px-2 py-2"></i>
                                        </a>
                                        <form action="{{ route('utenti.passwordReset', $utente) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fa-solid fa-lock gsv-secure px-2 py-2"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('utenti.destroy', $utente) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fa-solid fa-ban px-2 py-2 gsv-destroy"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-std-content>
</x-app-layout>
