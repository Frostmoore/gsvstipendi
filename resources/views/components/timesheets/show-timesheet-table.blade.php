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
$o_fascia       = $timesheet->override_fascia;
$bonus_list     = is_array($ts->bonuses) ? $ts->bonuses : (json_decode($ts->bonuses ?? '[]', true) ?? []);
$totale_bonus   = array_sum(array_column($bonus_list, 'amount'));
$timesheet = json_decode($timesheet->link);
$compensi = [];

foreach($users as $u) {
    if($u->id == $userid) {
        $user_fullname = $u->surname . ' ' . $u->name;
        $u_fissa = (float)$u->fissa > 0 ? (float)$u->fissa : 0;
        $role = $u->role;
    }
}

// Carica la retribuzione per-utente per-ruolo (giornata, fissa, tariffa_sabato)
$_userRoleRate = \App\Models\UserRoleRate::where('user_id', $userid)
    ->where('role', $ruolo)
    ->first();

// Carica gli override individuali e costruisce la collection effettiva
$userCompOverrides = \App\Models\UserCompensation::where('user_id', $userid)
    ->pluck('value', 'compensation_id');

$effectiveComps = $compensations->map(function($c) use ($userCompOverrides, $_userRoleRate) {
    if (isset($userCompOverrides[$c->id])) {
        $c = clone $c;
        $c->value = $userCompOverrides[$c->id];
    }
    // user_role_rates.giornata sovrascrive la Giornata Lavorativa (massima priorità)
    if ($_userRoleRate && (float)$_userRoleRate->giornata > 0 && $c->name == 'Giornata Lavorativa') {
        $c = clone $c;
        $c->value = $_userRoleRate->giornata;
    }
    return $c;
});

