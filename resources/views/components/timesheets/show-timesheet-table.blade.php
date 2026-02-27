<?php

use App\Helpers\DateHelper;

$id = $timesheet->id;
$ts = $timesheet;
$_month = $timesheet->month;
$month = $months[$_month];
$year = $timesheet->year;
$userid = $timesheet->user;
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
    }
}

// Load per-user rates (role='user')
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

// Fissa effettiva (user_role_rates.fissa overrides users.fissa)
$fissa_eff = 0;
if ($_userRoleRate && (float)($_userRoleRate->fissa ?? 0) > 0) {
    $fissa_eff = (float)$_userRoleRate->fissa;
} elseif (isset($u_fissa) && $u_fissa > 0) {
    $fissa_eff = $u_fissa;
}

// Dynamic column set — only include columns for rates that are configured
$cols    = ['Data', 'Cliente', 'Luogo', 'Entrata', 'Uscita'];
$colKeys = ['Data', 'Cliente', 'Luogo', 'Entrata', 'Uscita'];
if ($rate_trasferta > 0) {
    $cols[] = 'Trasferta'; $colKeys[] = 'TrasfBreve';
}
if ($rate_trasferta_lunga > 0) { $cols[] = 'Trasf. Lunga'; $colKeys[] = 'TrasfLunga'; }
if ($rate_pernotto > 0)        { $cols[] = 'Pernotto';     $colKeys[] = 'Pernotto'; }
if ($rate_presidio > 0)        { $cols[] = 'Presidio';     $colKeys[] = 'Presidio'; }
if ($rate_feriale_estero > 0 || $rate_festivo_estero > 0) {
    $cols[] = 'Estero'; $colKeys[] = 'Estero';
}
$cols[] = 'Note'; $colKeys[] = 'Note';

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
    if (!$ha_flag_speciale && !empty($entrata) && !empty($uscita)) {
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
            // Feriale Estero — sovrascrive completamente la giornata Italia
            if ($rate_feriale_estero > 0) {
                $rowCompensi['feriale_estero'] = $rate_feriale_estero;
            }
        } else {
            // Feriale Italia (con logica fissa + sabato)
            if ($fissa_eff > 0) {
                if ($is_sabato && $sabati_lavorati <= 2) {
                    $rowCompensi['giornata'] = $fissa_eff; // placeholder, overridden in totals
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
            // Festivo Estero
            if ($rate_festivo_estero > 0) {
                $rowCompensi['festivo_estero'] = $rate_festivo_estero;
            }
        } else {
            // Festivo Italia
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

// Raccoglie le note da TUTTE le giornate (anche senza entrata/uscita)
$note_summary = ['Ferie' => 0, 'Permesso' => 0, 'Malattia' => 0, '104' => 0, 'Smart Working' => 0];
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

// Totals
$trasferte        = 0; $pernotti              = 0; $presidi              = 0;
$trasferte_lunghe = 0; $trasferte_brevi       = 0;
$giornate         = 0; $feriali_estero        = 0;
$festivi_italia   = 0; $festivi_estero        = 0;
$straordinari     = 0;

$trasferte_num        = 0; $pernotti_num             = 0; $presidi_num              = 0;
$trasferte_lunghe_num = 0; $trasferte_brevi_num      = 0;
$giornate_num         = 0; $feriali_estero_num        = 0;
$festivi_italia_num   = 0; $festivi_estero_num        = 0;
$straordinari_ore     = 0;

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

    array_key_exists('trasferta', $z)       ? $trasferte_num++         : null;
    array_key_exists('pernotto', $z)        ? $pernotti_num++          : null;
    array_key_exists('presidio', $z)        ? $presidi_num++           : null;
    array_key_exists('trasferta_lunga', $z) ? $trasferte_lunghe_num++  : null;
    array_key_exists('trasferta_breve', $z) ? $trasferte_brevi_num++   : null;
    array_key_exists('giornata', $z)        ? $giornate_num++          : null;
    array_key_exists('feriale_estero', $z)  ? $feriali_estero_num++    : null;
    array_key_exists('festivo_italia', $z)  ? $festivi_italia_num++    : null;
    array_key_exists('festivo_estero', $z)  ? $festivi_estero_num++    : null;
}

// Apply fissa logic to giornate total
if ($fissa_eff > 0) {
    $sabati_extra = max(0, $sabati_lavorati - 2);
    $tariffa_sabato_calc = $rate_tariffa_sabato > 0
        ? $rate_tariffa_sabato
        : ($giornate_num > 0 ? $fissa_eff / $giornate_num : 0);
    $giornate = $fissa_eff + ($tariffa_sabato_calc * $sabati_extra);
}

// Per-timesheet giornata override
if ($o_fascia > 0) {
    $giornate = (float)$o_fascia * $giornate_num;
}

$totale = $trasferte + $pernotti + $presidi + $trasferte_lunghe + $trasferte_brevi
        + $giornate + $feriali_estero + $festivi_italia + $festivi_estero + $straordinari;

if ($o_compensation > 0) {
    $totale = (float)$o_compensation;
}

// Bonus/detrazioni always apply (even over override_compensation)
$totale += $totale_bonus;

?>

<div class="title-container mb-4">
    <h2 class="text-lg text-gray-800 dark:text-gray-200 leading-tight">
        {{ $user_fullname }} - {{ $month }} {{ $year }}
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
        {{-- Mobile: stat cards --}}
        <div class="md:hidden grid grid-cols-2 gap-2 mb-4">
            @if($giornate_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Feriale Italia</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $giornate_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $giornate }}€</span>
                </div>
            </div>
            @endif
            @if($feriali_estero_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Feriale Estero</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $feriali_estero_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $feriali_estero }}€</span>
                </div>
            </div>
            @endif
            @if($festivi_italia_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Festivo Italia</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $festivi_italia_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $festivi_italia }}€</span>
                </div>
            </div>
            @endif
            @if($festivi_estero_num > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Festivo Estero</div>
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
            @if(($trasferte_num + $trasferte_brevi_num) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-200 dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Trasferta</div>
                <div class="flex justify-between items-baseline mt-0.5">
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $trasferte_num + $trasferte_brevi_num }}</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $trasferte + $trasferte_brevi }}€</span>
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
        </div>

        {{-- Desktop: table --}}
        <div class="hidden md:block overflow-x-auto">
        <table class="table-fixed w-full gsv-timesheet-table">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-2"></th>
                    @if($rate_giornata > 0 || $fissa_eff > 0)
                    <th class="px-4 py-2">Feriale Italia</th>
                    @endif
                    @if($rate_feriale_estero > 0)
                    <th class="px-4 py-2">Feriale Estero</th>
                    @endif
                    @if($rate_festivo > 0)
                    <th class="px-4 py-2">Festivo Italia</th>
                    @endif
                    @if($rate_festivo_estero > 0)
                    <th class="px-4 py-2">Festivo Estero</th>
                    @endif
                    @if($rate_straordinari > 0)
                    <th class="px-4 py-2">Straordinari</th>
                    @endif
                    @if($rate_trasferta > 0)
                    <th class="px-4 py-2">Trasferta</th>
                    @endif
                    @if($rate_pernotto > 0)
                    <th class="px-4 py-2">Pernotti</th>
                    @endif
                    @if($rate_presidio > 0)
                    <th class="px-4 py-2">Presidi</th>
                    @endif
                    @if($rate_trasferta_lunga > 0)
                    <th class="px-4 py-2">Trasf. Lunghe</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 dark:text-gray-200">
                    <td class="px-4 py-2">NUMERO</td>
                    @if($rate_giornata > 0 || $fissa_eff > 0)
                    <td class="px-4 py-2">{{ $giornate_num ?: '—' }}</td>
                    @endif
                    @if($rate_feriale_estero > 0)
                    <td class="px-4 py-2">{{ $feriali_estero_num ?: '—' }}</td>
                    @endif
                    @if($rate_festivo > 0)
                    <td class="px-4 py-2">{{ $festivi_italia_num ?: '—' }}</td>
                    @endif
                    @if($rate_festivo_estero > 0)
                    <td class="px-4 py-2">{{ $festivi_estero_num ?: '—' }}</td>
                    @endif
                    @if($rate_straordinari > 0)
                    <td class="px-4 py-2">{{ $straordinari_ore > 0 ? $straordinari_ore . 'h' : '—' }}</td>
                    @endif
                    @if($rate_trasferta > 0)
                    <td class="px-4 py-2">{{ ($trasferte_num + $trasferte_brevi_num) ?: '—' }}</td>
                    @endif
                    @if($rate_pernotto > 0)
                    <td class="px-4 py-2">{{ $pernotti_num ?: '—' }}</td>
                    @endif
                    @if($rate_presidio > 0)
                    <td class="px-4 py-2">{{ $presidi_num ?: '—' }}</td>
                    @endif
                    @if($rate_trasferta_lunga > 0)
                    <td class="px-4 py-2">{{ $trasferte_lunghe_num ?: '—' }}</td>
                    @endif
                </tr>
                <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 dark:text-gray-200">
                    <td class="px-4 py-2">COMPENSO</td>
                    @if($rate_giornata > 0 || $fissa_eff > 0)
                    <td class="px-4 py-2">{{ $giornate > 0 ? $giornate . ' €' : '—' }}</td>
                    @endif
                    @if($rate_feriale_estero > 0)
                    <td class="px-4 py-2">{{ $feriali_estero > 0 ? $feriali_estero . ' €' : '—' }}</td>
                    @endif
                    @if($rate_festivo > 0)
                    <td class="px-4 py-2">{{ $festivi_italia > 0 ? $festivi_italia . ' €' : '—' }}</td>
                    @endif
                    @if($rate_festivo_estero > 0)
                    <td class="px-4 py-2">{{ $festivi_estero > 0 ? $festivi_estero . ' €' : '—' }}</td>
                    @endif
                    @if($rate_straordinari > 0)
                    <td class="px-4 py-2">{{ $straordinari > 0 ? $straordinari . ' €' : '—' }}</td>
                    @endif
                    @if($rate_trasferta > 0)
                    <td class="px-4 py-2">{{ ($trasferte + $trasferte_brevi) > 0 ? ($trasferte + $trasferte_brevi) . ' €' : '—' }}</td>
                    @endif
                    @if($rate_pernotto > 0)
                    <td class="px-4 py-2">{{ $pernotti > 0 ? $pernotti . ' €' : '—' }}</td>
                    @endif
                    @if($rate_presidio > 0)
                    <td class="px-4 py-2">{{ $presidi > 0 ? $presidi . ' €' : '—' }}</td>
                    @endif
                    @if($rate_trasferta_lunga > 0)
                    <td class="px-4 py-2">{{ $trasferte_lunghe > 0 ? $trasferte_lunghe . ' €' : '—' }}</td>
                    @endif
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    @if(Auth::user()->role == 'admin' || Auth::user()->role == 'superadmin')
    <div class="mb-6">
        <p class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2"><strong>Formula di Calcolo:</strong></p>
        @if($o_compensation > 0)
            <p class="text-sm italic text-orange-600 dark:text-orange-400">
                ⚠ Compenso impostato manualmente: {{ $o_compensation }}€ — formula automatica ignorata.
                @if($totale_bonus != 0)(Bonus/detrazioni {{ ($totale_bonus >= 0 ? '+' : '') }}{{ $totale_bonus }}€ applicati sopra l'override.)@endif
            </p>
        @else
            @if($_userRoleRate !== null)
            <p class="text-xs italic text-indigo-500 dark:text-indigo-400 mb-2">★ = tariffa individuale per questo utente</p>
            @endif
            <div class="text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 max-w-lg">
                <dl class="space-y-1">

                    {{-- Giornate --}}
                    @if($fissa_eff > 0)
                        @php
                            $sabati_extra_n = max(0, $sabati_lavorati - 2);
                            $_sab_tar = $rate_tariffa_sabato > 0
                                ? $rate_tariffa_sabato
                                : ($giornate_num > 0 ? round($fissa_eff / $giornate_num, 2) : 0);
                        @endphp
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Paga mensile fissa ★:</dt>
                            <dd class="font-semibold text-right">{{ $fissa_eff }}€</dd>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 pl-2 mb-0.5">
                            {{ $sabati_lavorati }} sabato/i lavorato/i:
                            {{ min($sabati_lavorati, 2) }} nel base{{ $sabati_extra_n > 0 ? ' + ' . $sabati_extra_n . ' extra (dal 3°)' : ' (nessun extra)' }}
                        </div>
                        @if($sabati_extra_n > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Dal 3° sabato ({{ $sabati_extra_n }} × {{ $rate_tariffa_sabato > 0 ? '★ ' : '' }}{{ $_sab_tar }}€/sabato):</dt>
                            <dd class="font-semibold text-right">+ {{ round($sabati_extra_n * $_sab_tar, 2) }}€</dd>
                        </div>
                        @endif
                    @else
                        @php
                            $_tar_gg      = $rate_giornata;
                            $_star_gg     = ($_userRoleRate && $rate_giornata > 0) ? ' ★' : '';
                            $_has_sab_tar = $rate_tariffa_sabato > 0;
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
                                {{ $sabati_lavorati }} sabato/i: {{ min(2, $sabati_lavorati) }} normale + {{ $sabati_extra_f }} extra ★ (dal 3°)
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-600 dark:text-gray-400">Dal 3° sabato ★ ({{ $sabati_extra_f }} × {{ $rate_tariffa_sabato }}€):</dt>
                                <dd class="font-semibold text-right">+ {{ round($sabati_extra_f * $rate_tariffa_sabato, 2) }}€</dd>
                            </div>
                        @elseif($giornate_num > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Giornate{{ $_star_gg }} ({{ $giornate_num }} × {{ $_tar_gg }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($giornate_num * $_tar_gg, 2) }}€</dd>
                        </div>
                        @endif
                    @endif

                    {{-- Feriale Estero --}}
                    @if($feriali_estero_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Feriale Estero ({{ $feriali_estero_num }} × {{ $rate_feriale_estero }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($feriali_estero, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Festivo Italia --}}
                    @if($festivi_italia_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Festivo Italia ({{ $festivi_italia_num }} × {{ $rate_festivo }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($festivi_italia, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Festivo Estero --}}
                    @if($festivi_estero_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Festivo Estero ({{ $festivi_estero_num }} × {{ $rate_festivo_estero }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($festivi_estero, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Straordinari --}}
                    @if($straordinari_ore > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Straordinari ({{ $straordinari_ore }}h × {{ $rate_straordinari }}€/h):</dt>
                        <dd class="font-semibold text-right">{{ round($straordinari, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Trasferta (brevi + legacy Trasferta) --}}
                    @if(($trasferte_num + $trasferte_brevi_num) > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Trasferta ({{ $trasferte_num + $trasferte_brevi_num }} × {{ $rate_trasferta }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($trasferte + $trasferte_brevi, 2) }}€</dd>
                    </div>
                    @endif
                    @if($pernotti_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Pernotti ({{ $pernotti_num }} × {{ $rate_pernotto }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($pernotti, 2) }}€</dd>
                    </div>
                    @endif
                    @if($presidi_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Presidi ({{ $presidi_num }} × {{ $rate_presidio }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($presidi, 2) }}€</dd>
                    </div>
                    @endif
                    @if($trasferte_lunghe_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Trasferte Lunghe ({{ $trasferte_lunghe_num }} × {{ $rate_trasferta_lunga }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($trasferte_lunghe, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Totale --}}
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
            @if($note_summary['Smart Working'] > 0)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
                    Smart Working &mdash; {{ $note_summary['Smart Working'] }} {{ $note_summary['Smart Working'] == 1 ? 'giorno' : 'giorni' }}
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

        <x-input-label for="override_fascia" :value="__('Override Giornata (€/giornata — sovrascrive la tariffa individuale)')" />
        <x-text-input id="override_fascia" class="block mt-1 w-full mb-4" type="number" step="0.01" name="override_fascia" :value="old('override_fascia', $o_fascia)" autofocus />

        <x-input-label for="override_compensation" :value="__('Override Compenso Totale')" />
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

        <x-timesheets.show-edit-timesheet-table :timesheet="$ts" :months="$months" :cols="$cols" :col-keys="$colKeys" />
        <div class="flex items-center justify-end mt-4 mb-8">
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

        const bonusContainer = document.getElementById('bonus_list_container');
        if (!bonusContainer) return;

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
            renderBonusList();
            document.querySelector('form.gsv-form').submit();
        };

        document.getElementById('add_bonus_btn').addEventListener('click', function() {
            const amountInput = document.getElementById('bonus_amount');
            const noteInput   = document.getElementById('bonus_note');
            const amount      = parseFloat(amountInput.value);
            const note        = noteInput.value.trim();

            if (isNaN(amount) || amount === 0) { amountInput.focus(); return; }
            if (!note) { noteInput.focus(); return; }

            bonusEntries.push({ amount: amount, note: note });
            renderBonusList();
            document.querySelector('form.gsv-form').submit();
        });

        renderBonusList();
    });
</script>
