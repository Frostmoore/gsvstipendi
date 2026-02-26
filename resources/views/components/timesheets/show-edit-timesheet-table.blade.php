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

    <div id="mobileCardsContainer" class="space-y-2 p-2" style="display:none"></div>

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

    const allowedColKeys = <?= json_encode($colKeys ?? []) ?>;

    const allColumns = [
        { name: "Data",       type: "text",        editable: false },
        { name: "Cliente",    type: "text",        editable: true  },
        { name: "Luogo",      type: "text",        editable: true  },
        { name: "Entrata",    type: "time",        editable: true  },
        { name: "Uscita",     type: "time",        editable: true  },
        { name: "TrasfBreve", label: "Trasferta",  type: "checkbox", editable: false },
        { name: "TrasfLunga", type: "checkbox",    editable: false },
        { name: "Pernotto",   type: "checkbox",    editable: false },
        { name: "Presidio",   type: "checkbox",    editable: false },
        { name: "Estero",     type: "checkbox",    editable: false },
        { name: "Note",       type: "multiselect", editable: false }
    ];

    let columns = [];
    let timesheetData = [];

    function updateRoleAndGenerateTable() {
        columns = allowedColKeys.length > 0
            ? allColumns.filter(col => allowedColKeys.includes(col.name))
            : allColumns;
        generateTable();
    }


    // Funzione per generare la tabella
    function generateTable() {
        timesheetData = JSON.parse('<?= json_encode($timesheet) ?>');
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
            th.textContent = col.label || col.name;
            headerRow.appendChild(th);
        });
        tableHead.appendChild(headerRow);

        generaTabellaDaDati();
        renderMobileCards();
        applyLayout();
    }

    // Popola la tabella desktop partendo dai dati JSON esistenti
    function generaTabellaDaDati() {
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


    // Popola le card mobile partendo dai dati JSON esistenti
    function renderMobileCards() {
        let container = document.getElementById("mobileCardsContainer");
        container.innerHTML = "";

        timesheetData.forEach((dayData) => {
            let card = document.createElement("div");
            card.classList.add(
                "bg-white", "dark:bg-gray-800", "rounded-lg",
                "border", "border-gray-200", "dark:border-gray-700", "p-3"
            );

            // Intestazione con la data
            let header = document.createElement("div");
            header.classList.add(
                "font-semibold", "text-sm", "text-gray-800", "dark:text-gray-200",
                "mb-2", "pb-1", "border-b", "border-gray-200", "dark:border-gray-700"
            );
            header.textContent = dayData["Data"] || "";
            card.appendChild(header);

            columns.forEach((col) => {
                if (col.name === "Data") return;

                let fieldRow = document.createElement("div");
                fieldRow.classList.add("flex", "items-center", "justify-between", "gap-2", "py-0.5");

                let label = document.createElement("span");
                label.classList.add(
                    "text-xs", "text-gray-500", "dark:text-gray-400", "flex-shrink-0", "w-24"
                );
                label.textContent = col.label || col.name;
                fieldRow.appendChild(label);

                let inputWrap = document.createElement("div");
                inputWrap.classList.add("flex-1", "min-w-0");

                if (col.type === "multiselect") {
                    let select = document.createElement("select");
                    select.classList.add("note-select", "w-full");
                    select.style.backgroundColor = "white";
                    select.style.color = "#111827";
                    select.style.border = "1px solid #e5e7eb";
                    select.style.borderRadius = "4px";

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
                    inputWrap.appendChild(select);

                } else if (col.type === "checkbox") {
                    let input = document.createElement("input");
                    input.type = "checkbox";
                    input.checked = dayData[col.name] === "1";
                    input.classList.add("h-4", "w-4");
                    input.addEventListener("change", function () {
                        dayData[col.name] = this.checked ? "1" : "0";
                        updateHiddenInput();
                    });
                    inputWrap.classList.add("text-right");
                    inputWrap.appendChild(input);

                } else if (col.name === "Entrata" || col.name === "Uscita") {
                    let input = document.createElement("input");
                    input.type = "time";
                    input.value = dayData[col.name] || "";
                    input.classList.add(
                        "time-column", "bg-white", "text-gray-900",
                        "border", "border-gray-200", "dark:border-gray-500",
                        "rounded", "px-2", "py-0.5", "w-full",
                        "focus:outline-none", "focus:ring-1", "focus:ring-blue-400"
                    );
                    input.addEventListener("input", function () {
                        dayData[col.name] = this.value;
                        updateHiddenInput();
                    });
                    inputWrap.appendChild(input);

                } else {
                    let input = document.createElement("input");
                    input.type = "text";
                    input.value = dayData[col.name] || "";
                    input.classList.add(
                        "text-sm", "bg-white", "text-gray-900",
                        "border", "border-gray-200", "dark:border-gray-500",
                        "rounded", "px-2", "py-0.5", "w-full",
                        "focus:outline-none", "focus:ring-1", "focus:ring-blue-400"
                    );
                    input.addEventListener("input", function () {
                        dayData[col.name] = this.value;
                        updateHiddenInput();
                    });
                    inputWrap.appendChild(input);
                }

                fieldRow.appendChild(inputWrap);
                card.appendChild(fieldRow);
            });

            container.appendChild(card);
        });
    }

    // Mostra tabella su desktop, card su mobile
    function applyLayout() {
        let table = document.getElementById("editableTable");
        let mobileContainer = document.getElementById("mobileCardsContainer");
        if (window.innerWidth < 768) {
            table.style.display = "none";
            mobileContainer.style.display = "";
        } else {
            table.style.display = "";
            mobileContainer.style.display = "none";
        }
    }

    // Aggiorna l'input nascosto serializzando timesheetData
    function updateHiddenInput() {
        hiddenInput.value = JSON.stringify(timesheetData);
        console.log("Modifica rilevata:", hiddenInput.value);
    }

    // Funzione per capitalizzare la prima lettera di una stringa
    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    // Genera la tabella iniziale
    updateRoleAndGenerateTable();

    // Riadatta il layout al resize della finestra
    window.addEventListener("resize", applyLayout);


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
