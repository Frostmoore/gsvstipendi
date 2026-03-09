@props([
    'timesheet'  => null,
    'users'      => [],
    'userRates'  => null,
])

@php
    $_ur = $userRates;
    $_show_estero          = $_ur && ((float)($_ur->feriale_estero ?? 0) > 0 || (float)($_ur->festivo_estero ?? 0) > 0);
    $_show_figc_tr_aut     = $_ur && (float)($_ur->figc_trasp_autista ?? 0) > 0;
    $_show_figc_tr_acmp    = $_ur && (float)($_ur->figc_trasp_accompagnatore ?? 0) > 0;
    $_show_pres_aut        = $_ur && (float)($_ur->presidio_autisti ?? 0) > 0;
    $_show_pres_acmp       = $_ur && (float)($_ur->presidio_accompagnatori ?? 0) > 0;
    $_show_aut_nofigc      = $_ur && (float)($_ur->autista_no_figc ?? 0) > 0;
    $_show_trasf_breve     = $_ur && (float)($_ur->trasferta ?? 0) > 0;
    $_show_trasf_media     = $_ur && (float)($_ur->trasferta_media ?? 0) > 0;
    $_show_trasf_lunga     = $_ur && (float)($_ur->trasferta_lunga ?? 0) > 0;
    $_show_pernotto        = $_ur && (float)($_ur->pernotto ?? 0) > 0;
