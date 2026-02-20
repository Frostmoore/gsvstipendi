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

                    select.value = dayData[col.name] || "";
                    select.addEventListener("change", function () {
                        dayData[col.name] = this.value;
                        updateHiddenInput();
                    });

                    td.appendChild(select);
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
