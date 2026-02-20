<?php

use App\Helpers\DateHelper;

$id = $timesheet->id;
$ts = $timesheet;
$_month = $timesheet->month;
$month = $months[$_month];
$year = $timesheet->year;
$userid = $timesheet->user;
$ruolo = $timesheet->role;
$o_compensation = $timesheet->override_compensation;
$o_fascia = $timesheet->override_fascia;
$timesheet = json_decode($timesheet->link);
$compensi = [];

foreach($users as $u) {
    if($u->id == $userid) {
        $user_fullname = $u->surname . ' ' . $u->name;
        $u_fissa = (float)$u->fissa > 0 ? (float)$u->fissa : 0;
        $u_fascia = (float)$u->fascia > 0 ? (float)$u->fascia : 0;
        $u_special = (float)$u->special > 0 ? (float)$u->special : 0;
        $u_trasferta = (float)$u->trasferta > 0 ? (float)$u->trasferta : 0;
        $u_incremento = (float)$u->incremento > 0 ? (float)$u->incremento : 0;
        $role = $u->role;
    }
}

foreach($roles as $r) {
    if($r->id == $role) {
        $role_id = $r->id;
        $role = $r->role;
    }
}

$u_role = $role;
$role = $ruolo;

switch($role) {
    case 'Magazziniere FIGC':
        $cols = [
            'Data',
            'Cliente',
            'Luogo',
            'Estero',
            'Note'
        ];
        break;
    case 'Autista':
        $cols = [
            'Data',
            'Cliente',
            'Luogo',
            'Entrata',
            'Uscita',
            'Trasf. Lunga',
            'Trasferta breve',
            'Pernotto',
            'Presidio',
            'Note'
        ];
        break;
    case 'Facchino':
        $cols = [
            'Data',
            'Cliente',
            'Luogo',
            'Entrata',
            'Uscita',
            'Trasferta',
            'Note'
        ];
        break;
    default:
        $cols = [
            'Data',
            'Cliente',
            'Luogo',
            'Entrata',
            'Uscita',
            'Trasferta',
            'Trasf. Lunga',
            'Trasferta breve',
            'Pernotto',
            'Presidio',
            'Estero',
            'Note'
        ];
        break;
}

$sabati_lavorati = 0;

