@csrf
<div class="gsv-timesheet-form">
    <div class="mb-4">
        <x-input-label for="company" :value="__('Azienda')" />
        <x-text-input id="company" class="block mt-1 w-full" type="text" name="company" :value="old('company', $role->company ?? '')" required autofocus />
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
<div class="mb-4" style="width: 100%; overflow-x: auto;">
    <x-timesheet-table />
</div>

<div class="gsv-timesheet-form">
    <div class="flex items-center justify-end mt-4">
        <x-primary-button class="ms-3">
            {{ __('Salva') }}
        </x-primary-button>
    </div>
</div>