@endphp
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
        {{ __('3. Le colonne checkbox sono additive: puoi selezionarne più di una per giorno') }}
    </p>
    <p class="text-xs text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('4. Trasferta Breve = fino a 230 km, Trasferta Media = fino a 300 km, Trasferta Lunga = oltre 300 km') }}
    </p>
    <p class="text-xs text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('5. Estero: sostituisce la tariffa giornaliera con la corrispondente tariffa estero') }}
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
                <th>Estero</th>
                <th>FIGC Trasp. Autista</th>
                <th>FIGC Trasp. Accompagnatore</th>
                <th>Presidio Autisti</th>
                <th>Presidio Accompagnatori</th>
                <th>No FIGC</th>
                <th>Trasferta Breve</th>
                <th>Trasferta Media</th>
                <th>Trasferta Lunga</th>
                <th>Pernotto</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody class="text-gray-700 dark:text-gray-200">
        </tbody>
    </table>

    <div id="mobileCardsContainer" class="space-y-2 p-2" style="display:none"></div>

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
    // Dati esistenti da caricare al primo render (null in modalità creazione)
    let existingData = <?= json_encode($timesheet ? json_decode($timesheet->link, true) : null) ?>;
    let hasLoadedExisting = false;

    const NOTE_OPTIONS = ["Ferie", "Permesso", "Malattia", "104", "Smart Working"];

    const userRatesConfig = {
        showEstero:        <?= json_encode($_show_estero) ?>,
        showFigcTrAut:     <?= json_encode($_show_figc_tr_aut) ?>,
        showFigcTrAccomp:  <?= json_encode($_show_figc_tr_acmp) ?>,
        showPresAut:       <?= json_encode($_show_pres_aut) ?>,
        showPresAccomp:    <?= json_encode($_show_pres_acmp) ?>,
        showAutNoFigc:     <?= json_encode($_show_aut_nofigc) ?>,
        showTrasfBreve:    <?= json_encode($_show_trasf_breve) ?>,
        showTrasfMedia:    <?= json_encode($_show_trasf_media) ?>,
        showTrasfLunga:    <?= json_encode($_show_trasf_lunga) ?>,
        showPernotto:      <?= json_encode($_show_pernotto) ?>,
    };

    const allColumns = [
        { name: "Data",            type: "text",        editable: false },
        { name: "Cliente",         type: "text",        editable: true  },
        { name: "Luogo",           type: "text",        editable: true  },
        { name: "Entrata",         type: "time",        editable: true  },
        { name: "Uscita",          type: "time",        editable: true  },
        { name: "Estero",          type: "checkbox",    editable: false },
        { name: "FigcTraspAut",    label: "FIGC Trasp. Autista",  type: "checkbox", editable: false },
        { name: "FigcTraspAccomp", label: "FIGC Trasp. Accompagnatore",  type: "checkbox", editable: false },
        { name: "PresidioAut",     label: "Presidio Autisti",     type: "checkbox", editable: false },
        { name: "PresidioAccomp",  label: "Presidio Accompagnatori",     type: "checkbox", editable: false },
        { name: "AutistaNoFigc",   label: "No FIGC",        type: "checkbox", editable: false },
        { name: "TrasfBreve",      label: "Trasferta Breve",      type: "checkbox", editable: false },
        { name: "TrasfMedia",      label: "Trasferta Media",      type: "checkbox", editable: false },
        { name: "TrasfLunga",      label: "Trasferta Lunga",      type: "checkbox", editable: false },
        { name: "Pernotto",        type: "checkbox",    editable: false },
        { name: "Note",            type: "multiselect", editable: false },
    ];

    let columns = [];
    let timesheetData = [];

    function updateRoleAndGenerateTable() {
        columns = allColumns.filter(function(col) {
            switch (col.name) {
                case "Estero":         return userRatesConfig.showEstero;
                case "FigcTraspAut":   return userRatesConfig.showFigcTrAut;
                case "FigcTraspAccomp":return userRatesConfig.showFigcTrAccomp;
                case "PresidioAut":    return userRatesConfig.showPresAut;
                case "PresidioAccomp": return userRatesConfig.showPresAccomp;
                case "AutistaNoFigc":  return userRatesConfig.showAutNoFigc;
                case "TrasfBreve":     return userRatesConfig.showTrasfBreve;
                case "TrasfMedia":     return userRatesConfig.showTrasfMedia;
                case "TrasfLunga":     return userRatesConfig.showTrasfLunga;
                case "Pernotto":       return userRatesConfig.showPernotto;
                default:               return true;
            }
        });
        generateTable();
    }


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
        const textCols = columns.filter(c => c.type !== "checkbox");
        const flagCols  = columns.filter(c => c.type === "checkbox");
        textCols.forEach(col => {
            let th = document.createElement("th");
            th.textContent = col.label || col.name;
            headerRow.appendChild(th);
        });
        if (flagCols.length > 0) {
            let th = document.createElement("th");
            th.textContent = "Opzioni"; th.colSpan = 2;
            headerRow.appendChild(th);
        }
        tableHead.appendChild(headerRow);

        generateTableRows();
    }


    function generateTableRows() {
        let month = parseInt(monthSelect.value);
        let year = parseInt(yearSelect.value);

        tableBody.innerHTML = "";
        timesheetData = [];

        let daysInMonth = new Date(year, month, 0).getDate();

        for (let day = 1; day <= daysInMonth; day++) {
            let date = new Date(year, month - 1, day);
            let dayOfWeek = date.toLocaleDateString("it-IT", { weekday: "long" });
            let formattedDate = capitalizeFirstLetter(dayOfWeek) + " " + day;

            // Build data object for this day
            let dayData = { "Data": formattedDate };
            columns.forEach(col => {
                if (col.name !== "Data") {
                    dayData[col.name] = col.type === "checkbox" ? "0" : "";
                }
            });
            timesheetData.push(dayData);

            let row = document.createElement("tr");
            row.classList.add("odd:bg-white", "odd:dark:bg-gray-700", "even:bg-gray-50", "even:dark:bg-gray-800", "even:color-gray-700", "dark:text-gray-200");

            const textCols = columns.filter(c => c.type !== "checkbox");
            const flagCols  = columns.filter(c => c.type === "checkbox");

            textCols.forEach(col => {
                let td = document.createElement("td");

                if (col.name === "Data") {
                    td.innerHTML = `<strong>${capitalizeFirstLetter(dayOfWeek)}</strong> <span>${day}</span>`;
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

                    select.addEventListener("change", (function(d, c) {
                        return function() { d[c.name] = this.value; updateHiddenInput(); };
                    })(dayData, col));
                    td.appendChild(select);
                } else if (col.type === "time") {
                    let input = document.createElement("input");
                    input.type = "time";
                    input.classList.add("odd:bg-white", "odd:dark:bg-gray-700", "even:bg-gray-50", "even:dark:bg-gray-800", "hover:bg-gray-100", "hover:dark:bg-gray-600");
                    input.classList.add("time-column");
                    input.addEventListener("input", (function(d, c) {
                        return function() { d[c.name] = this.value; updateHiddenInput(); };
                    })(dayData, col));
                    td.appendChild(input);
                } else {
                    td.contentEditable = col.editable;
                    if (col.name === "Cliente") {
                        td.classList.add("cliente-column");
                        td.addEventListener("input", (function(d, c) {
                            return function() { d[c.name] = this.innerText.trim(); updateHiddenInput(); };
                        })(dayData, col));
                    }
                    if (col.name === "Luogo") {
                        td.classList.add("luogo-column");
                        td.addEventListener("input", (function(d, c) {
                            return function() { d[c.name] = this.innerText.trim(); updateHiddenInput(); };
                        })(dayData, col));
                    }
                }

                row.appendChild(td);
            });

            if (flagCols.length > 0) {
                let td = document.createElement("td");
                td.colSpan = 2; td.style.verticalAlign = "top";
                td.style.paddingTop = "2px";
                flagCols.forEach(col => {
                    let lbl = document.createElement("label");
                    lbl.style.cssText = "display:flex;align-items:center;gap:4px;font-size:0.8em;cursor:pointer;white-space:nowrap;padding:1px 0;";
                    let input = document.createElement("input");
                    input.type = "checkbox";
                    input.setAttribute("data-col", col.name);
                    input.addEventListener("change", (function(d, c) {
                        return function() { d[c.name] = this.checked ? "1" : "0"; updateHiddenInput(); };
                    })(dayData, col));
                    lbl.appendChild(input);
                    lbl.appendChild(document.createTextNode("\u00a0" + (col.label || col.name)));
                    td.appendChild(lbl);
                });
                row.appendChild(td);
            }

            tableBody.appendChild(row);
        }

        // Pre-popola le righe con i dati salvati (solo al primo caricamento)
        if (!hasLoadedExisting && existingData && existingData.length > 0) {
            existingData.forEach((rowData, rowIndex) => {
                if (!timesheetData[rowIndex]) return;
                columns.forEach(col => {
                    if (col.name === "Data") return;
                    let val = rowData[col.name];
                    if (val === undefined || val === null) return;
                    timesheetData[rowIndex][col.name] = String(val);
                });
            });
            hasLoadedExisting = true;

            // Update DOM to reflect loaded data
            document.querySelectorAll("#editableTable tbody tr").forEach((row, rowIndex) => {
                let rowData = timesheetData[rowIndex];
                if (!rowData) return;
                const textColsLoad = columns.filter(c => c.type !== "checkbox");
                const flagColsLoad  = columns.filter(c => c.type === "checkbox");
                row.querySelectorAll("td").forEach((td, colIndex) => {
                    let col = textColsLoad[colIndex];
                    if (!col || col.name === "Data") return;
                    let val = rowData[col.name];
                    if (val === undefined || val === null) return;
                    if (col.type === "multiselect") {
                        let sel = td.querySelector("select.note-select");
                        if (sel) sel.value = val || "";
                    } else if (col.type === "time") {
                        let inp = td.querySelector("input[type='time']");
                        if (inp) inp.value = val;
                    } else {
                        td.innerText = val;
                    }
                });
                // Restore checkbox values using data-col attributes
                flagColsLoad.forEach(col => {
                    let val = rowData[col.name];
                    if (val === undefined || val === null) return;
                    let cb = row.querySelector("input[type='checkbox'][data-col='" + col.name + "']");
                    if (cb) cb.checked = (val === "1" || val === 1 || val === true);
                });
            });
        }

        updateHiddenInput();
        renderMobileCards();
        applyLayout();
    }


    // Popola le card mobile
    function renderMobileCards() {
        let container = document.getElementById("mobileCardsContainer");
        container.innerHTML = "";

        timesheetData.forEach((dayData, dayIndex) => {
            let card = document.createElement("div");
            card.classList.add(
                "bg-white", "dark:bg-gray-800", "rounded-lg",
                "border", "border-gray-200", "dark:border-gray-700", "p-3"
            );

            let header = document.createElement("div");
            header.classList.add(
                "font-semibold", "text-sm", "text-gray-800", "dark:text-gray-200",
                "mb-2", "pb-1", "border-b", "border-gray-200", "dark:border-gray-700"
            );
            header.textContent = dayData["Data"] || "";
            card.appendChild(header);

            const textCols = columns.filter(c => c.type !== "checkbox");
            const flagCols  = columns.filter(c => c.type === "checkbox");

            textCols.forEach((col) => {
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
                    select.addEventListener("change", (function(d, c) {
                        return function() {
                            d[c.name] = this.value;
                            // sync to table
                            let tableRow = document.querySelectorAll("#editableTable tbody tr")[dayIndex];
                            if (tableRow) {
                                let colIdx = columns.findIndex(x => x.name === c.name);
                                let sel = tableRow.cells[colIdx] && tableRow.cells[colIdx].querySelector("select.note-select");
                                if (sel) sel.value = this.value;
                            }
                            updateHiddenInput();
                        };
                    })(dayData, col));
                    inputWrap.appendChild(select);

                } else if (col.type === "time") {
                    let input = document.createElement("input");
                    input.type = "time";
                    input.value = dayData[col.name] || "";
                    input.classList.add(
                        "time-column", "bg-white", "text-gray-900",
                        "border", "border-gray-200", "dark:border-gray-500",
                        "rounded", "px-2", "py-0.5", "w-full",
                        "focus:outline-none", "focus:ring-1", "focus:ring-blue-400"
                    );
                    input.addEventListener("input", (function(d, c) {
                        return function() {
                            d[c.name] = this.value;
                            let tableRow = document.querySelectorAll("#editableTable tbody tr")[dayIndex];
                            if (tableRow) {
                                let colIdx = columns.findIndex(x => x.name === c.name);
                                let inp = tableRow.cells[colIdx] && tableRow.cells[colIdx].querySelector("input[type='time']");
                                if (inp) inp.value = this.value;
                            }
                            updateHiddenInput();
                        };
                    })(dayData, col));
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
                    input.addEventListener("input", (function(d, c) {
                        return function() {
                            d[c.name] = this.value;
                            let tableRow = document.querySelectorAll("#editableTable tbody tr")[dayIndex];
                            if (tableRow) {
                                let colIdx = columns.findIndex(x => x.name === c.name);
                                let cell = tableRow.cells[colIdx];
                                if (cell) cell.innerText = this.value;
                            }
                            updateHiddenInput();
                        };
                    })(dayData, col));
                    inputWrap.appendChild(input);
                }

                fieldRow.appendChild(inputWrap);
                card.appendChild(fieldRow);
            });

            if (flagCols.length > 0) {
                let flagSection = document.createElement("div");
                flagSection.classList.add("py-0.5");
                let flagLabel = document.createElement("div");
                flagLabel.classList.add("text-xs", "text-gray-500", "dark:text-gray-400", "mb-1");
                flagLabel.textContent = "Opzioni";
                flagSection.appendChild(flagLabel);
                let flagWrap = document.createElement("div");
                flagWrap.style.cssText = "display:flex;flex-direction:column;gap:4px;";
                flagCols.forEach(col => {
                    let lbl = document.createElement("label");
                    lbl.style.cssText = "display:flex;align-items:center;gap:3px;font-size:0.8em;cursor:pointer;"; lbl.classList.add("text-gray-900", "dark:text-gray-100");
                    let input = document.createElement("input");
                    input.type = "checkbox";
                    input.checked = dayData[col.name] === "1";
                    input.classList.add("h-4", "w-4");
                    input.addEventListener("change", (function(d, c) {
                        return function() {
                            d[c.name] = this.checked ? "1" : "0";
                            // Sync to desktop table row
                            let tableRow = document.querySelectorAll("#editableTable tbody tr")[dayIndex];
                            if (tableRow) {
                                let cb = tableRow.querySelector("input[type='checkbox'][data-col='" + c.name + "']");
                                if (cb) cb.checked = this.checked;
                            }
                            updateHiddenInput();
                        };
                    })(dayData, col));
                    lbl.appendChild(input);
                    lbl.appendChild(document.createTextNode("\u00a0" + (col.label || col.name)));
                    flagWrap.appendChild(lbl);
                });
                flagSection.appendChild(flagWrap);
                card.appendChild(flagSection);
            }

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


    function updateHiddenInput() {
        hiddenInput.value = JSON.stringify(timesheetData);
    }

    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    // Eventi per rigenerare la tabella quando cambiano i selettori
    monthSelect.addEventListener("change", generateTable);
    yearSelect.addEventListener("change", generateTable);

    // Genera la tabella iniziale
    updateRoleAndGenerateTable();

    // Riadatta il layout al resize
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