foreach ($timesheet as $t) {
    $festivo = false;
    $t = json_decode(json_encode($t), true);
    $rowCompensi = [];

    $day = explode(' ', $t['Data'])[1];
    $_month = str_pad($_month, 2, '0', STR_PAD_LEFT);
    $day = str_pad($day, 2, '0', STR_PAD_LEFT);
    $true_date_str = $year . '-' . $_month . '-' . $day;
    if(DateHelper::isHoliday($true_date_str)) {
        $festivo = true;
    }
    $rowCompensi['data'] = $true_date_str;

    $entrata = array_key_exists('Entrata', $t) ? $t['Entrata'] : null;
    $uscita = array_key_exists('Uscita', $t) ? $t['Uscita'] : null;

    // NUOVO CONTROLLO: salta la giornata se manca entrata o uscita
    if($role != 'Magazziniere FIGC') {
        if (empty($entrata) || empty($uscita) || $entrata == '00:00' || $uscita == '00:00') {
            continue;
        }
    } else {
        $cliente = array_key_exists('Cliente', $t) ? $t['Cliente'] : null;
        $luogo = array_key_exists('Luogo', $t) ? $t['Luogo'] : null;
        if (empty($cliente)) {
            continue;
        }
        if(empty($luogo)) {
            continue;
        }
    }

    // NUOVO CONTROLLO: conta i sabati lavorati
    if (strpos($t['Data'], 'Sabato') !== false && !empty($entrata) && !empty($uscita)) {
        $sabati_lavorati++;
    }

    $trasferta        = array_key_exists('Trasferta', $t)  ? $t['Trasferta']  : null;
    $pernotto         = array_key_exists('Pernotto', $t)   ? $t['Pernotto']   : null;
    $presidio         = array_key_exists('Presidio', $t)   ? $t['Presidio']   : null;
    $trasferta_lunga  = array_key_exists('TrasfLunga', $t) ? $t['TrasfLunga'] : null;
    $trasferta_breve  = array_key_exists('TrasfBreve', $t) ? $t['TrasfBreve'] : null;
    $estero           = array_key_exists('Estero', $t)     ? $t['Estero']     : null;

    // -----------------------------------------------------------------------
    // Flag che annullano il calcolo degli straordinari per la giornata.
    // Aggiungere o rimuovere condizioni qui per cambiare il comportamento.
    // -----------------------------------------------------------------------
    $ha_flag_speciale = (
        $trasferta       == 1 ||
        $pernotto        == 1 ||
        $presidio        == 1 ||
        $trasferta_lunga == 1 ||
        $trasferta_breve == 1 ||
        $estero          == 1
    );

    // Calcolo straordinari (ore oltre la 9ª) — azzerato se c'è una flag speciale
    $rowCompensi['straordinari']     = 0;
    $rowCompensi['straordinari_ore'] = 0;

    if (!$ha_flag_speciale && $entrata != '' && $uscita != '') {
        $t1 = DateTime::createFromFormat('H:i', $entrata);
        $t2 = DateTime::createFromFormat('H:i', $uscita);

        if ($t1 && $t2) {
            $difference = $t1->diff($t2);
            $totalHours = $difference->h + ($difference->i / 60);

            if ($totalHours > 9) {
                $extraHours = floor($totalHours - 9); // ore intere oltre la soglia
                foreach ($compensations as $c) {
                    if ($c->name == 'Straordinari') {
                        $x = $c->value;
                    }
                }
                $rowCompensi['straordinari']     = $extraHours * (float)$x; // valore in €
                $rowCompensi['straordinari_ore'] = $extraHours;              // ore di straordinario
            }
        }
    }

    if($role == 'Facchino') {

        foreach($compensations as $c) {
            if(!$festivo) {
                if($c->name == 'Giornata Lavorativa') {
                    if($u_fascia > 0) {
                        $rowCompensi['giornata'] = (float)$u_fascia;
                    } else {
                        $rowCompensi['giornata'] = (float)$c->value;
                    }
                }
                if($estero > 0) {
                    $rowCompensi['estero'] = 1;
                }
            } else {
                // Giorno festivo: solo il compenso festivo, non anche la giornata normale
                if($c->name == 'Festivo') {
                    if ($estero > 0) {
                        $rowCompensi['festivo_estero'] = (float)$c->value;
                    } else {
                        $rowCompensi['festivo_italia'] = (float)$c->value;
                    }
                }
                if($estero > 0) {
                    $rowCompensi['estero'] = 1;
                }
            }
        }

    } else if($role == 'Autista') {

        foreach($compensations as $c) {
            if(!$festivo) {
                if($c->name == 'Giornata Lavorativa') {
                    if($u_fissa > 0) {
                        // Dal 3° sabato in poi (sabati_lavorati già incrementato prima di questo blocco)
                        // il compenso passa alla tariffa standard
                        if($sabati_lavorati <= 2) {
                            $rowCompensi['giornata'] = (float)$u_fissa;
                        } else {
                            $rowCompensi['giornata'] = (float)$c->value;
                        }
                    } else {
                        $rowCompensi['giornata'] = (float)$c->value;
                    }
                }
                if($estero > 0) {
                    $rowCompensi['estero'] = 1;
                }
            } else {
                // Giorno festivo: solo il compenso festivo, non anche la giornata normale
                if($c->name == 'Festivo') {
                    if ($estero > 0) {
                        $rowCompensi['festivo_estero'] = (float)$c->value;
                    } else {
                        $rowCompensi['festivo_italia'] = (float)$c->value;
                    }
                }
                if($estero > 0) {
                    $rowCompensi['estero'] = 1;
                }
            }
        }

    } else if($role == 'Magazziniere FIGC') {
        // Pre-carica le tariffe base per FIGC così le usiamo nei calcoli sottostanti
        $baseGiornata   = 0; // Feriale Italia
        $feriale_estero = 0; // Feriale Estero
        foreach($compensations as $d) {
            if($d->name == 'Feriale Italia') $baseGiornata   = (float)$d->value;
            if($d->name == 'Feriale Estero') $feriale_estero = (float)$d->value;
        }

        if($estero == 1) {
            if(!$festivo) {
                // Feriale estero: giornata base + extra per essere all'estero
                $rowCompensi['giornata'] = $baseGiornata;
                $rowCompensi['estero']   = $feriale_estero - $baseGiornata;
            } else {
                foreach($compensations as $c) {
                    if($c->name == 'Festivo Estero') {
                        // Stessa logica del festivo italia, ma con l'extra estero separato:
                        // - estero        = bonus trasferta (Feriale Estero - Feriale Italia)
                        // - festivo_estero = bonus festivo  (Festivo Estero  - Feriale Estero)
                        $rowCompensi['giornata']       = $baseGiornata;
                        $rowCompensi['estero']         = $feriale_estero - $baseGiornata;
                        $rowCompensi['festivo_estero'] = (float)$c->value - $feriale_estero;
                    }
                }
            }
        } else {
            if(!$festivo) {
                // Feriale italia: solo la giornata base
                $rowCompensi['giornata'] = $baseGiornata;
            } else {
                foreach($compensations as $c) {
                    if($c->name == 'Festivo Italia') {
                        // Festivo italia: giornata base + extra festivo
                        $rowCompensi['giornata']       = $baseGiornata;
                        $rowCompensi['festivo_italia'] = (float)$c->value - $baseGiornata;
                    }
                }
            }
        }

    } else {

        foreach($compensations as $c) {
            if(!$festivo){
                if($c->name == 'Giornata Lavorativa') {
                    $rowCompensi['giornata'] = (float)$c->value;
                }
                if($estero > 0) {
                    $rowCompensi['estero'] = 1;
                }
            } else {
                // Giorno festivo: solo il compenso festivo, non anche la giornata normale
                if($c->name == 'Festivo') {
                    if ($estero > 0) {
                        $rowCompensi['festivo_estero'] = (float)$c->value;
                    } else {
                        $rowCompensi['festivo_italia'] = (float)$c->value;
                    }
                }
                if($estero > 0) {
                    $rowCompensi['estero'] = 1;
                }
            }
        }
    }

    if($trasferta == 1) {
        foreach($compensations as $c) {
            if($c->name == 'Trasferta') {
                $rowCompensi['trasferta'] = $c->value;
            }
        }
    }

    if($pernotto == 1) {
        foreach($compensations as $c) {
            if($c->name == 'Pernotto') {
                $rowCompensi['pernotto'] = $c->value;
            }
        }
    }

    if($presidio == 1) {
        foreach($compensations as $c) {
            if($c->name == 'Presidio') {
                $rowCompensi['presidio'] = $c->value;
            }
        }
    }

    if($trasferta_lunga == 1) {
        foreach($compensations as $c) {
            if($c->name == 'Trasf. Lunga') {
                $rowCompensi['trasferta_lunga'] = $c->value;
            }
        }
    }

    if($trasferta_breve == 1) {
        foreach($compensations as $c) {
            if($c->name == 'Trasferta breve') {
                $rowCompensi['trasferta_breve'] = $c->value;
            }
        }
    }

    array_push($compensi, $rowCompensi);
}

