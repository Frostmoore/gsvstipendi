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

$rate_figc_feriale_italia      = $_userRoleRate ? (float)($_userRoleRate->figc_feriale_italia      ?? 0) : 0;
$rate_feriale_estero           = $_userRoleRate ? (float)($_userRoleRate->feriale_estero           ?? 0) : 0;
$rate_figc_festivo_italia      = $_userRoleRate ? (float)($_userRoleRate->figc_festivo_italia      ?? 0) : 0;
$rate_festivo_estero           = $_userRoleRate ? (float)($_userRoleRate->festivo_estero           ?? 0) : 0;
$rate_figc_trasp_aut           = $_userRoleRate ? (float)($_userRoleRate->figc_trasp_autista       ?? 0) : 0;
$rate_figc_trasp_acmp          = $_userRoleRate ? (float)($_userRoleRate->figc_trasp_accompagnatore ?? 0) : 0;
$rate_presidio_aut             = $_userRoleRate ? (float)($_userRoleRate->presidio_autisti         ?? 0) : 0;
$rate_presidio_acmp            = $_userRoleRate ? (float)($_userRoleRate->presidio_accompagnatori  ?? 0) : 0;
$rate_autista_nofigc           = $_userRoleRate ? (float)($_userRoleRate->autista_no_figc          ?? 0) : 0;
$rate_trasf_breve              = $_userRoleRate ? (float)($_userRoleRate->trasferta                ?? 0) : 0;
$rate_trasf_media              = $_userRoleRate ? (float)($_userRoleRate->trasferta_media          ?? 0) : 0;
$rate_trasf_lunga              = $_userRoleRate ? (float)($_userRoleRate->trasferta_lunga          ?? 0) : 0;
$rate_pernotto                 = $_userRoleRate ? (float)($_userRoleRate->pernotto                 ?? 0) : 0;
$rate_sielte                   = $_userRoleRate ? (float)($_userRoleRate->sielte                   ?? 0) : 0;
$rate_pernotto_sielte          = $_userRoleRate ? (float)($_userRoleRate->pernotto_sielte          ?? 0) : 0;
$rate_straordinari             = $_userRoleRate ? (float)($_userRoleRate->straordinari             ?? 0) : 0;
$rate_tariffa_sabato           = $_userRoleRate ? (float)($_userRoleRate->tariffa_sabato           ?? 0) : 0;

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
if ($rate_feriale_estero > 0 || $rate_festivo_estero > 0) { $cols[] = 'Estero';         $colKeys[] = 'Estero'; }
if ($rate_figc_trasp_aut > 0)   { $cols[] = 'FIGC Trasp. Autista'; $colKeys[] = 'FigcTraspAut'; }
if ($rate_figc_trasp_acmp > 0)  { $cols[] = 'FIGC Trasp. Accomp.';  $colKeys[] = 'FigcTraspAccomp'; }
if ($rate_presidio_aut > 0)     { $cols[] = 'Presidio Autisti';     $colKeys[] = 'PresidioAut'; }
if ($rate_presidio_acmp > 0)    { $cols[] = 'Presidio Accomp.';     $colKeys[] = 'PresidioAccomp'; }
if ($rate_autista_nofigc > 0)   { $cols[] = 'Autista no FIGC';      $colKeys[] = 'AutistaNoFigc'; }
if ($rate_trasf_breve > 0)      { $cols[] = 'Trasf. Breve <230km';  $colKeys[] = 'TrasfBreve'; }
if ($rate_trasf_media > 0)      { $cols[] = 'Trasf. Media <300km';  $colKeys[] = 'TrasfMedia'; }
if ($rate_trasf_lunga > 0)      { $cols[] = 'Trasf. Lunga >300km';  $colKeys[] = 'TrasfLunga'; }
if ($rate_pernotto > 0)         { $cols[] = 'Pernotto';        $colKeys[] = 'Pernotto'; }
if ($rate_sielte > 0)           { $cols[] = 'SIELTE';          $colKeys[] = 'Sielte'; }
if ($rate_pernotto_sielte > 0)  { $cols[] = 'Pernotto SIELTE';     $colKeys[] = 'PernSielte'; }
$cols[] = 'Comp. Atteso (€)'; $colKeys[] = 'CompensoAtteso';
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
    $rowCompensi['data']    = $true_date_str;
    $rowCompensi['cliente'] = $t['Cliente'] ?? '';

    $entrata = array_key_exists('Entrata', $t) ? $t['Entrata'] : null;
    $uscita  = array_key_exists('Uscita', $t)  ? $t['Uscita']  : null;

    if (empty($entrata) || empty($uscita) || $entrata == '00:00' || $uscita == '00:00') {
        continue;
    }

    $is_sabato = strpos($t['Data'], 'Sabato') !== false;
    if ($is_sabato) $sabati_lavorati++;

    $estero         = array_key_exists('Estero',         $t) ? $t['Estero']         : null;
    $figc_tr_aut    = array_key_exists('FigcTraspAut',   $t) ? $t['FigcTraspAut']   : null;
    $figc_tr_acmp   = array_key_exists('FigcTraspAccomp',$t) ? $t['FigcTraspAccomp']: null;
    $pres_aut       = array_key_exists('PresidioAut',    $t) ? $t['PresidioAut']    : null;
    $pres_aut      = $pres_aut ?? (array_key_exists('Presidio', $t) ? $t['Presidio'] : null); // legacy
    $pres_acmp      = array_key_exists('PresidioAccomp', $t) ? $t['PresidioAccomp'] : null;
    $aut_nofigc     = array_key_exists('AutistaNoFigc',  $t) ? $t['AutistaNoFigc']  : null;
    $trasf_breve    = array_key_exists('TrasfBreve',     $t) ? $t['TrasfBreve']     : null;
    $trasf_breve   = $trasf_breve ?? (array_key_exists('Trasferta', $t) ? $t['Trasferta'] : null); // legacy
    $trasf_media    = array_key_exists('TrasfMedia',     $t) ? $t['TrasfMedia']     : null;
    $trasf_lunga    = array_key_exists('TrasfLunga',     $t) ? $t['TrasfLunga']     : null;
    $pernotto       = array_key_exists('Pernotto',       $t) ? $t['Pernotto']       : null;
    $sielte         = array_key_exists('Sielte',         $t) ? $t['Sielte']         : null;
    $pern_sielte    = array_key_exists('PernSielte',     $t) ? $t['PernSielte']     : null;

    $ha_flag_speciale = (
        $estero == 1 || $figc_tr_aut == 1 || $figc_tr_acmp == 1 ||
        $pres_aut == 1 || $pres_acmp == 1 || $aut_nofigc == 1 ||
        $trasf_breve == 1 || $trasf_media == 1 || $trasf_lunga == 1 || $pernotto == 1 ||
        $sielte == 1 || $pern_sielte == 1
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
                $rowCompensi['fer_estero'] = $rate_feriale_estero;
            }
        } else {
            // Feriale Italia (con logica fissa + sabato)
            if ($fissa_eff > 0) {
                if ($is_sabato && $sabati_lavorati <= 2) {
                    $rowCompensi['figc_fer_it'] = $fissa_eff; // placeholder, overridden in totals
                } else {
                    $rowCompensi['figc_fer_it'] = ($is_sabato && $sabati_lavorati > 2 && $rate_tariffa_sabato > 0)
                        ? $rate_tariffa_sabato : $rate_figc_feriale_italia;
                }
            } else {
                $rowCompensi['figc_fer_it'] = ($is_sabato && $sabati_lavorati > 2 && $rate_tariffa_sabato > 0)
                    ? $rate_tariffa_sabato : $rate_figc_feriale_italia;
            }
        }
    } else {
        if ($estero == 1) {
            // Festivo Estero
            if ($rate_festivo_estero > 0) {
                $rowCompensi['fest_estero'] = $rate_festivo_estero;
            }
        } else {
            // Festivo Italia
            if ($rate_figc_festivo_italia > 0) {
                $rowCompensi['figc_fest_it'] = $rate_figc_festivo_italia;
            }
        }
    }

    if ($figc_tr_aut == 1)   $rowCompensi['figc_tr_aut']  = $rate_figc_trasp_aut;
    if ($figc_tr_acmp == 1)  $rowCompensi['figc_tr_acmp'] = $rate_figc_trasp_acmp;
    if ($pres_aut == 1)      $rowCompensi['pres_aut']     = $rate_presidio_aut;
    if ($pres_acmp == 1)     $rowCompensi['pres_acmp']    = $rate_presidio_acmp;
    if ($aut_nofigc == 1)    $rowCompensi['aut_nofigc']   = $rate_autista_nofigc;
    if ($trasf_breve == 1)   $rowCompensi['trasf_breve']  = $rate_trasf_breve;
    if ($trasf_media == 1)   $rowCompensi['trasf_media']  = $rate_trasf_media;
    if ($trasf_lunga == 1)   $rowCompensi['trasf_lunga']  = $rate_trasf_lunga;
    if ($pernotto == 1)      $rowCompensi['pernotto']     = $rate_pernotto;
    if ($sielte == 1)        $rowCompensi['sielte']       = $rate_sielte;
    if ($pern_sielte == 1)   $rowCompensi['pern_sielte']      = $rate_pernotto_sielte;

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
$figc_fer_it      = 0; $figc_fer_it_num      = 0;
$figc_fest_it     = 0; $figc_fest_it_num     = 0;
$fer_estero       = 0; $fer_estero_num       = 0;
$fest_estero      = 0; $fest_estero_num      = 0;
$figc_tr_aut_tot  = 0; $figc_tr_aut_num      = 0;
$figc_tr_acmp_tot = 0; $figc_tr_acmp_num     = 0;
$pres_aut_tot     = 0; $pres_aut_num         = 0;
$pres_acmp_tot    = 0; $pres_acmp_num        = 0;
$aut_nofigc_tot   = 0; $aut_nofigc_num       = 0;
$trasf_breve_tot  = 0; $trasf_breve_num      = 0;
$trasf_media_tot  = 0; $trasf_media_num      = 0;
$trasf_lunga_tot  = 0; $trasf_lunga_num      = 0;
$pernotto_tot       = 0; $pernotto_num       = 0;
$sielte_tot         = 0; $sielte_num         = 0;
$pern_sielte_tot    = 0; $pern_sielte_num    = 0;
$straordinari_tot   = 0; $straordinari_ore   = 0;

