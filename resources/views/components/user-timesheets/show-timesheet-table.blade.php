<?php

use App\Helpers\DateHelper;
$ts = $userTimesheet;
$id = $ts->id;
$_month = $ts->month;
$month = $months[$_month];
$year = $ts->year;
$userid = $ts->user;
$bonus_list   = is_array($ts->bonuses) ? $ts->bonuses : (json_decode($ts->bonuses ?? '[]', true) ?? []);
$totale_bonus = array_sum(array_column($bonus_list, 'amount'));
$timesheet = json_decode($ts->link);
$compensi = [];

foreach($users as $u) {
    if($u->id == $userid) {
        $user_fullname = $u->surname . ' ' . $u->name;
        $u_fissa = (float)$u->fissa > 0 ? (float)$u->fissa : 0;
    }
}

// Load per-user rates
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

$fissa_eff = 0;
if ($_userRoleRate && (float)($_userRoleRate->fissa ?? 0) > 0) {
    $fissa_eff = (float)$_userRoleRate->fissa;
} elseif (isset($u_fissa) && $u_fissa > 0) {
    $fissa_eff = $u_fissa;
}

// Dynamic column set — only include columns for rates that are configured
$cols        = ['Data', 'Cliente', 'Luogo', 'Entrata', 'Uscita'];
$allowedKeys = ['Data', 'Cliente', 'Luogo', 'Entrata', 'Uscita'];
$flagColKeys = [];
$flagColKeys['Feriale Italia'] = 'FerItalia';
if ($rate_figc_festivo_italia > 0) $flagColKeys['Festivo Italia'] = 'FestItalia';
if ($rate_feriale_estero > 0)      $flagColKeys['Feriale Estero'] = 'FerEstero';
if ($rate_festivo_estero > 0)      $flagColKeys['Festivo Estero'] = 'FestEstero';
if ($rate_figc_trasp_aut > 0)   $flagColKeys['FIGC Trasp. Autista'] = 'FigcTraspAut';
if ($rate_figc_trasp_acmp > 0)  $flagColKeys['FIGC Trasp. Accomp.']  = 'FigcTraspAccomp';
if ($rate_presidio_aut > 0)     $flagColKeys['Presidio Autisti']     = 'PresidioAut';
if ($rate_presidio_acmp > 0)    $flagColKeys['Presidio Accomp.']     = 'PresidioAccomp';
if ($rate_autista_nofigc > 0)   $flagColKeys['Autista no FIGC']      = 'AutistaNoFigc';
if ($rate_trasf_breve > 0)      $flagColKeys['Trasf. Breve <230km']  = 'TrasfBreve';
if ($rate_trasf_media > 0)      $flagColKeys['Trasf. Media <300km']  = 'TrasfMedia';
if ($rate_trasf_lunga > 0)      $flagColKeys['Trasf. Lunga >300km']  = 'TrasfLunga';
if ($rate_pernotto > 0)        $flagColKeys['Pernotto']        = 'Pernotto';
if ($rate_sielte > 0)          $flagColKeys['SIELTE']          = 'Sielte';
if ($rate_pernotto_sielte > 0)   $flagColKeys['Pernotto SIELTE']     = 'PernSielte';
if (!empty($flagColKeys)) { $cols[] = 'Opzioni'; $allowedKeys[] = '__flags__'; }
$cols[] = 'Comp. Atteso (€)'; $allowedKeys[] = 'CompensoAtteso';
$cols[] = 'Note'; $allowedKeys[] = 'Note';

$sabati_lavorati = 0;

