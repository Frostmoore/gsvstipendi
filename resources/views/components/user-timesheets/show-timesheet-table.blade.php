<?php

use App\Helpers\DateHelper;
$timesheet = $userTimesheet;
$id = $timesheet->id;
$_month = $timesheet->month;
$month = $months[$_month];
$year = $timesheet->year;
$userid = $timesheet->user;
$timesheet = json_decode($timesheet->link);
$compensi = [];

foreach($users as $u) {
    if($u->id == $userid) {
        $user_fullname = $u->surname . ' ' . $u->name;
        $u_fissa = (float)$u->fissa > 0 ? (float)$u->fissa : 0;
    }
}

// Load per-user rates
$_userRoleRate = \App\Models\UserRoleRate::where('user_id', $userid)->where('role', 'user')->first();

$rate_giornata        = $_userRoleRate ? (float)($_userRoleRate->giornata        ?? 0) : 0;
$rate_feriale_estero  = $_userRoleRate ? (float)($_userRoleRate->feriale_estero  ?? 0) : 0;
$rate_festivo         = $_userRoleRate ? (float)($_userRoleRate->festivo         ?? 0) : 0;
$rate_festivo_estero  = $_userRoleRate ? (float)($_userRoleRate->festivo_estero  ?? 0) : 0;
$rate_trasferta       = $_userRoleRate ? (float)($_userRoleRate->trasferta       ?? 0) : 0;
$rate_trasferta_lunga = $_userRoleRate ? (float)($_userRoleRate->trasferta_lunga ?? 0) : 0;
$rate_pernotto        = $_userRoleRate ? (float)($_userRoleRate->pernotto        ?? 0) : 0;
$rate_presidio        = $_userRoleRate ? (float)($_userRoleRate->presidio        ?? 0) : 0;
$rate_straordinari    = $_userRoleRate ? (float)($_userRoleRate->straordinari    ?? 0) : 0;
$rate_tariffa_sabato  = $_userRoleRate ? (float)($_userRoleRate->tariffa_sabato  ?? 0) : 0;

$fissa_eff = 0;
if ($_userRoleRate && (float)($_userRoleRate->fissa ?? 0) > 0) {
    $fissa_eff = (float)$_userRoleRate->fissa;
} elseif (isset($u_fissa) && $u_fissa > 0) {
    $fissa_eff = $u_fissa;
}

// Dynamic column set — only include columns for rates that are configured
$cols        = ['Data', 'Cliente', 'Luogo', 'Entrata', 'Uscita'];
$allowedKeys = ['Data', 'Cliente', 'Luogo', 'Entrata', 'Uscita'];
if ($rate_trasferta > 0) {
    $cols[] = 'Trasferta';   $allowedKeys[] = 'Trasferta';
    $cols[] = 'TrasfBreve';  $allowedKeys[] = 'TrasfBreve';
}
if ($rate_trasferta_lunga > 0) { $cols[] = 'Trasf. Lunga'; $allowedKeys[] = 'TrasfLunga'; }
if ($rate_pernotto > 0)        { $cols[] = 'Pernotto';     $allowedKeys[] = 'Pernotto'; }
if ($rate_presidio > 0)        { $cols[] = 'Presidio';     $allowedKeys[] = 'Presidio'; }
if ($rate_feriale_estero > 0 || $rate_festivo_estero > 0) {
    $cols[] = 'Estero'; $allowedKeys[] = 'Estero';
}
$cols[] = 'Note'; $allowedKeys[] = 'Note';

$sabati_lavorati = 0;