foreach ($compensi as $z) {
    $figc_fer_it      += $z['figc_fer_it']   ?? 0;
    $figc_fest_it     += $z['figc_fest_it']  ?? 0;
    $fer_estero       += $z['fer_estero']    ?? 0;
    $fest_estero      += $z['fest_estero']   ?? 0;
    $figc_tr_aut_tot  += $z['figc_tr_aut']   ?? 0;
    $figc_tr_acmp_tot += $z['figc_tr_acmp']  ?? 0;
    $pres_aut_tot     += $z['pres_aut']      ?? 0;
    $pres_acmp_tot    += $z['pres_acmp']     ?? 0;
    $aut_nofigc_tot   += $z['aut_nofigc']    ?? 0;
    $trasf_breve_tot  += $z['trasf_breve']   ?? 0;
    $trasf_media_tot  += $z['trasf_media']   ?? 0;
    $trasf_lunga_tot  += $z['trasf_lunga']   ?? 0;
    $pernotto_tot       += $z['pernotto']   ?? 0;
    $sielte_tot         += $z['sielte']     ?? 0;
    $pern_sielte_tot    += $z['pern_sielte']    ?? 0;
    $straordinari_tot   += $z['straordinari']   ?? 0;
    $straordinari_ore   += $z['straordinari_ore'] ?? 0;

    array_key_exists('figc_fer_it',  $z) ? $figc_fer_it_num++     : null;
    array_key_exists('figc_fest_it', $z) ? $figc_fest_it_num++    : null;
    array_key_exists('fer_estero',   $z) ? $fer_estero_num++      : null;
    array_key_exists('fest_estero',  $z) ? $fest_estero_num++     : null;
    array_key_exists('figc_tr_aut',  $z) ? $figc_tr_aut_num++     : null;
    array_key_exists('figc_tr_acmp', $z) ? $figc_tr_acmp_num++    : null;
    array_key_exists('pres_aut',     $z) ? $pres_aut_num++        : null;
    array_key_exists('pres_acmp',    $z) ? $pres_acmp_num++       : null;
    array_key_exists('aut_nofigc',   $z) ? $aut_nofigc_num++      : null;
    array_key_exists('trasf_breve',  $z) ? $trasf_breve_num++     : null;
    array_key_exists('trasf_media',  $z) ? $trasf_media_num++     : null;
    array_key_exists('trasf_lunga',  $z) ? $trasf_lunga_num++     : null;
    array_key_exists('pernotto', $z) ? $pernotto_num++ : null;
    array_key_exists('sielte',   $z) ? $sielte_num++  : null;
    array_key_exists('pern_sielte',   $z) ? $pern_sielte_num++    : null;
}