foreach ($timesheet as $t) {
    $t = json_decode(json_encode($t), true);
    $rowCompensi = [];

    $day = explode(' ', $t['Data'])[1];
    $_month_str = str_pad($_month, 2, '0', STR_PAD_LEFT);
    $day = str_pad($day, 2, '0', STR_PAD_LEFT);
    $true_date_str = $year . '-' . $_month_str . '-' . $day;
    $rowCompensi['data'] = $true_date_str;

    $entrata = array_key_exists('Entrata', $t) ? $t['Entrata'] : null;
    $uscita  = array_key_exists('Uscita', $t)  ? $t['Uscita']  : null;

    if (empty($entrata) || empty($uscita) || $entrata == '00:00' || $uscita == '00:00') {
        continue;
    }

    $is_sabato = strpos($t['Data'], 'Sabato') !== false;
    if ($is_sabato) $sabati_lavorati++;

    $fer_italia     = array_key_exists('FerItalia',      $t) ? $t['FerItalia']      : null;
    $fest_italia    = array_key_exists('FestItalia',     $t) ? $t['FestItalia']     : null;
    $fer_estero     = array_key_exists('FerEstero',      $t) ? $t['FerEstero']      : null;
    $fest_estero    = array_key_exists('FestEstero',     $t) ? $t['FestEstero']     : null;
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
        $fer_estero == 1 || $fest_estero == 1 ||
        $figc_tr_aut == 1 || $figc_tr_acmp == 1 ||
        $pres_aut == 1 || $pres_acmp == 1 || $aut_nofigc == 1 ||
        $trasf_breve == 1 || $trasf_media == 1 || $trasf_lunga == 1 || $pernotto == 1 ||
        $sielte == 1 || $pern_sielte == 1
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

    // Giornata base: diretta da checkbox (nessun calcolo automatico)
    if ($fer_italia == 1) {
        if ($fissa_eff > 0) {
            $rowCompensi['figc_fer_it'] = ($is_sabato && $sabati_lavorati <= 2)
                ? $fissa_eff
                : (($is_sabato && $sabati_lavorati > 2 && $rate_tariffa_sabato > 0) ? $rate_tariffa_sabato : $rate_figc_feriale_italia);
        } else {
            $rowCompensi['figc_fer_it'] = ($is_sabato && $sabati_lavorati > 2 && $rate_tariffa_sabato > 0)
                ? $rate_tariffa_sabato : $rate_figc_feriale_italia;
        }
    }
    if ($fest_italia == 1 && $rate_figc_festivo_italia > 0) {
        $rowCompensi['figc_fest_it'] = $rate_figc_festivo_italia;
    }
    if ($fer_estero == 1 && $rate_feriale_estero > 0) {
        $rowCompensi['fer_estero'] = $rate_feriale_estero;
    }
    if ($fest_estero == 1 && $rate_festivo_estero > 0) {
        $rowCompensi['fest_estero'] = $rate_festivo_estero;
    }

    if ($figc_tr_aut == 1)   $rowCompensi['figc_tr_aut']  = $rate_figc_trasp_aut;
    if ($figc_tr_acmp == 1)  $rowCompensi['figc_tr_acmp'] = $rate_figc_trasp_acmp;
    if ($pres_aut == 1)      $rowCompensi['pres_aut']     = $rate_presidio_aut;
    if ($pres_acmp == 1)     $rowCompensi['pres_acmp']    = $rate_presidio_acmp;
    if ($aut_nofigc == 1)    $rowCompensi['aut_nofigc']   = $rate_autista_nofigc;
    if ($trasf_breve == 1)   $rowCompensi['trasf_breve']  = $rate_trasf_breve;
    if ($trasf_media == 1)   $rowCompensi['trasf_media']  = $rate_trasf_media;
    if ($trasf_lunga == 1)   $rowCompensi['trasf_lunga']  = $rate_trasf_lunga;
    if ($pernotto == 1)  $rowCompensi['pernotto'] = $rate_pernotto;
    if ($sielte == 1)    $rowCompensi['sielte']   = $rate_sielte;
    if ($pern_sielte == 1)   $rowCompensi['pern_sielte']      = $rate_pernotto_sielte;

    array_push($compensi, $rowCompensi);
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
$pernotto_tot = 0; $pernotto_num = 0;
$sielte_tot   = 0; $sielte_num   = 0;
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
    $pernotto_tot += $z['pernotto'] ?? 0;
    $sielte_tot   += $z['sielte']   ?? 0;
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

$totale = $figc_fer_it + $figc_fest_it + $fer_estero + $fest_estero
        + $figc_tr_aut_tot + $figc_tr_acmp_tot
        + $pres_aut_tot + $pres_acmp_tot + $aut_nofigc_tot
        + $trasf_breve_tot + $trasf_media_tot + $trasf_lunga_tot
        + $pernotto_tot + $sielte_tot + $pern_sielte_tot
        + $straordinari_tot + $totale_bonus;

// Compenso atteso per-day: sum from JSON
$sum_compenso_atteso = 0;
foreach ($timesheet as $_row) {
    $_row = json_decode(json_encode($_row), true);
    $sum_compenso_atteso += (float)($_row['CompensoAtteso'] ?? 0);
}
if ($sum_compenso_atteso <= 0) {
    $sum_compenso_atteso = (float)($ts->compenso_atteso ?? 0);
}

?>

<div class="title-container mb-4">
    <h2 class="text-lg text-gray-800 dark:text-gray-200 leading-tight">
        {{ $user_fullname }} - {{ $month }} {{ $year }}
    </h2>
</div>

{{-- Mobile cards (< md) --}}
<div class="md:hidden space-y-3 mb-4">
    <h2 class="text-lg text-gray-800 dark:text-gray-200 leading-tight mb-3"><strong>Foglio Orario Complessivo:</strong></h2>
    @foreach($timesheet as $row)
    @php
        $_r        = is_object($row) ? (array)$row : $row;
        $_data     = $_r['Data']            ?? '';
        $_cliente  = $_r['Cliente']         ?? '';
        $_luogo    = $_r['Luogo']           ?? '';
        $_entrata  = $_r['Entrata']         ?? '';
        $_uscita   = $_r['Uscita']          ?? '';
        $_note     = $_r['Note']            ?? '';
        $_ca       = $_r['CompensoAtteso']  ?? '';
        $_hasTime  = !empty($_entrata) && !empty($_uscita) && $_entrata !== '00:00' && $_uscita !== '00:00';
        $_mFlags   = [];
        foreach ($flagColKeys as $lbl => $fk) {
            $fv = $_r[$fk] ?? '';
            if ($fv == '1' || $fv === true) $_mFlags[] = $lbl;
        }
    @endphp
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
        <div class="font-semibold text-sm text-gray-800 dark:text-gray-200 mb-2 pb-1 border-b border-gray-200 dark:border-gray-700">
            {{ $_data }}
        </div>
        @if($_cliente || $_luogo)
        <div class="space-y-0.5 mb-2">
            @if($_cliente)
            <div class="flex justify-between text-sm gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Cliente</span>
                <span class="text-gray-800 dark:text-gray-200 text-right text-xs">{{ $_cliente }}</span>
            </div>
            @endif
            @if($_luogo)
            <div class="flex justify-between text-sm gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">Luogo</span>
                <span class="text-gray-800 dark:text-gray-200 text-right text-xs">{{ $_luogo }}</span>
            </div>
            @endif
        </div>
        @endif
        @if($_hasTime)
        <div class="flex gap-4 mb-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">Entrata: <strong class="text-gray-800 dark:text-gray-200">{{ $_entrata }}</strong></span>
            <span class="text-xs text-gray-500 dark:text-gray-400">Uscita: <strong class="text-gray-800 dark:text-gray-200">{{ $_uscita }}</strong></span>
        </div>
        @endif
        @if(count($_mFlags) > 0)
        <div class="flex flex-wrap gap-1 mb-2">
            @foreach($_mFlags as $af)
            <span class="text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 rounded px-1.5 py-0.5">{{ $af }}</span>
            @endforeach
        </div>
        @endif
        @if($_ca)
        <div class="flex justify-between text-xs pt-1.5 border-t border-gray-200 dark:border-gray-700">
            <span class="text-gray-500 dark:text-gray-400">Comp. Atteso</span>
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format((float)$_ca, 2, '.', '') }}€</span>
        </div>
        @endif
        @if($_note)
        <div class="text-xs mt-1.5 text-gray-500 dark:text-gray-400 {{ $_ca ? 'pt-1' : 'pt-1.5 border-t border-gray-200 dark:border-gray-700' }}">
            <span class="font-medium">Note:</span> {{ $_note }}
        </div>
        @endif
    </div>
    @endforeach
