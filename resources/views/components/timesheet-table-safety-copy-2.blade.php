<x-input-label for="editableTable" :value="__('Foglio Orario')" />
<div class="overflow-x-auto shadow-md rounded-lg">
        <table id="editableTable" class="table-auto w-full gsv-timesheet-table">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Luogo</th>
                    <th>Entrata</th>
                    <th>Uscita</th>
                    <th>Trasferta <= 200Km</th>
                    <th>Trasferta > 200Km</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 dark:text-gray-200">
                <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600">
                    <td contenteditable="true">Mario</td>
                    <td contenteditable="true">Rossi</td>
                    <td contenteditable="true">30</td>
                    <td>
                        <input type="time" min="08:00" max="23:59" class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600">
                    </td>
                    <td>
                        <input type="time" min="08:00" max="23:59" class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600">
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox">
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox">
                    </td>
                </tr>
                <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600">
                    <td contenteditable="true">Luca</td>
                    <td contenteditable="true">Bianchi</td>
                    <td contenteditable="true">25</td>
                    <td>
                        <input type="time" min="08:00" max="23:59" class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600">
                    </td>
                    <td>
                        <input type="time" min="08:00" max="23:59" class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600">
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox">
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox">
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Campo nascosto per memorizzare i dati JSON -->
        <input type="hidden" name="tableData" id="hiddenTableData" value="">
</div>

<script>

document.addEventListener("DOMContentLoaded", function () {
    let tableBody = document.querySelector("#editableTable tbody");
    let monthSelect = document.getElementById("month");
    let yearSelect = document.getElementById("year");
    let hiddenInput = document.getElementById("hiddenTableData");

    function generateTableRows() {
        let month = parseInt(monthSelect.value); // Mese (0-11)
        let year = parseInt(yearSelect.value);   // Anno

        tableBody.innerHTML = ""; // Pulisce la tabella

        let daysInMonth = new Date(year, month, 0).getDate(); // Numero di giorni nel mese

        for (let day = 1; day <= daysInMonth; day++) {
            let date = new Date(year, month, day);
            let dayOfWeek = date.toLocaleDateString("it-IT", { weekday: "long" });
            let monthName = date.toLocaleDateString("it-IT", { month: "long" });
            let formattedDate = `<strong>${capitalizeFirstLetter(dayOfWeek)}</strong>`;
            if(dayOfWeek == "domenica") {
                formattedDate += `<span style="color:red;"> ${day}</span>`;
            } else if(dayOfWeek == "sabato") {
                formattedDate += `<span style="color:orange;"> ${day}</span>`;
            } else {
                formattedDate += `<span> ${day}</span>`;
            }

            let row = `
                <tr>
                    <td style="text-align:left!important;">${formattedDate}</td>
                    <td contenteditable="true"></td>
                    <td contenteditable="true"></td>
                    <td><input type="time" class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600"></td>
                    <td><input type="time" class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 hover:bg-gray-100 hover:dark:bg-gray-600"></td>
                    <td style="text-align: center;"><input type="checkbox"></td>
                    <td style="text-align: center;"><input type="checkbox"></td>
                </tr>
            `;
            tableBody.insertAdjacentHTML("beforeend", row);
        }

        updateHiddenInput();
    }

    function updateHiddenInput() {
        let tableData = [];

        document.querySelectorAll("#editableTable tbody tr").forEach(row => {
            let rowData = [];
            row.querySelectorAll("td").forEach((cell, index) => {
                if (cell.querySelector("input[type='checkbox']")) {
                    rowData.push(cell.querySelector("input[type='checkbox']").checked ? "1" : "0");
                } else if (cell.querySelector("input[type='time']")) {
                    rowData.push(cell.querySelector("input[type='time']").value);
                } else {
                    rowData.push(cell.innerText.trim());
                }
            });
            tableData.push(rowData);
        });

        let jsonData = JSON.stringify(tableData);
        hiddenInput.value = jsonData;

        console.clear();
        console.log("Modifica rilevata:", jsonData);
    }

    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    monthSelect.addEventListener("change", generateTableRows);
    yearSelect.addEventListener("change", generateTableRows);
    tableBody.addEventListener("input", updateHiddenInput);
    tableBody.addEventListener("change", updateHiddenInput);

    // Genera la tabella iniziale basata sul mese e anno attuali
    generateTableRows();

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