// Raccoglie le note da TUTTE le giornate (anche senza entrata/uscita)
$note_summary = ['Ferie' => 0, 'Permesso' => 0, 'Malattia' => 0, '104' => 0];
foreach ($timesheet as $dayRow) {
    $dayRow = json_decode(json_encode($dayRow), true);
    if (!empty($dayRow['Note'])) {
        $noteVals = array_map('trim', explode(',', $dayRow['Note']));
        foreach ($noteVals as $nv) {
            if (array_key_exists($nv, $note_summary)) {
                $note_summary[$nv]++;
            }
        }
    }
}

//var_dump($compensi);

//-----------------------------------------------------------------------//
//------------------------ Calcolo Totali -------------------------------//
//-----------------------------------------------------------------------//
// Totali compensi (€)
$trasferte       = 0;
$pernotti        = 0;
$presidi         = 0;
$trasferte_lunghe = 0;
$trasferte_brevi = 0;
$esteri          = 0;
$giornate        = 0;
$festivi_italia  = 0; // festivi in Italia
$festivi_estero  = 0; // festivi all'estero
$straordinari    = 0;
$incrementi      = 0;

// Totali numerici (conteggi/ore)
$trasferte_num        = 0;
$pernotti_num         = 0;
$presidi_num          = 0;
$trasferte_lunghe_num = 0;
$trasferte_brevi_num  = 0;
$esteri_num           = 0;
$giornate_num         = 0;
$festivi_italia_num   = 0; // giorni festivi in Italia
$festivi_estero_num   = 0; // giorni festivi all'estero
$straordinari_ore     = 0; // ore totali di straordinario (non giorni)