// Determina la fissa effettiva prima del loop (serve nel ramo Autista)
$fissa_eff = 0;
if ($_userRoleRate && (float)$_userRoleRate->fissa > 0) {
    $fissa_eff = (float)$_userRoleRate->fissa;
} elseif ($u_fissa > 0) {
    $fissa_eff = $u_fissa;
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
    $is_sabato = strpos($t['Data'], 'Sabato') !== false;
    if ($is_sabato && !empty($entrata) && !empty($uscita)) {
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
                $x = 0;
                foreach ($effectiveComps as $c) {
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

        foreach($effectiveComps as $c) {
            if(!$festivo) {
                if($c->name == 'Giornata Lavorativa') {
                    if ($is_sabato && $sabati_lavorati > 2 && $_userRoleRate && (float)$_userRoleRate->tariffa_sabato > 0) {
                        $rowCompensi['giornata'] = (float)$_userRoleRate->tariffa_sabato;
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

        foreach($effectiveComps as $c) {
            if(!$festivo) {
                if($c->name == 'Giornata Lavorativa') {
                    if($fissa_eff > 0) {
                        // Dal 3° sabato in poi il compenso passa alla tariffa standard
                        if($sabati_lavorati <= 2) {
                            $rowCompensi['giornata'] = $fissa_eff;
                        } else {
                            $rowCompensi['giornata'] = (float)$c->value;
                        }
                    } else {
                        if ($is_sabato && $sabati_lavorati > 2 && $_userRoleRate && (float)$_userRoleRate->tariffa_sabato > 0) {
                            $rowCompensi['giornata'] = (float)$_userRoleRate->tariffa_sabato;
                        } else {
                            $rowCompensi['giornata'] = (float)$c->value;
                        }
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
        foreach($effectiveComps as $d) {
            if($d->name == 'Feriale Italia') $baseGiornata   = (float)$d->value;
            if($d->name == 'Feriale Estero') $feriale_estero = (float)$d->value;
        }

        if($estero == 1) {
            if(!$festivo) {
                // Feriale estero: giornata base + extra per essere all'estero
                $rowCompensi['giornata'] = $baseGiornata;
                $rowCompensi['estero']   = $feriale_estero - $baseGiornata;
            } else {
                foreach($effectiveComps as $c) {
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
                foreach($effectiveComps as $c) {
                    if($c->name == 'Festivo Italia') {
                        // Festivo italia: giornata base + extra festivo
                        $rowCompensi['giornata']       = $baseGiornata;
                        $rowCompensi['festivo_italia'] = (float)$c->value - $baseGiornata;
                    }
                }
            }
        }

    } else {

        foreach($effectiveComps as $c) {
            if(!$festivo){
                if($c->name == 'Giornata Lavorativa') {
                    if ($is_sabato && $sabati_lavorati > 2 && $_userRoleRate && (float)$_userRoleRate->tariffa_sabato > 0) {
                        $rowCompensi['giornata'] = (float)$_userRoleRate->tariffa_sabato;
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
    }

    if($trasferta == 1) {
        foreach($effectiveComps as $c) {
            if($c->name == 'Trasferta') {
                $rowCompensi['trasferta'] = $c->value;
            }
        }
    }

    if($pernotto == 1) {
        foreach($effectiveComps as $c) {
            if($c->name == 'Pernotto') {
                $rowCompensi['pernotto'] = $c->value;
            }
        }
    }

    if($presidio == 1) {
        foreach($effectiveComps as $c) {
            if($c->name == 'Presidio') {
                $rowCompensi['presidio'] = $c->value;
            }
        }
    }

    if($trasferta_lunga == 1) {
        foreach($effectiveComps as $c) {
            if($c->name == 'Trasf. Lunga') {
                $rowCompensi['trasferta_lunga'] = $c->value;
            }
        }
    }

    if($trasferta_breve == 1) {
        foreach($effectiveComps as $c) {
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

// L'incremento è un importo mensile fisso — legge da $effectiveComps (già overriddato se necessario)
$incrementi = 0;
foreach($effectiveComps as $c) {
    if($c->name == 'Incremento') {
        $incrementi = (float)$c->value;
        break;
    }
}

if($fissa_eff > 0) {
    // Paga base mensile fissa + extra per ogni sabato oltre i primi 2
    $sabati_extra = max(0, $sabati_lavorati - 2);
    // Tariffa 3° sabato: override esplicito > fissa/num_giorni
    if ($_userRoleRate && (float)$_userRoleRate->tariffa_sabato > 0) {
        $tariffa_sabato = (float)$_userRoleRate->tariffa_sabato;
    } else {
        $tariffa_sabato = $giornate_num > 0 ? $fissa_eff / $giornate_num : 0;
    }
    $giornate = $fissa_eff + ($tariffa_sabato * $sabati_extra);
}

if($o_fascia > 0) {
    $giornate = (float)$o_fascia * $giornate_num;
}


$totale = $trasferte + $pernotti + $presidi + $trasferte_lunghe + $trasferte_brevi + $esteri + $giornate + $festivi_italia + $festivi_estero + $straordinari + $incrementi;

if($o_compensation > 0) {
    $totale = (float)$o_compensation;
}

// I bonus/detrazioni si sommano sempre, anche sopra l'override_compensation
$totale += $totale_bonus;

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
        {{-- Mobile: stat cards (hidden on md+) --}}
        <div class="md:hidden grid grid-cols-2 gap-2 mb-4">
            <div class="col-span-2 bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Giornate</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $giornate_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $giornate }}€</span>
                </div>
            </div>
            @if($festivi_italia_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Festivi IT</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $festivi_italia_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $festivi_italia }}€</span>
                </div>
            </div>
            @endif
            @if($festivi_estero_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Festivi EST</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $festivi_estero_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $festivi_estero }}€</span>
                </div>
            </div>
            @endif
            @if($straordinari_ore > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Straordinari</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $straordinari_ore }}h</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $straordinari }}€</span>
                </div>
            </div>
            @endif
            @if($trasferte_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Trasferte</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $trasferte_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $trasferte }}€</span>
                </div>
            </div>
            @endif
            @if($pernotti_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Pernotti</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $pernotti_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $pernotti }}€</span>
                </div>
            </div>
            @endif
            @if($presidi_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Presidi</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $presidi_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $presidi }}€</span>
                </div>
            </div>
            @endif
            @if($trasferte_lunghe_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Trasf. Lunghe</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $trasferte_lunghe_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $trasferte_lunghe }}€</span>
                </div>
            </div>
            @endif
            @if($trasferte_brevi_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Trasf. Brevi</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $trasferte_brevi_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $trasferte_brevi }}€</span>
                </div>
            </div>
            @endif
            @if($esteri_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Estero</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $esteri_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $esteri }}€</span>
                </div>
            </div>
            @endif
        </div>

        {{-- Desktop: table (hidden below md) --}}
        <div class="hidden md:block overflow-x-auto">
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
    </div>

    @if(Auth::user()->role == 'admin' || Auth::user()->role == 'superadmin')
    @php
        $_eff = [];
        foreach($effectiveComps as $_c) { $_eff[$_c->name] = (float)$_c->value; }
        $_hasOverride = $userCompOverrides->isNotEmpty() || $_userRoleRate !== null;
    @endphp
    <div class="mb-6">
        <p class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2"><strong>Formula di Calcolo:</strong></p>
        @if($o_compensation > 0)
            <p class="text-sm italic text-orange-600 dark:text-orange-400">
                ⚠ Compenso impostato manualmente: {{ $o_compensation }}€ — formula automatica ignorata.
                @if($totale_bonus != 0)(Bonus/detrazioni {{ ($totale_bonus >= 0 ? '+' : '') }}{{ $totale_bonus }}€ applicati sopra l'override.)@endif
            </p>
        @else
            @if($_hasOverride)
            <p class="text-xs italic text-indigo-500 dark:text-indigo-400 mb-2">★ = tariffa con override individuale per questo utente</p>
            @endif
            <div class="text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 max-w-lg">
                <dl class="space-y-1">

                    {{-- ── GIORNATE (logica per ruolo) ── --}}
                    @if($role == 'Autista' && $fissa_eff > 0)
                        @php
                            $sabati_extra_n = max(0, $sabati_lavorati - 2);
                            $_sab_tar = ($_userRoleRate && (float)$_userRoleRate->tariffa_sabato > 0)
                                ? (float)$_userRoleRate->tariffa_sabato
                                : ($giornate_num > 0 ? round($fissa_eff / $giornate_num, 2) : 0);
                        @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Paga mensile fissa{{ $_hasOverride ? ' ★' : '' }}:</dt>
                            <dd class="font-semibold text-right">{{ $fissa_eff }}€</dd>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 pl-2 mb-0.5">
                            {{ $sabati_lavorati }} sabato/i lavorato/i:
                            {{ min($sabati_lavorati, 2) }} nel base{{ $sabati_extra_n > 0 ? ' + ' . $sabati_extra_n . ' extra (dal 3°)' : ' (nessun extra)' }}
                        </div>
                        @if($sabati_extra_n > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Dal 3° sabato ({{ $sabati_extra_n }} × {{ ($_userRoleRate && (float)$_userRoleRate->tariffa_sabato > 0) ? '★ ' : '' }}{{ $_sab_tar }}€/sabato):</dt>
                            <dd class="font-semibold text-right">+ {{ round($sabati_extra_n * $_sab_tar, 2) }}€</dd>
                        </div>
                        @endif

                    @elseif($role == 'Magazziniere FIGC')
                        @php
                            $_fi  = $_eff['Feriale Italia'] ?? 0;
                            $_fe  = $_eff['Feriale Estero'] ?? 0;
                            $_fsi = $_eff['Festivo Italia'] ?? 0;
                            $_fse = $_eff['Festivo Estero'] ?? 0;
                            $_gg_est_feriale = $esteri_num - $festivi_estero_num;
                            $_gg_it_feriale  = $giornate_num - $festivi_italia_num - $esteri_num;
                        @endphp
                        @if($_gg_it_feriale > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Feriali Italia ({{ $_gg_it_feriale }} × {{ $_fi }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($_gg_it_feriale * $_fi, 2) }}€</dd>
                        </div>
                        @endif
                        @if($_gg_est_feriale > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Feriali Estero ({{ $_gg_est_feriale }} × {{ $_fe }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($_gg_est_feriale * $_fe, 2) }}€</dd>
                        </div>
                        @endif
                        @if($festivi_italia_num > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Festivi Italia ({{ $festivi_italia_num }} × {{ $_fsi }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($festivi_italia_num * $_fsi, 2) }}€</dd>
                        </div>
                        @endif
                        @if($festivi_estero_num > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Festivi Estero ({{ $festivi_estero_num }} × {{ $_fse }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($festivi_estero_num * $_fse, 2) }}€</dd>
                        </div>
                        @endif

                    @else
                        {{-- Facchino / Autista senza fissa / Default --}}
                        @php
                            $_tar_gg      = ($_userRoleRate && (float)$_userRoleRate->giornata > 0)
                                ? (float)$_userRoleRate->giornata
                                : ($_eff['Giornata Lavorativa'] ?? 0);
                            $_star_gg     = ($_userRoleRate && (float)$_userRoleRate->giornata > 0) ? ' ★' : '';
                            $_has_sab_tar = $_userRoleRate && (float)$_userRoleRate->tariffa_sabato > 0;
                            $_tar_sab_f   = $_has_sab_tar ? (float)$_userRoleRate->tariffa_sabato : 0;
                            $sabati_extra_f  = max(0, $sabati_lavorati - 2);
                            $_gg_normali_f   = $giornate_num - $sabati_extra_f;
                        @endphp
                        @if($o_fascia > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Giornate (fascia override, {{ $giornate_num }} × {{ $o_fascia }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($o_fascia * $giornate_num, 2) }}€</dd>
                        </div>
                        @elseif($_has_sab_tar && $sabati_extra_f > 0)
                            @if($_gg_normali_f > 0)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-600 dark:text-gray-400">Giornate{{ $_star_gg }} ({{ $_gg_normali_f }} × {{ $_tar_gg }}€):</dt>
                                <dd class="font-semibold text-right">{{ round($_gg_normali_f * $_tar_gg, 2) }}€</dd>
                            </div>
                            @endif
                            <div class="text-xs text-gray-500 dark:text-gray-400 pl-2 mb-0.5">
                                {{ $sabati_lavorati }} sabato/i lavorato/i: {{ min(2, $sabati_lavorati) }} nel rate normale + {{ $sabati_extra_f }} extra (dal 3°)
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-600 dark:text-gray-400">Dal 3° sabato ★ ({{ $sabati_extra_f }} × {{ $_tar_sab_f }}€/sabato):</dt>
                                <dd class="font-semibold text-right">+ {{ round($sabati_extra_f * $_tar_sab_f, 2) }}€</dd>
                            </div>
                        @elseif($giornate_num > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Giornate{{ $_star_gg }} ({{ $giornate_num }} × {{ $_tar_gg }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($giornate_num * $_tar_gg, 2) }}€</dd>
                        </div>
                        @if($_has_sab_tar && $sabati_lavorati > 0)
                        <div class="text-xs text-gray-500 dark:text-gray-400 pl-2 mb-0.5">
                            {{ $sabati_lavorati }} sabato/i lavorato/i (tutti nel rate normale, extra dal 3°)
                        </div>
                        @endif
                        @endif
                    @endif

                    {{-- ── Festivi (tutti tranne FIGC che li gestisce sopra) ── --}}
                    @if($role != 'Magazziniere FIGC')
                        @if($festivi_italia_num > 0)
                        @php $_tar_fest = $_eff['Festivo'] ?? 0; @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Festivi Italia ({{ $festivi_italia_num }} × {{ $_tar_fest }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($festivi_italia_num * $_tar_fest, 2) }}€</dd>
                        </div>
                        @endif
                        @if($festivi_estero_num > 0)
                        @php $_tar_fest = $_eff['Festivo'] ?? 0; @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Festivi Estero ({{ $festivi_estero_num }} × {{ $_tar_fest }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($festivi_estero_num * $_tar_fest, 2) }}€</dd>
                        </div>
                        @endif
                        @if($straordinari_ore > 0)
                        @php $_tar_str = $_eff['Straordinari'] ?? 0; @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Straordinari ({{ $straordinari_ore }}h × {{ $_tar_str }}€/h):</dt>
                            <dd class="font-semibold text-right">{{ round($straordinari, 2) }}€</dd>
                        </div>
                        @endif
                        @if($trasferte_num > 0)
                        @php $_tar_tr = $_eff['Trasferta'] ?? 0; @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Trasferte ({{ $trasferte_num }} × {{ $_tar_tr }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($trasferte, 2) }}€</dd>
                        </div>
                        @endif
                        @if($pernotti_num > 0)
                        @php $_tar_p = $_eff['Pernotto'] ?? 0; @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Pernotti ({{ $pernotti_num }} × {{ $_tar_p }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($pernotti, 2) }}€</dd>
                        </div>
                        @endif
                        @if($presidi_num > 0)
                        @php $_tar_pr = $_eff['Presidio'] ?? 0; @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Presidi ({{ $presidi_num }} × {{ $_tar_pr }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($presidi, 2) }}€</dd>
                        </div>
                        @endif
                        @if($trasferte_lunghe_num > 0)
                        @php $_tar_tl = $_eff['Trasf. Lunga'] ?? 0; @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Trasferte Lunghe ({{ $trasferte_lunghe_num }} × {{ $_tar_tl }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($trasferte_lunghe, 2) }}€</dd>
                        </div>
                        @endif
                        @if($trasferte_brevi_num > 0)
                        @php $_tar_tb = $_eff['Trasferta breve'] ?? 0; @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Trasferte Brevi ({{ $trasferte_brevi_num }} × {{ $_tar_tb }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($trasferte_brevi, 2) }}€</dd>
                        </div>
                        @endif
                        @if($esteri_num > 0 && $esteri > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Estero ({{ $esteri_num }} giorni):</dt>
                            <dd class="font-semibold text-right">{{ round($esteri, 2) }}€</dd>
                        </div>
                        @endif
                    @endif

                    {{-- ── Incremento (tutti i ruoli) ── --}}
                    @if($incrementi > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Incremento mensile:</dt>
                        <dd class="font-semibold text-right">+ {{ $incrementi }}€</dd>
                    </div>
                    @endif

                    {{-- ── Totale ── --}}
                    <div class="border-t border-gray-300 dark:border-gray-500 pt-2 mt-1">
                        @php $_subtotale = round($totale - $totale_bonus, 2); @endphp
                        @if($totale_bonus != 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Subtotale:</dt>
                            <dd class="font-semibold text-right">{{ $_subtotale }}€</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Bonus / Detrazioni:</dt>
                            <dd class="font-semibold text-right {{ $totale_bonus >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ ($totale_bonus >= 0 ? '+' : '') }}{{ $totale_bonus }}€</dd>
                        </div>
                        @endif
                        <div class="flex justify-between gap-4 font-bold text-base mt-1">
                            <dt class="text-gray-800 dark:text-gray-100">= Totale:</dt>
                            <dd class="text-gray-800 dark:text-gray-100">{{ $totale }}€</dd>
                        </div>
                    </div>

                </dl>
            </div>
        @endif
    </div>
    @endif

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
            <x-select-input id="override_fascia" class="block mt-1 w-full mb-4" type="text" name="override_fascia" :value="old('override_fascia', $o_fascia)" autofocus>
                <option value="">Seleziona una fascia</option>
                <option value="50" {{ $selectedFascia == '50' ? 'selected' : '' }}>50€</option>
                <option value="55" {{ $selectedFascia == '55' ? 'selected' : '' }}>55€</option>
                <option value="60" {{ $selectedFascia == '60' ? 'selected' : '' }}>60€</option>
                <option value="70" {{ $selectedFascia == '70' ? 'selected' : '' }}>70€</option>
            </x-select-input>
        @endif


        <x-input-label for="override_compensation" :value="__('Override Compenso')" />
        <x-text-input id="override_compensation" class="block mt-1 w-full mb-4" type="text" name="override_compensation" :value="old('override_compensation', $o_compensation)" autofocus />

        @if(Auth::user()->role == 'admin' || Auth::user()->role == 'superadmin')
        <fieldset class="w-full overflow-hidden border border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-6">
            <legend class="px-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Bonus e Detrazioni</legend>

            <div class="flex flex-col md:flex-row gap-3 md:items-end mb-4">
                <div class="w-full md:w-40">
                    <x-input-label for="bonus_amount" :value="__('Importo (€)')" />
                    <x-text-input id="bonus_amount" class="block mt-1 w-full" type="number" step="0.01" placeholder="es. 50 o -30" />
                </div>
                <div class="w-full md:flex-1">
                    <x-input-label for="bonus_note" :value="__('Motivazione')" />
                    <x-text-input id="bonus_note" class="block mt-1 w-full" type="text" placeholder="es. Bon..." />
                </div>
                <div class="w-full md:w-auto md:flex-shrink-0 md:pb-0.5">
                    <button type="button" id="add_bonus_btn"
                        class="w-full md:w-auto inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none transition ease-in-out duration-150">
                        + Aggiungi
                    </button>
                </div>
            </div>

            <div id="bonus_list_container"></div>
            <input type="hidden" name="bonuses" id="bonuses_hidden" value="{{ old('bonuses', json_encode($bonus_list)) }}" />
        </fieldset>
        @endif

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

        // ── Override compenso: solo numeri ──────────────────────────────────
        let override_compensation = document.getElementById('override_compensation');
        override_compensation.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9.]/g, '');
        });

        // ── Bonus e Detrazioni ──────────────────────────────────────────────
        const bonusContainer = document.getElementById('bonus_list_container');
        if (!bonusContainer) return; // sezione non presente (utente non admin)

        let bonusEntries = JSON.parse(document.getElementById('bonuses_hidden').value || '[]');

        function renderBonusList() {
            const hidden = document.getElementById('bonuses_hidden');
            hidden.value = JSON.stringify(bonusEntries);

            if (bonusEntries.length === 0) {
                bonusContainer.innerHTML =
                    '<p class="text-sm text-gray-400 dark:text-gray-500 italic">Nessun bonus o detrazione aggiunto.</p>';
                return;
            }

            if (window.innerWidth < 768) {
                // Mobile: cards
                let html = '<div class="space-y-2">';
                bonusEntries.forEach(function(entry, index) {
                    const isBonus = parseFloat(entry.amount) >= 0;
                    const colorClass = isBonus
                        ? 'bg-green-50 dark:bg-green-950 text-green-800 dark:text-green-200 border-green-300 dark:border-green-800'
                        : 'bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border-red-300 dark:border-red-800';
                    const sign   = isBonus ? '+' : '';
                    const amount = sign + parseFloat(entry.amount).toFixed(2) + ' €';
                    html += `<div class="flex items-center gap-2 rounded-lg border p-3 ${colorClass}">`;
                    html += `<span class="font-bold text-sm w-20 flex-shrink-0">${amount}</span>`;
                    html += `<span class="text-sm flex-1 min-w-0 break-words">${entry.note}</span>`;
                    html += `<button type="button" onclick="removeBonus(${index})" class="text-xs hover:underline flex-shrink-0">Elimina</button>`;
                    html += `</div>`;
                });
                html += '</div>';
                bonusContainer.innerHTML = html;
            } else {
                // Desktop: table
                let html = '<table class="w-full text-sm border-collapse rounded overflow-hidden">';
                html += '<thead><tr class="text-left bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">';
                html += '<th class="px-3 py-1.5 w-28">Importo</th>';
                html += '<th class="px-3 py-1.5">Motivazione</th>';
                html += '<th class="px-3 py-1.5 w-20"></th>';
                html += '</tr></thead><tbody>';

                bonusEntries.forEach(function(entry, index) {
                    const isBonus  = parseFloat(entry.amount) >= 0;
                    const rowClass = isBonus
                        ? 'bg-green-50 dark:bg-green-950 text-green-800 dark:text-green-200'
                        : 'bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200';
                    const sign   = isBonus ? '+' : '';
                    const amount = sign + parseFloat(entry.amount).toFixed(2) + ' €';

                    html += `<tr class="${rowClass} border-t border-gray-200 dark:border-gray-600">`;
                    html += `<td class="px-3 py-1.5 font-semibold">${amount}</td>`;
                    html += `<td class="px-3 py-1.5">${entry.note}</td>`;
                    html += `<td class="px-3 py-1.5 text-right">`;
                    html += `<button type="button" onclick="removeBonus(${index})"`;
                    html += ` class="text-xs text-red-600 dark:text-red-400 hover:underline">Elimina</button>`;
                    html += `</td></tr>`;
                });

                html += '</tbody></table>';
                bonusContainer.innerHTML = html;
            }
        }

        window.removeBonus = function(index) {
            bonusEntries.splice(index, 1);
            renderBonusList(); // aggiorna il hidden input prima del submit
            document.querySelector('form.gsv-form').submit();
        };

        document.getElementById('add_bonus_btn').addEventListener('click', function() {
            const amountInput = document.getElementById('bonus_amount');
            const noteInput   = document.getElementById('bonus_note');
            const amount      = parseFloat(amountInput.value);
            const note        = noteInput.value.trim();

            if (isNaN(amount) || amount === 0) {
                amountInput.focus();
                return;
            }
            if (!note) {
                noteInput.focus();
                return;
            }

            bonusEntries.push({ amount: amount, note: note });
            renderBonusList(); // aggiorna il hidden input prima del submit

            // Salva e ricarica la pagina così i totali vengono ricalcolati dal server
            document.querySelector('form.gsv-form').submit();
        });

        renderBonusList(); // render iniziale con i dati salvati
    });
</script>
