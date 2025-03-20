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
            </tr>
        </thead>
        <tbody class="text-gray-700 dark:text-gray-200">
        </tbody>
    </table>

    <!-- Campo nascosto per memorizzare i dati JSON -->
    <input type="hidden" name="link" id="link" value="">
</div>

<script>

document.addEventListener("DOMContentLoaded", function () {
    let tableBody = document.querySelector("#editableTable tbody");
    let tableHead = document.querySelector('thead');
    let monthSelect = document.getElementById("month");
    let yearSelect = document.getElementById("year");
    let hiddenInput = document.getElementById("link");
    let userSelect = document.getElementById("user-select");
    let roleSelect = document.getElementById("role");
    

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
                { name: "Presidio", type: "checkbox", editable: false }
            ];
        } else if (ruolo_right === 'Magazziniere FIGC') {
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Estero", type: "checkbox", editable: false }
            ];
        } else if (ruolo_right === 'Facchino') {
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Entrata", type: "time", editable: true },
                { name: "Uscita", type: "time", editable: true },
                { name: "Trasferta", type: "checkbox", editable: false }
            ];
        } else if (ruolo_right === 'Superadmin') {
            // Definisci le colonne per Superadmin (oppure usa quelle di default)
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
                { name: "Estero", type: "checkbox", editable: false }
            ];
        } else {
            // Caso default, se necessario
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
                { name: "Estero", type: "checkbox", editable: false }
            ];
        }
        console.log("Colonne aggiornate:", columns);

        // Rigenera la tabella
        generateTable();
    }


    // Funzione per generare la tabella
    function generateTable() {
        // Prendi l'elemento tabella e ricrea interamente i suoi figli
        let timesheet = '<?= json_encode($timesheet) ?>';
        let table = document.getElementById("editableTable");
        table.innerHTML = ""; // Rimuove tutti i nodi figli

        // Crea nuovi elementi thead e tbody
        tableHead = document.createElement("thead");
        tableHead.classList.add("bg-gray-50", "dark:bg-gray-800", "text-gray-700", "dark:text-gray-200");
        tableBody = document.createElement("tbody");
        tableBody.classList.add("text-gray-700", "dark:text-gray-200");
        table.appendChild(tableHead);
        table.appendChild(tableBody);

        // Crea l'intestazione della tabella
        let headerRow = document.createElement("tr");
        columns.forEach(col => {
            let th = document.createElement("th");
            th.textContent = col.name;
            headerRow.appendChild(th);
        });
        tableHead.appendChild(headerRow);

        // Genera le righe della tabella
        //generateTableRows();
        generaTabellaDaDati(timesheet);
    }

    // Funzione aggiornata che popola la tabella partendo da una variabile (es. timesheetData)
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
        let value = element.innerText ? element.innerText.trim() : element.value;
        let columnIndex = Array.from(element.closest("tr").children).indexOf(element.closest("td"));

        //document.querySelectorAll(`#editableTable tbody tr`).forEach((row, rowIndex) => {
        //    if (rowIndex > Array.from(tableBody.children).indexOf(element.closest("tr"))) {
        //        let targetCell = row.children[columnIndex];
        //
        //        if (columnName === "Cliente" || columnName === "Luogo") {
        //            targetCell.innerText = value;
        //        } else if (columnName === "Entrata" || columnName === "Uscita") {
        //            targetCell.querySelector("input[type='time']").value = value;
        //        }
        //    }
        //});
        updateHiddenInput();
    }

    // Funzione per aggiornare l'input nascosto con i dati della tabella
    function updateHiddenInput() {
        let tableData = [];

        document.querySelectorAll("#editableTable tbody tr").forEach(row => {
            let rowData = {};
            row.querySelectorAll("td").forEach((cell, index) => {
                let columnName = columns[index].name; // Nome della colonna
                let cellValue = "";

                if (cell.querySelector("input[type='checkbox']")) {
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

        //console.clear();
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
        if (target.tagName !== "TD" && target.tagName !== "INPUT") return;

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

            if (nextCell.querySelector("input[type='checkbox']")) {
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