foreach($compensi as $y => $z) {
    $trasferte        += $z['trasferta']       ?? 0;
    $pernotti         += $z['pernotto']        ?? 0;
    $presidi          += $z['presidio']        ?? 0;
    $trasferte_lunghe += $z['trasferta_lunga'] ?? 0;
    $trasferte_brevi  += $z['trasferta_breve'] ?? 0;
    $esteri           += $z['estero']          ?? 0;
    $giornate         += $z['giornata']        ?? 0;
    $festivi_italia   += $z['festivo_italia']   ?? 0;
    $festivi_estero   += $z['festivo_estero']   ?? 0;
    $straordinari     += $z['straordinari']     ?? 0;
    $straordinari_ore += $z['straordinari_ore'] ?? 0;

    array_key_exists('trasferta', $z)       ? $trasferte_num++        : null;
    array_key_exists('pernotto', $z)        ? $pernotti_num++         : null;
    array_key_exists('presidio', $z)        ? $presidi_num++          : null;
    array_key_exists('trasferta_lunga', $z) ? $trasferte_lunghe_num++ : null;
    array_key_exists('trasferta_breve', $z) ? $trasferte_brevi_num++  : null;
    array_key_exists('estero', $z)          ? $esteri_num++           : null;
    array_key_exists('giornata', $z)        ? $giornate_num++         : null;
    array_key_exists('festivo_italia', $z)  ? $festivi_italia_num++   : null;
    array_key_exists('festivo_estero', $z)  ? $festivi_estero_num++   : null;
}

// L'incremento è un importo mensile fisso, non dipende dai giorni lavorati
$incrementi = (float)$u_incremento;

if($u_trasferta > 0) {
    // Tariffa personalizzata per evento: moltiplica per il numero di eventi effettivi
    $trasferte_lunghe = (float)$u_trasferta * $trasferte_lunghe_num;
    $trasferte_brevi  = (float)$u_trasferta * $trasferte_brevi_num;
}

if($u_fissa > 0) {
    // Paga base mensile fissa, più un rateo per ogni sabato lavorato oltre i primi 2
    $sabati_extra   = max(0, $sabati_lavorati - 2);
    $tariffa_sabato = $giornate_num > 0 ? (float)$u_fissa / $giornate_num : 0;
    $giornate       = (float)$u_fissa + ($tariffa_sabato * $sabati_extra);
}

if($u_fascia > 0) {
    $giornate = (float)$u_fascia * $giornate_num;
}

if($o_fascia > 0) {
    $giornate = (float)$o_fascia * $giornate_num;
}


$totale = $trasferte + $pernotti + $presidi + $trasferte_lunghe + $trasferte_brevi + $esteri + $giornate + $festivi_italia + $festivi_estero + $straordinari + $incrementi;

if($o_compensation > 0) {
    $totale = (float)$o_compensation;
}



?>

<div class="title-container mb-4">
    <h2 class="text-lg text-gray-800 dark:text-gray-200 leading-tight">
        {{ $user_fullname }} ({{ $role }}) - {{ $month }} {{ $year }}
    </h2>
</div>

