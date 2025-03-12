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
    let roles = `<?= json_encode($roles); ?>`;
    roles = JSON.parse(roles);
    let compensations = `<?= json_encode($compensations); ?>`;
    compensations = JSON.parse(compensations);
    let utenti = `<?= json_encode($users); ?>`;
    utenti = JSON.parse(utenti);
    //let user = 0;
    let user = userSelect.value;
    let ruolo = 0;
    let ruolo_name = '';
    let columns = [];
        
    // Definizione delle colonne in base al ruolo
    switch (ruolo_name) {
        case 'Autista':
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Entrata", type: "time", editable: true },
                { name: "Uscita", type: "time", editable: true },
                { name: "TrasfLunga", type: "checkbox", editable: false },
                { name: "Pernotto", type: "checkbox", editable: false },
                { name: "Presidio", type: "checkbox", editable: false }
            ];
            break;
        case 'Magazziniere FIGC':
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Estero", type: "checkbox", editable: false }
            ];
            break;
        case 'Facchino':
            columns = [
                { name: "Data", type: "text", editable: false },
                { name: "Cliente", type: "text", editable: true },
                { name: "Luogo", type: "text", editable: true },
                { name: "Entrata", type: "time", editable: true },
                { name: "Uscita", type: "time", editable: true },
                { name: "Trasferta", type: "checkbox", editable: false }
            ];
            break;
        default:
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
            break;
    }



    function generateTable() {

        tableHead.innerHTML = "";
        tableBody.innerHTML = "";

        let headerRow = document.createElement("tr");
        columns.forEach(col => {
            let th = document.createElement("th");
            th.textContent = col.name;
            headerRow.appendChild(th);
        });
        tableHead.appendChild(headerRow);

        generateTableRows();
    }

    function generateTableRows() {

        

        userSelect.addEventListener('change', function () {
            user = userSelect.value;
            //console.log(roles);

            for (let i = 0; i < utenti.length; i++) {
                if (user == utenti[i].id) {
                    ruolo = utenti[i].role;
                }
            }

            for (let j = 0; j < roles.length; j++) {
                if (ruolo == roles[j].id) {
                    ruolo_name = roles[j].role;
                }
            }
        });
        let month = parseInt(monthSelect.value);
        let year = parseInt(yearSelect.value);

        tableBody.innerHTML = "";

        let daysInMonth = new Date(year, month, 0).getDate();

        for (let day = 1; day <= daysInMonth; day++) {
            let date = new Date(year, month - 1, day);
            let dayOfWeek = date.toLocaleDateString("it-IT", { weekday: "long" });
            let monthName = date.toLocaleDateString("it-IT", { month: "long" });
            let formattedDate = `<strong>${capitalizeFirstLetter(dayOfWeek)}</strong>`;
            formattedDate += `<span> ${day}</span>`;

//            console.log(date);

            let row = document.createElement("tr");

            columns.forEach(col => {
                let td = document.createElement("td");

                if (col.name === "Data") {
                    td.innerHTML = formattedDate;
                    td.style.textAlign = "left";
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
            //console.log(date);
        }

        updateHiddenInput();
    }

    function propagateValue(element, columnName) {
        let value = element.innerText ? element.innerText.trim() : element.value;
        let columnIndex = Array.from(element.closest("tr").children).indexOf(element.closest("td"));

        document.querySelectorAll(`#editableTable tbody tr`).forEach((row, rowIndex) => {
            if (rowIndex > Array.from(tableBody.children).indexOf(element.closest("tr"))) {
                let targetCell = row.children[columnIndex];

                if (columnName === "Cliente" || columnName === "Luogo") {
                    targetCell.innerText = value;
                } else if (columnName === "Entrata" || columnName === "Uscita") {
                    targetCell.querySelector("input[type='time']").value = value;
                }
            }
        });
        updateHiddenInput();
    }

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


    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    monthSelect.addEventListener("change", generateTableRows);
    yearSelect.addEventListener("change", generateTableRows);

    generateTable();


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