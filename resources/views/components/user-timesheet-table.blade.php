@props([
    'timesheet'     => null,
    'roles'         => [],
    'compensations' => [],
    'users'         => [],
])
<x-input-label for="editableTable" :value="__('Foglio Orario')" />
<div class="gsv-description-container mb-4">
    <p class="text-xs text-gray-800 dark:text-gray-200 leading-tight" style="color:orange;">
        {{ __('Istruzioni per la compilazione del Foglio Orario:') }}
    </p>
    <p class="text-xs text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('1. Puoi lasciare bianchi i campi Cliente e Luogo e questi prenderanno il valore del giorno precedente') }}
    </p>
    <p class="text-xs text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('2. Cliccando accanto ai trattini, nei campi Entrata e Uscita, apparirà il Time Selector') }}
    </p>
    <p class="text-xs text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('3. Seleziona un solo campo Trasferta') }}
    </p>
    <p class="text-xs text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('4. Per Trasf. Breve si intende fino a 200 Km. Per Trasf. Lunga, si intende oltre i 200 Km') }}
    </p>
    <p class="text-xs text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('5. Per i Magazzinieri FIGC, è sufficiente spuntare il campo Estero, se si è stati all\'Estero') }}
    </p>
</div>
<div class="shadow-md rounded-lg">
    <table id="editableTable" class="table-fixed w-full gsv-timesheet-table">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
            <tr>
                <th>Data</th>
                <th>Cliente</th>
                <th>Luogo</th>
                <th>Entrata</th>
                <th>Uscita</th>
                <th>Trasf. Breve</th>
                <th>Trasf. Lunga</th>
                <th>Pernotto</th>
                <th>Presidio</th>
                <th>Estero</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody class="text-gray-700 dark:text-gray-200">
        </tbody>
    </table>

    <!-- Campo nascosto per memorizzare i dati JSON -->
    <input type="hidden" name="link" id="link" value="">
</div>

<style>
    /* Dice al browser di usare i colori nativi scuri per tutti i controlli form in dark mode */
    html.dark { color-scheme: dark; }

    .note-select {
        width: 100%;
        font-size: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        background: transparent;
        padding: 2px 4px;
        color: inherit;
    }
    .dark .note-select {
        border-color: #4b5563;
    }
</style>

<script>