// Apply fissa logic to figc_fer_it total
if ($fissa_eff > 0) {
    $sabati_extra = max(0, $sabati_lavorati - 2);
    $tariffa_sabato_calc = $rate_tariffa_sabato > 0
        ? $rate_tariffa_sabato
        : ($figc_fer_it_num > 0 ? $fissa_eff / $figc_fer_it_num : 0);
    $figc_fer_it = $fissa_eff + ($tariffa_sabato_calc * $sabati_extra);
}

// Per-timesheet giornata override
if ($o_fascia > 0) {
    $figc_fer_it = (float)$o_fascia * $figc_fer_it_num;
}

$totale = $figc_fer_it + $figc_fest_it + $fer_estero + $fest_estero
        + $figc_tr_aut_tot + $figc_tr_acmp_tot
        + $pres_aut_tot + $pres_acmp_tot + $aut_nofigc_tot
        + $trasf_breve_tot + $trasf_media_tot + $trasf_lunga_tot
        + $pernotto_tot + $sielte_tot + $pern_sielte_tot
        + $straordinari_tot;

if ($o_compensation > 0) {
    $totale = (float)$o_compensation;
}

// Bonus/detrazioni always apply (even over override_compensation)
$totale += $totale_bonus;

// Compenso atteso per-day: sum from JSON (all rows)
$sum_compenso_atteso = 0;
foreach ($timesheet as $_row) {
    $_row = json_decode(json_encode($_row), true);
    $sum_compenso_atteso += (float)($_row['CompensoAtteso'] ?? 0);
}
// Fallback to legacy DB field if no per-day values set
if ($sum_compenso_atteso <= 0) {
    $sum_compenso_atteso = (float)($ts->compenso_atteso ?? 0);
}

