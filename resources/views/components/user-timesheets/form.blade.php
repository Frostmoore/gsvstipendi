@csrf
<div class="gsv-timesheet-form">
    <input type="hidden" id="user_id" name="user" value="{{ Auth::id() }}">
    <div class="mb-4">
        <x-input-label for="company" :value="__('Azienda')" />
        <x-select-input id="company" class="block mt-1 w-full" type="text" name="company" :value="old('company', $company->name ?? '')" required autofocus>
        <option value="">Seleziona un'azienda</option>
            @foreach($companies as $company)
                <option value="{{ $company->name }}">{{ $company->name }}</option>
            @endforeach
        </x-select-input>
        {{-- <x-text-input id="company" class="block mt-1 w-full" type="text" name="company" :value="old('company', $role->company ?? '')" required autofocus /> --}}
        <x-input-error :messages="$errors->get('company')" class="mt-2" />
    </div>
    <div class="mb-4">
        <x-input-label for="role" :value="__('Ruolo')" />
        <x-select-input id="role" class="block mt-1 w-full" type="text" name="role" :value="old('role', $role->role ?? '')" required autofocus>
            @foreach($roles as $key => $role)
                <option value="{{ $role['role'] }}">{{ $role['role'] }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>
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
    <x-user-timesheet-table :roles="$roles" :compensations="$compensations" :users="$users"/>
</div>

<div class="gsv-timesheet-form">
    <div class="flex items-center justify-end mt-4">
        <x-primary-button class="ms-3">
            {{ __('Salva') }}
        </x-primary-button>
    </div>
</div>