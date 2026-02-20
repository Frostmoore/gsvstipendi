@props([
    'timesheet'     => null,
    'companies'     => [],
    'roles'         => [],
    'months'        => [],
    'years'         => [],
    'compensations' => [],
    'users'         => [],
])
@csrf
<div class="gsv-timesheet-form">
    <input type="hidden" id="user_id" name="user" value="{{ Auth::id() }}">
    <div class="mb-4">
        <x-input-label for="company" :value="__('Azienda')" />
        <x-select-input id="company" class="block mt-1 w-full" type="text" name="company" required autofocus>
            <option value="">Seleziona un'azienda</option>
            @foreach($companies as $company)
                <option value="{{ $company->name }}" {{ $timesheet && $timesheet->company == $company->name ? 'selected' : '' }}>
                    {{ $company->name }}
                </option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('company')" class="mt-2" />
    </div>
    <div class="mb-4">
        <x-input-label for="role" :value="__('Ruolo')" />
        <x-select-input id="role" class="block mt-1 w-full" type="text" name="role" required autofocus>
            @foreach($roles as $key => $role)
                <option value="{{ $role['role'] }}" {{ $timesheet && $timesheet->role == $role['role'] ? 'selected' : '' }}>
                    {{ $role['role'] }}
                </option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>
    <div class="mb-4">
        <x-input-label for="month" :value="__('Mese')" />
        <x-select-input id="month" class="block mt-1 w-full" type="text" name="month" required autofocus>
            @foreach($months as $key => $month)
                <option value="{{ $key }}" {{
                    $timesheet
                        ? ((string)$timesheet->month === (string)$key ? 'selected' : '')
                        : ($key == date('m') ? 'selected' : '')
                }}>{{ $month }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('month')" class="mt-2" />
    </div>
    <div class="mb-4">
        <x-input-label for="year" :value="__('Anno')" />
        <x-select-input id="year" class="block mt-1 w-full" type="text" name="year" required autofocus>
            @foreach($years as $year)
                <option value="{{ $year }}" {{
                    $timesheet
                        ? ((string)$timesheet->year === (string)$year ? 'selected' : '')
                        : ($year == date('Y') ? 'selected' : '')
                }}>{{ $year }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('year')" class="mt-2" />
    </div>
</div>
<div class="mb-4 gsv-timesheet-table-container" style="width: 100%; overflow-x: auto;">
    <x-user-timesheet-table :roles="$roles" :compensations="$compensations" :users="$users" :timesheet="$timesheet ?? null"/>
</div>

<div class="gsv-timesheet-form">
    <div class="flex items-center justify-end mt-4">
        <x-primary-button class="ms-3">
            {{ __('Salva') }}
        </x-primary-button>
    </div>
</div>
