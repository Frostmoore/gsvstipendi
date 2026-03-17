@csrf
<div class="gsv-timesheet-form">
    <input type="hidden" name="role" value="user">
    <div class="mb-4">
        <x-input-label for="company" :value="__('Azienda')" />
        <x-select-input id="company" class="block mt-1 w-full" name="company" required autofocus>
            <option value="">Seleziona un'azienda</option>
            @foreach($companies as $company)
                <option value="{{ $company->name }}">{{ $company->name }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('company')" class="mt-2" />
    </div>
    <x-user-search />
    <div class="mb-4">
        <x-input-label for="month" :value="__('Mese')" />
        <x-select-input id="month" class="block mt-1 w-full" type="text" name="month" :value="old('month', $role->month ?? '')" required autofocus>
            @foreach($months as $key => $month)
                <option value="{{ $key }}" {{$key == date('m') ? 'selected' : ''}} >{{ $month }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('month')" class="mt-2" />
    </div>
    <div class="mb-4">
        <x-input-label for="year" :value="__('Anno')" />
        <x-select-input id="year" class="block mt-1 w-full" type="text" name="year" :value="old('year', $role->year ?? '')" required autofocus>
            @foreach($years as $year)
                <option value="{{ $year }}" {{$year == date('Y') ? 'selected' : ''}} >{{ $year }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('year')" class="mt-2" />
    </div>
</div>
<div class="mb-4 gsv-timesheet-table-container" style="width: 100%; overflow-x: auto;">
    <x-timesheet-table :users="$users" :usersRates="$usersRates ?? []" />
</div>

<div class="pb-20"></div>

<button type="submit"
    class="fixed bottom-6 right-6 z-50 inline-flex items-center gap-2 px-5 py-3 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 font-semibold text-sm rounded-full shadow-xl hover:bg-gray-700 dark:hover:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    Salva
</button>