</div>

{{-- Desktop table (>= md) --}}
<div class="w-full overflow-x-auto hidden md:block">
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
                        @if($key === '__flags__')
                        <td class="px-4 py-2">
                            @php
                                $activeFlags = [];
                                foreach ($flagColKeys as $lbl => $fk) {
                                    $val = is_object($row) ? ($row->$fk ?? '') : ($row[$fk] ?? '');
                                    if ($val == '1' || $val === true) $activeFlags[] = $lbl;
                                }
                            @endphp
                            @if(count($activeFlags) > 0)
                                <div class="flex flex-col gap-0.5">
                                @foreach($activeFlags as $af)
                                    <span class="inline-block text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 rounded px-1.5 py-0.5">{{ $af }}</span>
                                @endforeach
                                </div>
                            @else
                                <span class="text-gray-300 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        @else
                        @php $value = is_object($row) ? ($row->$key ?? '') : ($row[$key] ?? ''); @endphp
                        <td class="px-4 py-2">
                            {{ $value }}
                        </td>
                        @endif
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
    @if($sum_compenso_atteso > 0)
    @php $diff_u = round($totale - $sum_compenso_atteso, 2); @endphp
    <p class="text-sm mt-1 text-gray-600 dark:text-gray-400">
        Compenso atteso: <strong>{{ number_format($sum_compenso_atteso, 2, '.', '') }}€</strong>
        <span class="font-medium {{ $diff_u >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
            ({{ $diff_u >= 0 ? '+' : '' }}{{ $diff_u }}€)
        </span>
    </p>
    @endif


    <form class="gsv-bonus-form mt-6" method="POST" action="{{ route('user-timesheets.update-bonuses', $ts) }}">
        @csrf
        @method('PATCH')

        <fieldset class="w-full overflow-hidden border border-gray-300 dark:border-gray-600 rounded-lg p-4 mb-6">
            <legend class="px-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Aggiunte e Detrazioni</legend>

            <div class="flex flex-col md:flex-row gap-3 md:items-end mb-4">
                <div class="w-full md:w-40">
                    <x-input-label for="user_bonus_amount" :value="__('Importo (€)')" />
                    <x-text-input id="user_bonus_amount" class="block mt-1 w-full" type="number" step="0.01" placeholder="es. 50 o -30" />
                </div>
                <div class="w-full md:flex-1">
                    <x-input-label for="user_bonus_note" :value="__('Motivazione')" />
                    <x-text-input id="user_bonus_note" class="block mt-1 w-full" type="text" placeholder="es. Rimborso spese..." />
                </div>
                <div class="w-full md:w-auto md:flex-shrink-0 md:pb-0.5">
                    <button type="button" id="user_add_bonus_btn"
                        class="w-full md:w-auto inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none transition ease-in-out duration-150">
                        + Aggiungi
                    </button>
                </div>
            </div>

            <div id="user_bonus_list_container"></div>
            <input type="hidden" name="bonuses" id="user_bonuses_hidden" value="{{ old('bonuses', json_encode($bonus_list)) }}" />
        </fieldset>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bonusContainer = document.getElementById('user_bonus_list_container');
        let bonusEntries = JSON.parse(document.getElementById('user_bonuses_hidden').value || '[]');

        function renderBonusList() {
            document.getElementById('user_bonuses_hidden').value = JSON.stringify(bonusEntries);

            if (bonusEntries.length === 0) {
                bonusContainer.innerHTML =
                    '<p class="text-sm text-gray-400 dark:text-gray-500 italic">Nessuna aggiunta o detrazione.</p>';
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
                    html += `<button type="button" onclick="userRemoveBonus(${index})" class="text-xs hover:underline flex-shrink-0">Elimina</button>`;
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
                    html += `<button type="button" onclick="userRemoveBonus(${index})" class="text-xs text-red-600 dark:text-red-400 hover:underline">Elimina</button>`;
                    html += `</td></tr>`;
                });
                html += '</tbody></table>';
                bonusContainer.innerHTML = html;
            }
        }

        window.userRemoveBonus = function(index) {
            bonusEntries.splice(index, 1);
            renderBonusList();
            document.querySelector('form.gsv-bonus-form').submit();
        };

        document.getElementById('user_add_bonus_btn').addEventListener('click', function() {
            const amountInput = document.getElementById('user_bonus_amount');
            const noteInput   = document.getElementById('user_bonus_note');
            const amount      = parseFloat(amountInput.value);
            const note        = noteInput.value.trim();

            if (isNaN(amount) || amount === 0) { amountInput.focus(); return; }
            if (!note) { noteInput.focus(); return; }

            bonusEntries.push({ amount: amount, note: note });
            renderBonusList();
            document.querySelector('form.gsv-bonus-form').submit();
        });

        renderBonusList();
    });
</script>