foreach ($timesheet as $t) {
    $festivo = false;
    $t = json_decode(json_encode($t), true);
    $rowCompensi = [];

    $day = explode(' ', $t['Data'])[1];
    $_month_str = str_pad($_month, 2, '0', STR_PAD_LEFT);
    $day = str_pad($day, 2, '0', STR_PAD_LEFT);
    $true_date_str = $year . '-' . $_month_str . '-' . $day;
    if(DateHelper::isHoliday($true_date_str)) {
        $festivo = true;
    }
    $rowCompensi['data'] = $true_date_str;

    $entrata = array_key_exists('Entrata', $t) ? $t['Entrata'] : null;
    $uscita  = array_key_exists('Uscita', $t)  ? $t['Uscita']  : null;

    if (empty($entrata) || empty($uscita) || $entrata == '00:00' || $uscita == '00:00') {
        continue;
    }

    $is_sabato = strpos($t['Data'], 'Sabato') !== false;
    if ($is_sabato) $sabati_lavorati++;

    $trasferta       = array_key_exists('Trasferta',  $t) ? $t['Trasferta']  : null;
    $pernotto        = array_key_exists('Pernotto',   $t) ? $t['Pernotto']   : null;
    $presidio        = array_key_exists('Presidio',   $t) ? $t['Presidio']   : null;
    $trasferta_lunga = array_key_exists('TrasfLunga', $t) ? $t['TrasfLunga'] : null;
    $trasferta_breve = array_key_exists('TrasfBreve', $t) ? $t['TrasfBreve'] : null;
    $estero          = array_key_exists('Estero',     $t) ? $t['Estero']     : null;

    $ha_flag_speciale = (
        $trasferta == 1 || $pernotto == 1 || $presidio == 1 ||
        $trasferta_lunga == 1 || $trasferta_breve == 1 || $estero == 1
    );

    // Straordinari
    $rowCompensi['straordinari']     = 0;
    $rowCompensi['straordinari_ore'] = 0;
    if (!$ha_flag_speciale) {
        $t1 = DateTime::createFromFormat('H:i', $entrata);
        $t2 = DateTime::createFromFormat('H:i', $uscita);
        if ($t1 && $t2) {
            $difference = $t1->diff($t2);
            $totalHours = $difference->h + ($difference->i / 60);
            if ($totalHours > 9) {
                $extraHours = floor($totalHours - 9);
                $rowCompensi['straordinari']     = $extraHours * $rate_straordinari;
                $rowCompensi['straordinari_ore'] = $extraHours;
            }
        }
    }

    // Giornata base: dipende da festivo × estero
    if (!$festivo) {
        if ($estero == 1) {
            if ($rate_feriale_estero > 0) {
                $rowCompensi['feriale_estero'] = $rate_feriale_estero;
            }
        } else {
            if ($fissa_eff > 0) {
                if ($is_sabato && $sabati_lavorati <= 2) {
                    $rowCompensi['giornata'] = $fissa_eff; // placeholder, recalculated in totals
                } else {
                    $rowCompensi['giornata'] = ($is_sabato && $sabati_lavorati > 2 && $rate_tariffa_sabato > 0)
                        ? $rate_tariffa_sabato : $rate_giornata;
                }
            } else {
                $rowCompensi['giornata'] = ($is_sabato && $sabati_lavorati > 2 && $rate_tariffa_sabato > 0)
                    ? $rate_tariffa_sabato : $rate_giornata;
            }
        }
    } else {
        if ($estero == 1) {
            if ($rate_festivo_estero > 0) {
                $rowCompensi['festivo_estero'] = $rate_festivo_estero;
            }
        } else {
            if ($rate_festivo > 0) {
                $rowCompensi['festivo_italia'] = $rate_festivo;
            }
        }
    }

    if ($trasferta == 1)       $rowCompensi['trasferta']       = $rate_trasferta;
    if ($pernotto == 1)        $rowCompensi['pernotto']        = $rate_pernotto;
    if ($presidio == 1)        $rowCompensi['presidio']        = $rate_presidio;
    if ($trasferta_lunga == 1) $rowCompensi['trasferta_lunga'] = $rate_trasferta_lunga;
    if ($trasferta_breve == 1) $rowCompensi['trasferta_breve'] = $rate_trasferta;

    array_push($compensi, $rowCompensi);
}

// Totals
$trasferte = 0; $pernotti = 0; $presidi = 0;
$trasferte_lunghe = 0; $trasferte_brevi = 0;
$giornate = 0; $feriali_estero = 0;
$festivi_italia = 0; $festivi_estero = 0;
$straordinari = 0;

