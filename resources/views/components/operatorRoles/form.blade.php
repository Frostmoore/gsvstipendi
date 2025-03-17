@csrf
<div class="mb-4">
    <x-input-label for="operatorRole" :value="__('Nome Ruolo')" />
    <x-text-input id="operatorRole" class="block mt-1 w-full" type="text" name="operatorRole" :value="old('operatorRole', $operatorRole->operatorRole ?? '')" required />
    <x-input-error :messages="$errors->get('operatorRole')" class="mt-2" />
</div>

<div class="tabella-ruoli-container overflow-auto">
    <table class="table-fixed w-full gsv-timesheet-table">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
            <tr class="bg-gray-50">
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Retribuzione</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
            </tr>
        </thead>
        <tbody id="tableBody" class="
            text-gray-700 
            dark:text-gray-200 
            bg-gray-50
            dark:bg-gray-800
            text-gray-700
            dark:text-gray-200
        ">
            @for ($i = 0; $i < 15; $i++)
                <tr>
                    <td class="px-4 py-2"><input type="text" class="
                        text-gray-700 
                        dark:text-gray-200 
                        odd:bg-white
                        odd:dark:bg-gray-700
                        even:bg-gray-50
                        even:dark:bg-gray-800
                        hover:bg-gray-100
                        hover:dark:bg-gray-600
                    "></td>
                    <td class="px-4 py-2"><input type="text" class="
                        text-gray-700 
                        dark:text-gray-200 
                        odd:bg-white
                        odd:dark:bg-gray-700
                        even:bg-gray-50
                        even:dark:bg-gray-800
                        hover:bg-gray-100
                        hover:dark:bg-gray-600
                    "></td>
                    <td class="px-4 py-2"><input type="text" class="
                        text-gray-700 
                        dark:text-gray-200 
                        odd:bg-white
                        odd:dark:bg-gray-700
                        even:bg-gray-50
                        even:dark:bg-gray-800
                        hover:bg-gray-100
                        hover:dark:bg-gray-600
                    "></td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>

<input type="hidden" name="tableData" id="tableData">

<div class="flex items-center justify-end mt-4">
    <x-primary-button class="ms-3">
        {{ __('Salva') }}
    </x-primary-button>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('#tableBody tr');
    let data = [];

    rows.forEach(row => {
        let cells = row.querySelectorAll('input');
        data.push({
            nome: cells[0].value,
            retribuzione: cells[1].value,
            tipo: cells[2].value
        });
    });

    document.getElementById('tableData').value = JSON.stringify(data);
});
</script>