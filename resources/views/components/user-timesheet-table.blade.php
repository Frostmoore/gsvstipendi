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
    .note-pills {
        display: flex;
        flex-direction: column;
        gap: 3px;
        padding: 3px;
    }
    .note-pill {
        display: flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #9ca3af;
        transition: background 0.1s, color 0.1s, border-color 0.1s;
        user-select: none;
        white-space: nowrap;
    }
    .note-pill input[type="checkbox"] { display: none; }
    .note-pill-blue.note-pill-active   { background: #3b82f6; color: #ffffff; border-color: #2563eb; }
    .note-pill-yellow.note-pill-active { background: #f59e0b; color: #ffffff; border-color: #d97706; }
    .note-pill-red.note-pill-active    { background: #ef4444; color: #ffffff; border-color: #dc2626; }
    .note-pill-purple.note-pill-active { background: #8b5cf6; color: #ffffff; border-color: #7c3aed; }
    .note-pill-blue:hover   { background: #dbeafe; color: #1e40af; border-color: #93c5fd; }
    .note-pill-yellow:hover { background: #fef3c7; color: #92400e; border-color: #fde68a; }
    .note-pill-red:hover    { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    .note-pill-purple:hover { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }
    .dark .note-pill { background: #1e293b; color: #64748b; border-color: #334155; }
    .dark .note-pill-blue.note-pill-active   { background: #3b82f6; color: #ffffff; border-color: #2563eb; }
    .dark .note-pill-yellow.note-pill-active { background: #f59e0b; color: #ffffff; border-color: #d97706; }
    .dark .note-pill-red.note-pill-active    { background: #ef4444; color: #ffffff; border-color: #dc2626; }
    .dark .note-pill-purple.note-pill-active { background: #8b5cf6; color: #ffffff; border-color: #7c3aed; }
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
                    const NOTE_COLORS = {
                        "Ferie": "note-pill-blue",
                        "Permesso": "note-pill-yellow",
                        "Malattia": "note-pill-red",
                        "104": "note-pill-purple"
                    };
                    let wrapper = document.createElement("div");
                    wrapper.classList.add("note-pills");
                    NOTE_OPTIONS.forEach(opt => {
                        let label = document.createElement("label");
                        label.classList.add("note-pill", NOTE_COLORS[opt] || "note-pill-gray");
                        let checkbox = document.createElement("input");
                        checkbox.type = "checkbox";
                        checkbox.value = opt;
                        checkbox.classList.add("note-check");
                        checkbox.addEventListener("change", function () {
                            if (this.checked) label.classList.add("note-pill-active");
                            else label.classList.remove("note-pill-active");
                            updateHiddenInput();
                        });
                        label.appendChild(checkbox);
                        label.appendChild(document.createTextNode(opt));
                        wrapper.appendChild(label);
                    });
                    td.appendChild(wrapper);
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
                        if (val) {
                            let selectedVals = String(val).split(",").map(v => v.trim()).filter(v => v);
                            td.querySelectorAll("input.note-check").forEach(cb => {
                                cb.checked = selectedVals.includes(cb.value);
                                if (cb.checked) cb.closest("label").classList.add("note-pill-active");
                            });
                        }
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
                    let checks = cell.querySelectorAll("input.note-check:checked");
                    cellValue = Array.from(checks).map(c => c.value).join(",");
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

            if (nextCell.querySelector("input.note-check")) {
                nextCell.querySelector("input.note-check").focus();
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
