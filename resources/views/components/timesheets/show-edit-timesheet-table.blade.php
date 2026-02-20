<?php

use App\Helpers\DateHelper;

$id = $timesheet->id;
$_month = $timesheet->month;
$month = $months[$_month];
$year = $timesheet->year;
$userid = $timesheet->user;
$ruolo = $timesheet->role;
$timesheet = json_decode($timesheet->link);
$compensi = [];
?>


<div class="shadow-md rounded-lg">
    <table id="editableTable" class="table-fixed w-full gsv-timesheet-table">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
            <tr>
                <th>Data</th>
                <th>Cliente</th>
                <th>Luogo</th>
                <th>Entrata</th>
                <th>Uscita</th>
                <th>Trasf. Lunga</th>
                <th>Trasf. Breve</th>
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
    <input type="hidden" name="link" id="link" value="{{ old('link', json_encode($timesheet)) }}">
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
    let roleSelect = document.getElementById("role");

    const NOTE_OPTIONS = ["Ferie", "Permesso", "Malattia", "104"];

    let ruolo = "<?=$ruolo?>";
    let ruolo_name = '';
    let ruolo_right = '<?=$ruolo?>';
    let columns = [];

    function updateRoleAndGenerateTable() {

        // Aggiorna le colonne in base al ruolo aggiornato
        console.log("Ruolo: " + ruolo_right);
        if (ruolo_right === 'Autista') {
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Entrata", type: "time", editable: true },
                { name: "Uscita", type: "time", editable: true },
                { name: "TrasfLunga", type: "checkbox", editable: false },
                { name: "TrasfBreve", type: "checkbox", editable: false },
                { name: "Pernotto", type: "checkbox", editable: false },
                { name: "Presidio", type: "checkbox", editable: false },
                { name: "Note", type: "multiselect", editable: false }
            ];
        } else if (ruolo_right === 'Magazziniere FIGC') {
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Estero", type: "checkbox", editable: false },
                { name: "Note", type: "multiselect", editable: false }
            ];
        } else if (ruolo_right === 'Facchino') {
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Entrata", type: "time", editable: true },
                { name: "Uscita", type: "time", editable: true },
                { name: "Trasferta", type: "checkbox", editable: false },
                { name: "Note", type: "multiselect", editable: false }
            ];
        } else if (ruolo_right === 'Superadmin') {
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
        let timesheet = '<?= json_encode($timesheet) ?>';
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

        generaTabellaDaDati(timesheet);
    }

    // Popola la tabella partendo dai dati JSON esistenti
    function generaTabellaDaDati(timesheetData) {
        if (typeof timesheetData === 'string') {
            timesheetData = JSON.parse(timesheetData);
        }

        tableBody.innerHTML = "";

        timesheetData.forEach((dayData) => {
            let row = document.createElement("tr");
            row.classList.add("odd:bg-white", "odd:dark:bg-gray-700", "even:bg-gray-50", "even:dark:bg-gray-800", "even:color-gray-700", "dark:text-gray-200");

            columns.forEach((col) => {
                let td = document.createElement("td");

                if (col.name === "Data") {
                    td.innerHTML = dayData[col.name];
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

                    let savedVals = dayData[col.name]
                        ? String(dayData[col.name]).split(",").map(v => v.trim()).filter(v => v)
                        : [];

                    NOTE_OPTIONS.forEach(opt => {
                        let label = document.createElement("label");
                        label.classList.add("note-pill", NOTE_COLORS[opt] || "note-pill-gray");
                        if (savedVals.includes(opt)) label.classList.add("note-pill-active");

                        let checkbox = document.createElement("input");
                        checkbox.type = "checkbox";
                        checkbox.value = opt;
                        checkbox.classList.add("note-check");
                        checkbox.checked = savedVals.includes(opt);

                        checkbox.addEventListener("change", function () {
                            if (this.checked) label.classList.add("note-pill-active");
                            else label.classList.remove("note-pill-active");
                            let selected = Array.from(wrapper.querySelectorAll("input.note-check:checked")).map(c => c.value);
                            dayData[col.name] = selected.join(",");
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
                    input.checked = dayData[col.name] === "1";
                    input.addEventListener("change", function () {
                        dayData[col.name] = this.checked ? "1" : "0";
                        updateHiddenInput();
                    });
                    td.appendChild(input);
                } else if (col.name === "Entrata" || col.name === "Uscita") {
                    let input = document.createElement("input");
                    input.type = "time";
                    input.value = dayData[col.name] || "";
                    input.classList.add("time-column");
                    input.classList.add("odd:bg-white", "odd:dark:bg-gray-700", "even:bg-gray-50", "even:dark:bg-gray-800", "hover:bg-gray-100", "hover:dark:bg-gray-600");
                    input.addEventListener("input", function () {
                        dayData[col.name] = this.value;
                        updateHiddenInput();
                    });
                    td.appendChild(input);
                } else {
                    td.contentEditable = true;
                    td.textContent = dayData[col.name] || "";
                    td.classList.add("editable-cell");
                    td.addEventListener("input", function () {
                        dayData[col.name] = td.textContent;
                        updateHiddenInput();
                    });
                }

                row.appendChild(td);
            });

            tableBody.appendChild(row);
        });
    }


    function propagateValue(element, columnName) {
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
