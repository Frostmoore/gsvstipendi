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
                <a href="{{ route('user-timesheets.create') }}" class="btn btn-primary mb-3">
                    <i class="fa-solid fa-plus-circle gsv-add px-2 py-2"></i>
                </a>
        </div>

            {{-- Mobile: cards --}}
            <div id="utMobileCards" class="space-y-2" style="display:none">
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
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <a href="{{ route('user-timesheets.edit', $timesheet_worked->id) }}">
                            <i class="fa-solid fa-pen-to-square gsv-edit px-2 py-2"></i>
                        </a>
                        @if(Auth::user()->role == 'admin' || Auth::user()->role == 'superadmin')
                        <form action="{{ route('user-timesheets.destroy', $timesheet_worked->id) }}" method="POST" style="display: inline;">
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

            {{-- Desktop: table --}}
            <div id="utDesktopTable" class="overflow-x-auto shadow-md rounded-lg" style="display:none">
                <table class="table-auto w-full std-table">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-2">Mese</th>
                            <th class="px-4 py-2">Anno</th>
                            <th class="px-4 py-2">Operatore</th>
                            <th class="px-4 py-2">Link</th>
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
                                <td class="px-4 py-2"><a href="{{ route('user-timesheets.edit', $timesheet_worked->id) }}"><i class="fa-solid fa-pen-to-square gsv-edit px-2 py-2"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        function applyUtLayout() {
            var mobile = document.getElementById('utMobileCards');
            var desktop = document.getElementById('utDesktopTable');
            if (window.innerWidth < 768) {
                mobile.style.display = '';
                desktop.style.display = 'none';
            } else {
                mobile.style.display = 'none';
                desktop.style.display = '';
            }
        }
        applyUtLayout();
        window.addEventListener('resize', applyUtLayout);
        </script>
    </x-std-content>
</x-app-layout>