document.addEventListener("DOMContentLoaded", function () {
    let tableBody = document.querySelector("#editableTable tbody");
    let tableHead = document.querySelector('thead');
    let monthSelect = document.getElementById("month");
    let yearSelect = document.getElementById("year");
    let hiddenInput = document.getElementById("link");
    let userSelect = document.getElementById("user-select");
    let il_ruolo = document.getElementById("role");
    let ruolo_name = il_ruolo.value;

    // Dati esistenti da caricare al primo render (null in modalità creazione)
    let existingData = <?= json_encode($timesheet ? json_decode($timesheet->link, true) : null) ?>;
    let hasLoadedExisting = false;

    const NOTE_OPTIONS = ["Ferie", "Permesso", "Malattia", "104"];

    let roles = JSON.parse(`<?= json_encode($roles); ?>`);
    let utenti = JSON.parse(`<?= json_encode($users); ?>`);

    let ruolo = 0;
    let columns = [];

    function updateRoleAndGenerateTable() {

        // Aggiorna le colonne in base al ruolo aggiornato
        if (ruolo_name === 'Autista') {
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Entrata", type: "time", editable: true },
                { name: "Uscita", type: "time", editable: true },
                { name: "TrasfLunga", type: "checkbox", editable: false },
                { name: "Pernotto", type: "checkbox", editable: false },
                { name: "Presidio", type: "checkbox", editable: false },
                { name: "Note", type: "multiselect", editable: false }
            ];
        } else if (ruolo_name === 'Magazziniere FIGC') {
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Estero", type: "checkbox", editable: false },
                { name: "Note", type: "multiselect", editable: false }
            ];
        } else if (ruolo_name === 'Facchino') {
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Entrata", type: "time", editable: true },
                { name: "Uscita", type: "time", editable: true },
                { name: "Trasferta", type: "checkbox", editable: false },
                { name: "Note", type: "multiselect", editable: false }
            ];
        } else if (ruolo_name === 'Superadmin') {
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Entrata", type: "time", editable: true },
                { name: "Uscita", type: "time", editable: true },
                { name: "Trasferta", type: "checkbox", editable: false },
                { name: "TrasfLunga", type: "checkbox", editable: false },
                { name: "Pernotto", type: "checkbox", editable: false },
                { name: "Presidio", type: "checkbox", editable: false },
                { name: "Estero", type: "checkbox", editable: false },
                { name: "Note", type: "multiselect", editable: false }
            ];
        } else {
            // Caso default
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Entrata", type: "time", editable: true },
                { name: "Uscita", type: "time", editable: true },
                { name: "Trasferta", type: "checkbox", editable: false },
                { name: "TrasfLunga", type: "checkbox", editable: false },
                { name: "Pernotto", type: "checkbox", editable: false },
                { name: "Presidio", type: "checkbox", editable: false },
                { name: "Estero", type: "checkbox", editable: false },
                { name: "Note", type: "multiselect", editable: false }
            ];
        }
        console.log("Colonne aggiornate:", columns);

        // Rigenera la tabella
        generateTable();
    }


    // Funzione per generare la tabella
    function generateTable() {
        let table = document.getElementById("editableTable");
        table.innerHTML = "";

        tableHead = document.createElement("thead");
        tableHead.classList.add("bg-gray-50", "dark:bg-gray-800", "text-gray-700", "dark:text-gray-200");
        tableBody = document.createElement("tbody");
        tableBody.classList.add("text-gray-700", "dark:text-gray-200");
        table.appendChild(tableHead);
        table.appendChild(tableBody);

        let headerRow = document.createElement("tr");
        columns.forEach(col => {
            let th = document.createElement("th");
            th.textContent = col.name;
            headerRow.appendChild(th);
        });
        tableHead.appendChild(headerRow);

        generateTableRows();
    }


    // Funzione per generare le righe della tabella
    function generateTableRows() {
        let month = parseInt(monthSelect.value);
        let year = parseInt(yearSelect.value);

        tableBody.innerHTML = "";

        let daysInMonth = new Date(year, month, 0).getDate();

        for (let day = 1; day <= daysInMonth; day++) {
            let date = new Date(year, month - 1, day);
            let dayOfWeek = date.toLocaleDateString("it-IT", { weekday: "long" });
            let formattedDate = `<strong>${capitalizeFirstLetter(dayOfWeek)}</strong> <span>${day}</span>`;

            let row = document.createElement("tr");

            columns.forEach(col => {
                let td = document.createElement("td");

                if (col.name === "Data") {
                    td.innerHTML = formattedDate;
                    td.style.textAlign = "left";
                } else if (col.type === "multiselect") {
                    let select = document.createElement("select");
                    select.classList.add("note-select");

                    let emptyOpt = document.createElement("option");
                    emptyOpt.value = "";
                    emptyOpt.textContent = "—";
                    emptyOpt.style.color = "black";
                    emptyOpt.style.backgroundColor = "white";
                    select.appendChild(emptyOpt);

                    NOTE_OPTIONS.forEach(opt => {
                        let option = document.createElement("option");
                        option.value = opt;
                        option.textContent = opt;
                        option.style.color = "black";
                        option.style.backgroundColor = "white";
                        select.appendChild(option);
                    });

                    select.addEventListener("change", updateHiddenInput);
                    td.appendChild(select);
                } else if (col.type === "checkbox") {
                    let input = document.createElement("input");
                    input.type = "checkbox";
                    input.addEventListener("change", updateHiddenInput);
                    td.style.textAlign = "center";
                    td.appendChild(input);
                } else if (col.type === "time") {
                    let input = document.createElement("input");
                    input.type = "time";
                    input.classList.add("odd:bg-white", "odd:dark:bg-gray-700", "even:bg-gray-50", "even:dark:bg-gray-800", "hover:bg-gray-100", "hover:dark:bg-gray-600");
                    input.classList.add("time-column");
                    input.addEventListener("input", function () {
                        propagateValue(this, col.name);
                    });
                    td.appendChild(input);
                } else {
                    td.contentEditable = col.editable;
                    if (col.name === "Cliente") {
                        td.classList.add("cliente-column");
                        td.addEventListener("input", function () {
                            propagateValue(this, col.name);
                        });
                    }

                    if (col.name === "Luogo") {
                        td.classList.add("luogo-column");
                        td.addEventListener("input", function () {
                            propagateValue(this, col.name);
                        });
                    }
                }

                row.appendChild(td);
            });

            tableBody.appendChild(row);
        }

        // Pre-popola le righe con i dati salvati (solo al primo caricamento)
        if (!hasLoadedExisting && existingData && existingData.length > 0) {
            document.querySelectorAll("#editableTable tbody tr").forEach((row, rowIndex) => {
                let rowData = existingData[rowIndex];
                if (!rowData) return;

                row.querySelectorAll("td").forEach((td, colIndex) => {
                    let col = columns[colIndex];
                    if (!col || col.name === "Data") return;

                    let val = rowData[col.name];
                    if (val === undefined || val === null) return;

                    if (col.type === "multiselect") {
                        let sel = td.querySelector("select.note-select");
                        if (sel) sel.value = val || "";
                    } else if (col.type === "checkbox") {
                        let cb = td.querySelector("input[type='checkbox']");
                        if (cb) cb.checked = (val === "1" || val === 1 || val === true);
                    } else if (col.type === "time") {
                        let inp = td.querySelector("input[type='time']");
                        if (inp) inp.value = val;
                    } else {
                        td.innerText = val;
                    }
                });
            });
            hasLoadedExisting = true;
        }

        updateHiddenInput();
    }

    function propagateValue(element, columnName) {
        let value = element.innerText ? element.innerText.trim() : element.value;
        let columnIndex = Array.from(element.closest("tr").children).indexOf(element.closest("td"));
        updateHiddenInput();
    }

    // Funzione per aggiornare l'input nascosto con i dati della tabella
    function updateHiddenInput() {
        let tableData = [];

        document.querySelectorAll("#editableTable tbody tr").forEach(row => {
            let rowData = {};
            row.querySelectorAll("td").forEach((cell, index) => {
                let columnName = columns[index].name;
                let col = columns[index];
                let cellValue = "";

                if (col.type === "multiselect") {
                    let sel = cell.querySelector("select.note-select");
                    cellValue = sel ? sel.value : "";
                } else if (cell.querySelector("input[type='checkbox']")) {
                    cellValue = cell.querySelector("input[type='checkbox']").checked ? "1" : "0";
                } else if (cell.querySelector("input[type='time']")) {
                    cellValue = cell.querySelector("input[type='time']").value;
                } else {
                    cellValue = cell.innerText.trim();
                }

                rowData[columnName] = cellValue;
            });

            tableData.push(rowData);
        });

        let jsonData = JSON.stringify(tableData);
        hiddenInput.value = jsonData;

        console.log(ruolo_name);
        console.log("Modifica rilevata:", jsonData);
    }

    // Funzione per capitalizzare la prima lettera di una stringa
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    // Eventi per rigenerare la tabella quando cambiano i selettori
    il_ruolo.addEventListener("change", function(){
        ruolo_name = il_ruolo.value;
        updateRoleAndGenerateTable();
    });
    monthSelect.addEventListener("change", generateTable);
    yearSelect.addEventListener("change", generateTable);

    // Genera la tabella iniziale
    updateRoleAndGenerateTable();


    //***********************************************************************//
    //************************** Movimento Celle ****************************//
    //***********************************************************************//

    let table = document.getElementById("editableTable");

    table.addEventListener("keydown", function (event) {
        let target = event.target;
        if (target.tagName !== "TD" && target.tagName !== "INPUT" && target.tagName !== "SELECT") return;

        let currentRow = target.closest("tr");
        let currentCellIndex = Array.from(currentRow.children).indexOf(target.closest("td"));
        let currentRowIndex = Array.from(table.rows).indexOf(currentRow);

        switch (event.key) {
            case "ArrowRight":
                moveToCell(currentRowIndex, currentCellIndex + 1);
                event.preventDefault();
                break;
            case "ArrowLeft":
                moveToCell(currentRowIndex, currentCellIndex - 1);
                event.preventDefault();
                break;
            case "ArrowDown":
                moveToCell(currentRowIndex + 1, currentCellIndex);
                event.preventDefault();
                break;
            case "ArrowUp":
                moveToCell(currentRowIndex - 1, currentCellIndex);
                event.preventDefault();
                break;
            case "Tab":
                event.preventDefault();
                if (currentCellIndex < currentRow.children.length - 1) {
                    moveToCell(currentRowIndex, currentCellIndex + 1);
                } else {
                    moveToCell(currentRowIndex + 1, 0);
                }
                break;
            case "Enter":
                event.preventDefault();
                moveToCell(currentRowIndex + 1, 0);
                break;
        }
    });

    function selectText(element) {
        let range = document.createRange();
        let selection = window.getSelection();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function moveToCell(row, cell) {
        if (row >= 1 && row < table.rows.length && cell >= 0 && cell < table.rows[row].cells.length) {
            let nextCell = table.rows[row].cells[cell];

            if (nextCell.querySelector("select.note-select")) {
                nextCell.querySelector("select.note-select").focus();
            } else if (nextCell.querySelector("input[type='checkbox']")) {
                nextCell.querySelector("input[type='checkbox']").focus();
            } else if (nextCell.querySelector("input[type='time']")) {
                nextCell.querySelector("input[type='time']").focus();
            } else {
                nextCell.focus();
                selectText(nextCell);
            }
        }
    }
});




</script>