<div class="w-full overflow-x-auto">
    <div class="mb-8">
        <p class="text-lg text-gray-800 dark:text-gray-200 leading-tight">
            <strong>Totale Compenso:</strong> <span style="padding: 5px;background-color:orange;color:black; font-size: 1.5rem;font-weight:bolder;">{{ $totale }}€</span>
        </p><br />
        <p class="text-lg text-gray-800 dark:text-gray-200 leading-tight">
            <strong>Distinta:</strong>
        </p><br />
        <table class="table-fixed w-full gsv-timesheet-table">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-2"></th>
                    <th class="px-4 py-2">Giornate</th>
                    <th class="px-4 py-2">Festivi IT</th>
                    <th class="px-4 py-2">Festivi EST</th>
                    <th class="px-4 py-2">Straordinari</th>
                    <th class="px-4 py-2">Trasferte</th>
                    <th class="px-4 py-2">Pernotti</th>
                    <th class="px-4 py-2">Presidi</th>
                    <th class="px-4 py-2">Trasferte Lunghe</th>
                    <th class="px-4 py-2">Trasferte Brevi</th>
                    <th class="px-4 py-2">Estero</th>
                </tr>
            </thead>
            <tbody>
                <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 even:color-gray-700 dark:text-gray-200">
                    <td class="px-4 py-2">NUMERO</td>
                    <td class="px-4 py-2">{{ $giornate_num }}</td>
                    <td class="px-4 py-2">{{ $festivi_italia_num ?: '—' }}</td>
                    <td class="px-4 py-2">{{ $festivi_estero_num ?: '—' }}</td>
                    <td class="px-4 py-2">{{ $straordinari_ore > 0 ? $straordinari_ore . 'h' : '—' }}</td>
                    <td class="px-4 py-2">{{ $trasferte_num }}</td>
                    <td class="px-4 py-2">{{ $pernotti_num }}</td>
                    <td class="px-4 py-2">{{ $presidi_num }}</td>
                    <td class="px-4 py-2">{{ $trasferte_lunghe_num }}</td>
                    <td class="px-4 py-2">{{ $trasferte_brevi_num }}</td>
                    <td class="px-4 py-2">{{ $esteri_num }}</td>
                </tr>
                <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 even:color-gray-700 dark:text-gray-200">
                    <td class="px-4 py-2">COMPENSO</td>
                    <td class="px-4 py-2">{{ $giornate }} €</td>
                    <td class="px-4 py-2">{{ $festivi_italia > 0 ? $festivi_italia . ' €' : '—' }}</td>
                    <td class="px-4 py-2">{{ $festivi_estero > 0 ? $festivi_estero . ' €' : '—' }}</td>
                    <td class="px-4 py-2">{{ $straordinari }} €</td>
                    <td class="px-4 py-2">{{ $trasferte }} €</td>
                    <td class="px-4 py-2">{{ $pernotti }} €</td>
                    <td class="px-4 py-2">{{ $presidi }} €</td>
                    <td class="px-4 py-2">{{ $trasferte_lunghe }} €</td>
                    <td class="px-4 py-2">{{ $trasferte_brevi }} €</td>
                    <td class="px-4 py-2">{{ $esteri }} €</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if(array_sum($note_summary) > 0)
    <div class="mb-6">
        <p class="text-lg text-gray-800 dark:text-gray-200 leading-tight mb-2">
            <strong>Note:</strong>
        </p>
        <div class="flex flex-wrap gap-2">
            @if($note_summary['Ferie'] > 0)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    Ferie &mdash; {{ $note_summary['Ferie'] }} {{ $note_summary['Ferie'] == 1 ? 'giorno' : 'giorni' }}
                </span>
            @endif
            @if($note_summary['Permesso'] > 0)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    Permesso &mdash; {{ $note_summary['Permesso'] }} {{ $note_summary['Permesso'] == 1 ? 'giorno' : 'giorni' }}
                </span>
            @endif
            @if($note_summary['Malattia'] > 0)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    Malattia &mdash; {{ $note_summary['Malattia'] }} {{ $note_summary['Malattia'] == 1 ? 'giorno' : 'giorni' }}
                </span>
            @endif
            @if($note_summary['104'] > 0)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                    104 &mdash; {{ $note_summary['104'] }} {{ $note_summary['104'] == 1 ? 'giorno' : 'giorni' }}
                </span>
            @endif
        </div>
    </div>
    @endif

    <h2 class="text-lg text-gray-800 dark:text-gray-200 leading-tight mb-4">
        <strong>Foglio Orario Complessivo:</strong>
    </h2>
    <form class="gsv-form" method="POST" action="{{ route('timesheets.update', $ts) }}">
        @csrf
        @method('PATCH')

        @php
            $selectedFascia = old('override_fascia', $o_fascia);
        @endphp

        @if($role == "Facchino" && $u_role != "Facchino")
            <x-input-label for="override_fascia" :value="__('Fascia')" />
            <x-select-input id="override_fascia" class="block mt-1 w-full mb-4" type="text" name="override_fascia" :value="old('override_fascia', $u_fascia)" autofocus>
                <option value="">Seleziona una fascia</option>
                <option value="50" {{ $selectedFascia == '50' ? 'selected' : '' }}>50€</option>
                <option value="55" {{ $selectedFascia == '55' ? 'selected' : '' }}>55€</option>
                <option value="60" {{ $selectedFascia == '60' ? 'selected' : '' }}>60€</option>
                <option value="70" {{ $selectedFascia == '70' ? 'selected' : '' }}>70€</option>
            </x-select-input>
        @endif


        <x-input-label for="override_compensation" :value="__('Override Compenso')" />
        <x-text-input id="override_compensation" class="block mt-1 w-full mb-4" type="text" name="override_compensation" :value="old('override_compensation', $o_compensation)" autofocus />

        <x-timesheets.show-edit-timesheet-table :timesheet="$ts" :months="$months" :cols="$cols" />
        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Salva') }}
            </x-primary-button>
        </div>
    </form>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        let override_compensation = document.getElementById('override_compensation');
        override_compensation.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9.]/g, '');
        });
    });
</script>