?>

{{-- ====== HEADER CENTRATO ====== --}}
<div class="flex flex-col items-center text-center mb-8">

    {{-- Titolo --}}
    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 tracking-tight">
        {{ $user_fullname }} &mdash; {{ $month }} {{ $year }}
    </h2>

    {{-- Card totale compenso --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-md px-10 py-6 mb-6 min-w-[260px]">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Totale Compenso</p>
        <p class="text-5xl font-extrabold text-orange-500 dark:text-orange-400 leading-none">{{ $totale }}€</p>
        @if($sum_compenso_atteso > 0)
        @php $diff = round($totale - $sum_compenso_atteso, 2); @endphp
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1">Compenso Atteso dall'utente</p>
            <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ number_format($sum_compenso_atteso, 2, '.', '') }}€</p>
            <p class="text-sm mt-1 font-medium {{ $diff >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                {{ $diff >= 0 ? '+' : '' }}{{ $diff }}€ rispetto all'atteso
            </p>
        </div>
        @endif
    </div>

    {{-- Formula di calcolo --}}
    @if(Auth::user()->role == 'admin' || Auth::user()->role == 'superadmin')
    <div class="w-full max-w-lg text-left">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3 text-center">Formula di Calcolo</p>
        @if($o_compensation > 0)
            <div class="bg-orange-50 dark:bg-orange-950 border border-orange-200 dark:border-orange-700 rounded-xl px-4 py-3 text-sm text-orange-700 dark:text-orange-300">
                ⚠ Compenso impostato manualmente: {{ $o_compensation }}€ — formula automatica ignorata.
                @if($totale_bonus != 0)(Bonus/detrazioni {{ ($totale_bonus >= 0 ? '+' : '') }}{{ $totale_bonus }}€ applicati sopra l'override.)@endif
            </div>
        @else
            @if($_userRoleRate !== null)
            <p class="text-xs italic text-indigo-500 dark:text-indigo-400 mb-2">★ = tariffa individuale per questo utente</p>
            @endif
            <div class="text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-5">
                <dl class="space-y-1">

                    {{-- FIGC Feriale Italia --}}
                    @if($fissa_eff > 0)
                        @php
                            $sabati_extra_n = max(0, $sabati_lavorati - 2);
                            $_sab_tar = $rate_tariffa_sabato > 0
                                ? $rate_tariffa_sabato
                                : ($figc_fer_it_num > 0 ? round($fissa_eff / $figc_fer_it_num, 2) : 0);
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
                            $_tar_gg      = $rate_figc_feriale_italia;
                            $_star_gg     = ($_userRoleRate && $rate_figc_feriale_italia > 0) ? ' ★' : '';
                            $_has_sab_tar = $rate_tariffa_sabato > 0;
                            $sabati_extra_f  = max(0, $sabati_lavorati - 2);
                            $_gg_normali_f   = $figc_fer_it_num - $sabati_extra_f;
                        @endphp
                        @if($o_fascia > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">FIGC Mag. Feriale Italia (fascia override, {{ $figc_fer_it_num }} × {{ $o_fascia }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($o_fascia * $figc_fer_it_num, 2) }}€</dd>
                        </div>
                        @elseif($_has_sab_tar && $sabati_extra_f > 0)
                            @if($_gg_normali_f > 0)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-600 dark:text-gray-400">FIGC Mag. Feriale Italia{{ $_star_gg }} ({{ $_gg_normali_f }} × {{ $_tar_gg }}€):</dt>
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
                        @elseif($figc_fer_it_num > 0 && !$fissa_eff)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">FIGC Mag. Feriale Italia{{ $_star_gg }} ({{ $figc_fer_it_num }} × {{ $_tar_gg }}€):</dt>
                            <dd class="font-semibold text-right">{{ round($figc_fer_it_num * $_tar_gg, 2) }}€</dd>
                        </div>
                        @endif
                    @endif

                    {{-- FIGC Festivo Italia --}}
                    @if($figc_fest_it_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">FIGC Mag. Festivo Italia ({{ $figc_fest_it_num }} × {{ $rate_figc_festivo_italia }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($figc_fest_it, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Feriale Estero --}}
                    @if($fer_estero_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">FIGC Mag. Feriale Estero ({{ $fer_estero_num }} × {{ $rate_feriale_estero }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($fer_estero, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Festivo Estero --}}
                    @if($fest_estero_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">FIGC Mag. Festivo Estero ({{ $fest_estero_num }} × {{ $rate_festivo_estero }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($fest_estero, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Straordinari --}}
                    @if($straordinari_ore > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Straordinari ({{ $straordinari_ore }}h × {{ $rate_straordinari }}€/h):</dt>
                        <dd class="font-semibold text-right">{{ round($straordinari_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- FIGC Trasp. Autista --}}
                    @if($figc_tr_aut_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">FIGC Trasp. Autista ({{ $figc_tr_aut_num }} × {{ $rate_figc_trasp_aut }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($figc_tr_aut_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- FIGC Trasp. Accomp. --}}
                    @if($figc_tr_acmp_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">FIGC Trasp. Accomp. ({{ $figc_tr_acmp_num }} × {{ $rate_figc_trasp_acmp }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($figc_tr_acmp_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Presidio Autisti --}}
                    @if($pres_aut_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Presidio Autisti ({{ $pres_aut_num }} × {{ $rate_presidio_aut }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($pres_aut_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Presidio Accomp. --}}
                    @if($pres_acmp_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Presidio Accomp. ({{ $pres_acmp_num }} × {{ $rate_presidio_acmp }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($pres_acmp_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Autista no FIGC --}}
                    @if($aut_nofigc_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Autista no FIGC ({{ $aut_nofigc_num }} × {{ $rate_autista_nofigc }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($aut_nofigc_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Trasf. Breve --}}
                    @if($trasf_breve_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Trasf. Breve &lt;230km ({{ $trasf_breve_num }} × {{ $rate_trasf_breve }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($trasf_breve_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Trasf. Media --}}
                    @if($trasf_media_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Trasf. Media &lt;300km ({{ $trasf_media_num }} × {{ $rate_trasf_media }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($trasf_media_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Trasf. Lunga --}}
                    @if($trasf_lunga_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Trasf. Lunga &gt;300km ({{ $trasf_lunga_num }} × {{ $rate_trasf_lunga }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($trasf_lunga_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Pernotto --}}
                    @if($pernotto_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Pernotti ({{ $pernotto_num }} × {{ $rate_pernotto }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($pernotto_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- SIELTE --}}
                    @if($sielte_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">SIELTE ({{ $sielte_num }} × {{ $rate_sielte }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($sielte_tot, 2) }}€</dd>
                    </div>
                    @endif

                    {{-- Pernotto SIELTE --}}
                    @if($pern_sielte_num > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-600 dark:text-gray-400">Pernotto SIELTE ({{ $pern_sielte_num }} × {{ $rate_pernotto_sielte }}€):</dt>
                        <dd class="font-semibold text-right">{{ round($pern_sielte_tot, 2) }}€</dd>
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

</div>
{{-- ====== FINE HEADER CENTRATO ====== --}}

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

{{-- ====== CARD FILTRO CLIENTE ====== --}}
<div class="mb-5 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
    <button type="button" id="clientFilterToggle"
        class="w-full flex items-center justify-between px-5 py-3 bg-gray-50 dark:bg-gray-800 text-left hover:bg-gray-100 dark:hover:bg-gray-750 transition-colors focus:outline-none">
        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>
            Filtra per Cliente
        </span>
        <svg id="clientFilterChevron" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div id="clientFilterBody" class="hidden px-5 py-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
        <input type="text" id="clientSearchInput"
            placeholder="Scrivi il nome del cliente per filtrare le righe..."
            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:focus:ring-indigo-500" />
        <div id="clientFilterResult" class="hidden mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300"></div>
    </div>
</div>

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
        <div class="pb-20"></div>

        <button type="submit"
            class="fixed bottom-6 right-6 z-50 inline-flex items-center gap-2 px-5 py-3 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 font-semibold text-sm rounded-full shadow-xl hover:bg-gray-700 dark:hover:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Salva
        </button>
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

        // ====== FILTRO CLIENTE ======
        const compensiData = <?= json_encode($compensi) ?>;

        const filterToggle  = document.getElementById('clientFilterToggle');
        const filterBody    = document.getElementById('clientFilterBody');
        const filterChevron = document.getElementById('clientFilterChevron');
        const searchInput   = document.getElementById('clientSearchInput');
        const filterResult  = document.getElementById('clientFilterResult');

        filterToggle.addEventListener('click', function () {
            const isOpen = !filterBody.classList.contains('hidden');
            filterBody.classList.toggle('hidden', isOpen);
            filterChevron.classList.toggle('rotate-180', !isOpen);
            if (!isOpen) searchInput.focus();
        });

        const COMP_LABELS = {
            figc_fer_it:   'Feriale Italia',
            figc_fest_it:  'Festivo Italia',
            fer_estero:    'Feriale Estero',
            fest_estero:   'Festivo Estero',
            figc_tr_aut:   'FIGC Trasp. Autista',
            figc_tr_acmp:  'FIGC Trasp. Accomp.',
            pres_aut:      'Presidio Autisti',
            pres_acmp:     'Presidio Accomp.',
            aut_nofigc:    'Autista no FIGC',
            trasf_breve:   'Trasf. Breve \u003c230km',
            trasf_media:   'Trasf. Media \u003c300km',
            trasf_lunga:   'Trasf. Lunga \u003e300km',
            pernotto:      'Pernotto',
            sielte:        'SIELTE',
            pern_sielte:   'Pernotto SIELTE',
            straordinari:  'Straordinari',
        };

        function applyClientFilter() {
            const term = searchInput.value.toLowerCase().trim();

            // Show/hide table rows
            document.querySelectorAll('#editableTable tbody tr').forEach(function (row) {
                const c = (row.dataset.cliente || '').toLowerCase();
                row.style.display = (!term || c.includes(term)) ? '' : 'none';
            });

            // Show/hide mobile cards
            document.querySelectorAll('#mobileCardsContainer [data-cliente]').forEach(function (card) {
                const c = (card.dataset.cliente || '').toLowerCase();
                card.style.display = (!term || c.includes(term)) ? '' : 'none';
            });

            if (!term) {
                filterResult.classList.add('hidden');
                return;
            }

            const filtered = compensiData.filter(function (c) {
                return (c.cliente || '').toLowerCase().includes(term);
            });

            if (filtered.length === 0) {
                filterResult.innerHTML = '<p class="italic text-gray-400 dark:text-gray-500">Nessuna giornata trovata per <strong>"' + searchInput.value + '"</strong>.</p>';
                filterResult.classList.remove('hidden');
                return;
            }

            const totals = {};
            Object.keys(COMP_LABELS).forEach(function (k) { totals[k] = 0; });
            filtered.forEach(function (c) {
                Object.keys(COMP_LABELS).forEach(function (k) {
                    if (c[k] !== undefined) totals[k] += parseFloat(c[k]) || 0;
                });
            });

            const grandTotal = Object.keys(COMP_LABELS).reduce(function (s, k) { return s + totals[k]; }, 0);

            let html = '<div class="flex items-center gap-3 mb-3">'
                + '<span class="text-gray-600 dark:text-gray-400">'
                + '<strong class="text-gray-800 dark:text-gray-200">' + filtered.length + '</strong>'
                + ' giornate per <strong class="text-gray-800 dark:text-gray-200">"' + searchInput.value + '"</strong>'
                + '</span>'
                + '<span class="ml-auto text-lg font-extrabold text-orange-500 dark:text-orange-400">' + grandTotal.toFixed(2) + '€</span>'
                + '</div>'
                + '<dl class="space-y-1 border-t border-gray-200 dark:border-gray-600 pt-2">';

            Object.keys(COMP_LABELS).forEach(function (k) {
                if (totals[k] > 0) {
                    html += '<div class="flex justify-between gap-4">'
                        + '<dt class="text-gray-500 dark:text-gray-400">' + COMP_LABELS[k] + '</dt>'
                        + '<dd class="font-semibold">' + totals[k].toFixed(2) + '€</dd>'
                        + '</div>';
                }
            });

            html += '</dl>';

            <?php if ($fissa_eff > 0): ?>
            html += '<p class="mt-3 text-xs text-indigo-500 dark:text-indigo-400 italic">'
                + '&#9432; La paga mensile fissa (<?= $fissa_eff ?>€) è su base mensile e non suddivisibile per cliente.'
                + '</p>';
            <?php endif; ?>

            filterResult.innerHTML = html;
            filterResult.classList.remove('hidden');
        }

        searchInput.addEventListener('input', applyClientFilter);
    });
</script>
