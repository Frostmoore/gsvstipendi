<div class="relative w-full">
    <!-- Campo select -->
    <x-input-label for="user" :value="__('Operatore')" />
    <select id="user-select" name="user" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm mb-4">
        <option value="">Cerca un utente...</option>
    </select>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof TomSelect !== "undefined") {
        let userSelect = new TomSelect("#user-select", {
            valueField: 'id',  // Il valore salvato è l'ID dell'utente
            labelField: 'name', // Mostra il nome
            searchField: ['name', 'surname', 'email', 'username', 'role'], // Ricerca su più campi
            create: false,
            load: function(query, callback) {
                if (!query.length) return callback();

                fetch("{{ route('search.users') }}?query=" + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => callback(data))
                    .catch(() => callback());
            },
            render: {
                option: function(item, escape) {
                    return `<div>${escape(item.surname)} ${escape(item.name)} (${escape(item.role)}) - ${escape(item.email)}</div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.surname)} ${escape(item.name)} (${escape(item.role)})</div>`;
                }
            },
            maxOptions: 10
        });

        // Seleziona il valore quando viene cambiato
        userSelect.on('change', function(value) {
            console.log("ID selezionato:", value); // Debug per controllare se il valore viene aggiornato
        });
    } else {
        console.error("TomSelect is not defined. Assicurati che lo script sia caricato correttamente.");
    }
});
</script>