$trasferte_num = 0; $pernotti_num = 0; $presidi_num = 0;
$trasferte_lunghe_num = 0; $trasferte_brevi_num = 0;
$giornate_num = 0; $feriali_estero_num = 0;
$festivi_italia_num = 0; $festivi_estero_num = 0;
$straordinari_ore = 0;

foreach($compensi as $z) {
    $trasferte        += $z['trasferta']        ?? 0;
    $pernotti         += $z['pernotto']         ?? 0;
    $presidi          += $z['presidio']         ?? 0;
    $trasferte_lunghe += $z['trasferta_lunga']  ?? 0;
    $trasferte_brevi  += $z['trasferta_breve']  ?? 0;
    $giornate         += $z['giornata']         ?? 0;
    $feriali_estero   += $z['feriale_estero']   ?? 0;
    $festivi_italia   += $z['festivo_italia']   ?? 0;
    $festivi_estero   += $z['festivo_estero']   ?? 0;
    $straordinari     += $z['straordinari']     ?? 0;
    $straordinari_ore += $z['straordinari_ore'] ?? 0;

    array_key_exists('trasferta', $z)       ? $trasferte_num++        : null;
    array_key_exists('pernotto', $z)        ? $pernotti_num++         : null;
    array_key_exists('presidio', $z)        ? $presidi_num++          : null;
    array_key_exists('trasferta_lunga', $z) ? $trasferte_lunghe_num++ : null;
    array_key_exists('trasferta_breve', $z) ? $trasferte_brevi_num++  : null;
    array_key_exists('giornata', $z)        ? $giornate_num++         : null;
    array_key_exists('feriale_estero', $z)  ? $feriali_estero_num++   : null;
    array_key_exists('festivo_italia', $z)  ? $festivi_italia_num++   : null;
    array_key_exists('festivo_estero', $z)  ? $festivi_estero_num++   : null;
}

// Apply fissa logic to giornate total
if ($fissa_eff > 0) {
    $sabati_extra = max(0, $sabati_lavorati - 2);
    $tariffa_sabato = $rate_tariffa_sabato > 0
        ? $rate_tariffa_sabato
        : ($giornate_num > 0 ? $fissa_eff / $giornate_num : 0);
    $giornate = $fissa_eff + ($tariffa_sabato * $sabati_extra);
}

$totale = $trasferte + $pernotti + $presidi + $trasferte_lunghe + $trasferte_brevi
        + $giornate + $feriali_estero + $festivi_italia + $festivi_estero + $straordinari;

?>

<div class="title-container mb-4">
    <h2 class="text-lg text-gray-800 dark:text-gray-200 leading-tight">
        {{ $user_fullname }} - {{ $month }} {{ $year }}
    </h2>
</div>

<div class="w-full overflow-x-auto">
    <h2 class="text-lg text-gray-800 dark:text-gray-200 leading-tight mb-4">
        <strong>Foglio Orario Complessivo:</strong>
    </h2>
    <table class="table-fixed w-full gsv-timesheet-table">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
            <tr>
                @foreach($cols as $col)
                    <th class="px-4 py-2">{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($timesheet as $row)
                <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 dark:text-gray-200">
                    @foreach($allowedKeys as $key)
                        @php $value = is_object($row) ? ($row->$key ?? '') : ($row[$key] ?? ''); @endphp
                        <td class="px-4 py-2">
                            @switch($key)
                                @case('Trasferta')
                                @case('Pernotto')
                                @case('Presidio')
                                @case('TrasfLunga')
                                @case('TrasfBreve')
                                @case('Estero')
                                    {{ $value == 1 ? '✔️' : '' }}
                                    @break
                                @default
                                    {{ $value }}
                                    @break
                            @endswitch
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <br />
    <p class="text-lg text-gray-800 dark:text-gray-200 leading-tight">
        <strong>Totale Compenso:</strong>
        <span style="padding:5px;background-color:orange;color:black;font-size:1.5rem;font-weight:bolder;">{{ $totale }}€</span>
    </p>
</div>
