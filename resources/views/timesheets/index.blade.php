<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestione Fogli Orari') }}
        </h2>
    </x-slot>
        
        
    <x-std-content>
        <div class="title-container mb-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Elenco Fogli Orari') }}
            </h2>
        </div>

        <!-- Barra di ricerca con filtraggio dinamico -->
        <div x-data="{ search: '', showModal: false, selectedRole: null }">
        <div class="gsv-row-search">
            <input 
                type="text" 
                x-model.debounce.200ms="search"
                placeholder="Cerca fogli orari..." 
                class="px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 w-full mb-4 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 gsv-search"
            />
                <a href="{{ route('timesheets.create') }}" class="btn btn-primary mb-3">
                    <i class="fa-solid fa-plus-circle gsv-add px-2 py-2"></i>
                </a>
        </div>

            {{-- Mobile: cards (hidden on md+) --}}
            <div class="md:hidden space-y-2">
                @foreach($timesheets_worked as $timesheet_worked)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 flex items-center justify-between gap-2"
                     x-show="search === '' ||
                             '{{ $timesheet_worked->month }}'.toLowerCase().includes(search.toLowerCase()) ||
                             '{{ $timesheet_worked->year }}'.toLowerCase().includes(search.toLowerCase()) ||
                             '{{ $timesheet_worked->user_fullname }}'.toLowerCase().includes(search.toLowerCase()) ||
                             '{{ $timesheet_worked->link }}'.toLowerCase().includes(search.toLowerCase())">
                    <div class="min-w-0">
                        <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ $timesheet_worked->month }} {{ $timesheet_worked->year }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $timesheet_worked->user_fullname }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $timesheet_worked->role }}</p>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <a href="{{ route('timesheets.show', $timesheet_worked->id) }}">
                            <i class="fa-solid fa-file-invoice gsv-document px-2 py-2"></i>
                        </a>
                        @if(Auth::user()->role == 'admin' || Auth::user()->role == 'superadmin')
                        <form action="{{ route('timesheets.destroy', $timesheet_worked->id) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-ban px-2 py-2 gsv-destroy"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Desktop: table (hidden on mobile) --}}
            <div class="hidden md:block overflow-x-auto shadow-md rounded-lg">
                <table class="table-auto w-full std-table">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-2">Mese</th>
                            <th class="px-4 py-2">Anno</th>
                            <th class="px-4 py-2">Operatore</th>
                            <th class="px-4 py-2">Ruolo</th>
                            <th class="px-4 py-2">Link</th>
                            <th class="px-4 py-2">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 dark:text-gray-200">
                        @foreach($timesheets_worked as $timesheet_worked)
                            <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600"
                                x-show="search === '' ||
                                        '{{ $timesheet_worked->month }}'.toLowerCase().includes(search.toLowerCase()) ||
                                        '{{ $timesheet_worked->year }}'.toLowerCase().includes(search.toLowerCase()) ||
                                        '{{ $timesheet_worked->user_fullname }}'.toLowerCase().includes(search.toLowerCase()) ||
                                        '{{ $timesheet_worked->link }}'.toLowerCase().includes(search.toLowerCase())">
                                <td class="px-4 py-2">{{ $timesheet_worked->month }}</td>
                                <td class="px-4 py-2">{{ $timesheet_worked->year }}</td>
                                <td class="px-4 py-2">{{ $timesheet_worked->user_fullname }}</td>
                                <td class="px-4 py-2">{{ $timesheet_worked->role }}</td>
                                <td class="px-4 py-2"><a href="{{ route('timesheets.show', $timesheet_worked->id) }}"><i class="fa-solid fa-file-invoice gsv-document px-2 py-2"></i></a></td>
                                <td class="px-4 py-2">
                                    @if(Auth::user()->role == 'admin' || Auth::user()->role == 'superadmin')
                                    <form action="{{ route('timesheets.destroy', $timesheet_worked->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fa-solid fa-ban px-2 py-2 gsv-destroy"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-std-content>
</x-app-layout>
