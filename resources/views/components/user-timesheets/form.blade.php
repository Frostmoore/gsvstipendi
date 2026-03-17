@props([
    'timesheet' => null,
    'companies' => [],
    'months'    => [],
    'years'     => [],
    'users'     => [],
    'userRates' => null,
])
@php
    $bonus_list_edit = $timesheet ? (is_array($timesheet->bonuses) ? $timesheet->bonuses : (json_decode($timesheet->bonuses ?? '[]', true) ?? [])) : [];
@endphp
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
    <input type="hidden" name="role" value="user">
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

{{-- Aggiunte/Detrazioni — parte del form principale, si salva con "Salva" --}}
@if($timesheet)
<fieldset class="w-full overflow-hidden border border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-6">
    <legend class="px-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Aggiunte e Detrazioni</legend>

    <div class="flex flex-col md:flex-row gap-3 md:items-end mb-4">
        <div class="w-full md:w-40">
            <x-input-label for="edit_bonus_amount" :value="__('Importo (€)')" />
            <x-text-input id="edit_bonus_amount" class="block mt-1 w-full" type="number" step="0.01" placeholder="es. 50 o -30" />
        </div>
        <div class="w-full md:flex-1">
            <x-input-label for="edit_bonus_note" :value="__('Motivazione')" />
            <x-text-input id="edit_bonus_note" class="block mt-1 w-full" type="text" placeholder="es. Rimborso spese..." />
        </div>
        <div class="w-full md:w-auto md:flex-shrink-0 md:pb-0.5">
            <button type="button" id="edit_add_bonus_btn"
                class="w-full md:w-auto inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none transition ease-in-out duration-150">
                + Aggiungi
            </button>
        </div>
    </div>

    <div id="edit_bonus_list_container"></div>
    <input type="hidden" name="bonuses" id="edit_bonuses_hidden" value="{{ json_encode($bonus_list_edit) }}" />
</fieldset>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bonusContainer = document.getElementById('edit_bonus_list_container');
        let bonusEntries = JSON.parse(document.getElementById('edit_bonuses_hidden').value || '[]');

        function renderBonusList() {
            document.getElementById('edit_bonuses_hidden').value = JSON.stringify(bonusEntries);

            if (bonusEntries.length === 0) {
                bonusContainer.innerHTML =
                    '<p class="text-sm text-gray-400 dark:text-gray-500 italic">Nessuna aggiunta o detrazione.</p>';
                return;
            }

            if (window.innerWidth < 768) {
                let html = '<div class="space-y-2">';
                bonusEntries.forEach(function(entry, index) {
                    const isBonus = parseFloat(entry.amount) >= 0;
                    const colorClass = isBonus
                        ? 'bg-green-50 dark:bg-green-950 text-green-800 dark:text-green-200 border-green-300 dark:border-green-800'
                        : 'bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border-red-300 dark:border-red-800';
                    const sign   = isBonus ? '+' : '';
                    const amount = sign + parseFloat(entry.amount).toFixed(2) + ' €';
                    html += `<div class="flex items-center gap-2 rounded-lg border p-3 ${colorClass}">`;
                    html += `<span class="font-bold text-sm w-20 flex-shrink-0">${amount}</span>`;
                    html += `<span class="text-sm flex-1 min-w-0 break-words">${entry.note}</span>`;
                    html += `<button type="button" onclick="editRemoveBonus(${index})" class="text-xs hover:underline flex-shrink-0">Elimina</button>`;
                    html += `</div>`;
                });
                html += '</div>';
                bonusContainer.innerHTML = html;
            } else {
                let html = '<table class="w-full text-sm border-collapse rounded overflow-hidden">';
                html += '<thead><tr class="text-left bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">';
                html += '<th class="px-3 py-1.5 w-28">Importo</th>';
                html += '<th class="px-3 py-1.5">Motivazione</th>';
                html += '<th class="px-3 py-1.5 w-20"></th>';
                html += '</tr></thead><tbody>';
                bonusEntries.forEach(function(entry, index) {
                    const isBonus  = parseFloat(entry.amount) >= 0;
                    const rowClass = isBonus
                        ? 'bg-green-50 dark:bg-green-950 text-green-800 dark:text-green-200'
                        : 'bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200';
                    const sign   = isBonus ? '+' : '';
                    const amount = sign + parseFloat(entry.amount).toFixed(2) + ' €';
                    html += `<tr class="${rowClass} border-t border-gray-200 dark:border-gray-600">`;
                    html += `<td class="px-3 py-1.5 font-semibold">${amount}</td>`;
                    html += `<td class="px-3 py-1.5">${entry.note}</td>`;
                    html += `<td class="px-3 py-1.5 text-right">`;
                    html += `<button type="button" onclick="editRemoveBonus(${index})" class="text-xs text-red-600 dark:text-red-400 hover:underline">Elimina</button>`;
                    html += `</td></tr>`;
                });
                html += '</tbody></table>';
                bonusContainer.innerHTML = html;
            }
        }

        window.editRemoveBonus = function(index) {
            bonusEntries.splice(index, 1);
            renderBonusList();
        };

        document.getElementById('edit_add_bonus_btn').addEventListener('click', function() {
            const amountInput = document.getElementById('edit_bonus_amount');
            const noteInput   = document.getElementById('edit_bonus_note');
            const amount      = parseFloat(amountInput.value);
            const note        = noteInput.value.trim();

            if (isNaN(amount) || amount === 0) { amountInput.focus(); return; }
            if (!note) { noteInput.focus(); return; }

            bonusEntries.push({ amount: amount, note: note });
            amountInput.value = '';
            noteInput.value = '';
            renderBonusList();
        });

        renderBonusList();
    });
</script>
@endif

<div class="mb-4 gsv-timesheet-table-container" style="width: 100%; overflow-x: auto;">
    <x-user-timesheet-table :users="$users" :timesheet="$timesheet ?? null" :userRates="$userRates" />
</div>

<div class="pb-20"></div>

<button type="submit"
    class="fixed bottom-6 right-6 z-50 inline-flex items-center gap-2 px-5 py-3 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 font-semibold text-sm rounded-full shadow-xl hover:bg-gray-700 dark:hover:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    Salva
</button>
