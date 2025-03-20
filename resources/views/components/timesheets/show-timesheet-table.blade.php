<?php

use App\Helpers\DateHelper;

$id = $timesheet->id;
$ts = $timesheet;
$_month = $timesheet->month;
$month = $months[$_month];
$year = $timesheet->year;
$userid = $timesheet->user;
$ruolo = $timesheet->role;
$timesheet = json_decode($timesheet->link);
$compensi = [];

foreach($users as $u) {
    if($u->id == $userid) {
        $user_fullname = $u->surname . ' ' . $u->name;
        $role = $u->role;
    }
}

// var_dump($roles);

foreach($roles as $r) {
    if($r->id == $role) {
        $role_id = $r->id;
        $role = $r->role;
    }
}

$role = $ruolo;

switch($role) {
    case 'Magazziniere FIGC':
        $cols = [
            'Data',
            'Cliente',
            'Luogo',
            'Estero'
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
            'Presidio'
        ];
        break;
    case 'Facchino':
        $cols = [
            'Data',
            'Cliente',
            'Luogo',
            'Entrata',
            'Uscita',
            'Trasferta'
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
            'Estero'
        ];
        break;
}

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
        if (empty($entrata) || empty($uscita)) {
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

    $trasferta = array_key_exists('Trasferta', $t) ? $t['Trasferta'] : null;
    $pernotto = array_key_exists('Pernotto', $t) ? $t['Pernotto'] : null;
    $presidio = array_key_exists('Presidio', $t) ? $t['Presidio'] : null;
    $trasferta_lunga = array_key_exists('TrasfLunga', $t) ? $t['TrasfLunga'] : null;
    $trasferta_breve = array_key_exists('TrasfBreve', $t) ? $t['TrasfBreve'] : null;
    $estero = array_key_exists('Estero', $t) ? $t['Estero'] : null;


    // Creazione oggetti DateTime
    if($entrata != '' && $uscita != '') {
        $t1 = DateTime::createFromFormat('H:i', $entrata);
        $t2 = DateTime::createFromFormat('H:i', $uscita);

        if ($t1 && $t2) { // Controllo validità degli orari
            $difference = $t1->diff($t2);
            $totalHours = $difference->h + ($difference->i / 60); // Differenza in ore decimali

            if ($totalHours > 9) {
                $extraHours = floor($totalHours - 9); // Numero di ore extra oltre le 9
                foreach($compensations as $c) {
                    if($c->name == 'Straordinari') {
                        $x = $c->value;
                    }
                }
                $rowCompensi['straordinari'] = $extraHours * (float)$x; // Calcolo compenso extra //CONTROLLARE!!!!!!!!
            } else {
                $rowCompensi['straordinari'] = 0; // Nessun extra se non supera le 9 ore
            }
        } else {
            $rowCompensi['straordinari'] = 0; // In caso di errore nei dati
        }
    }

    if($role == 'Facchino') {

        foreach($compensations as $c) {
            if(!$festivo) {
                if($c->name == 'Giornata Lavorativa') {
                    $rowCompensi['giornata'] = (float)$c->value;
                }
                if($estero > 0) {
                    $rowCompensi['estero'] = 1;
                }
            } else {
                if($c->name == 'Festivo') {
                    $rowCompensi['Festivo'] = (float)$c->value;
                }
                if($c->name == 'Giornata Lavorativa') {
                    $rowCompensi['giornata'] = (float)$c->value;
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
                    $rowCompensi['giornata'] = (float)$c->value;
                }
                if($estero > 0) {
                    $rowCompensi['estero'] = 1;
                }
            } else {
                if($c->name == 'Festivo') {
                    $rowCompensi['Festivo'] = (float)$c->value;
                }
                if($c->name == 'Giornata Lavorativa') {
                    $rowCompensi['giornata'] = (float)$c->value;
                }
                if($estero > 0) {
                    $rowCompensi['estero'] = 1;
                }
            }
        }

    } else if($role == 'Magazziniere FIGC') {
        //----------------------------------------------------------------------------------------------------------//
        //                                                                                                          //
        // DA CONTROLLARE IL CALCOLO PER I MAGAZZINIERI FIGC, IN PARTICOLARE PER I FESTIVI E LE GIORNATE ALL'ESTERO //
        //                                                                                                          //
        //----------------------------------------------------------------------------------------------------------//
        $baseGiornata = 0;
        foreach($compensations as $d) {
            if($d->name =='Feriale Italia') {
                $baseGiornata = $d->value;
            }
        }

        if($estero == 1) {
            if(!$festivo) {
                foreach($compensations as $c) {
                    if($c->name == 'Feriale Estero') {
                        $rowCompensi['giornata'] = $baseGiornata;
                        $rowCompensi['estero'] = $c->value - $baseGiornata;
                    }
                }
            } else {
                foreach($compensations as $c) {
                    if($c->name == 'Festivo Estero') {
                        $rowCompensi['giornata'] = $baseGiornata;
                        $rowCompensi['Festivo'] = ((float)$c->value - $baseGiornata)/2;
                        $rowCompensi['estero'] = ((float)$c->value - $baseGiornata)/2;
                    }
                }
            }
        } else {
            if(!$festivo) {
                foreach($compensations as $c) {
                    if($c->name == 'Feriale Italia') {
                        $rowCompensi['giornata'] = $baseGiornata;
                    }
                }
            } else {
                foreach($compensations as $c) {
                    if($c->name == 'Festivo Italia') {
                        $rowCompensi['giornata'] = $baseGiornata;
                        $rowCompensi['Festivo'] = (float)$c->value - $baseGiornata;
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
                if($c->name == 'Festivo') {
                    $rowCompensi['Festivo'] = (float)$c->value;
                }
                if($c->name == 'Giornata Lavorativa') {
                    $rowCompensi['giornata'] = (float)$c->value;
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

//var_dump($compensi);

//-----------------------------------------------------------------------//
//------------------------ Calcolo Totali -------------------------------//
//-----------------------------------------------------------------------//
$trasferte = 0;
$pernotti = 0;
$presidi = 0;
$trasferte_lunghe = 0;
$trasferte_brevi = 0;
$esteri = 0;
$giornate = 0;
$festivi = 0;
$straordinari = 0;

$trasferte_num = 0;
$pernotti_num = 0;
$presidi_num = 0;
$trasferte_lunghe_num = 0;
$trasferte_brevi_num = 0;
$esteri_num = 0;
$giornate_num = 0;
$festivi_num = 0;
$straordinari_num = 0;

foreach($compensi as $y => $z) {
    $trasferte += $z['trasferta'] ?? 0;
    $pernotti += $z['pernotto'] ?? 0;
    $presidi += $z['presidio'] ?? 0;
    $trasferte_lunghe += $z['trasferta_lunga'] ?? 0;
    $trasferte_brevi += $z['trasferta_breve'] ?? 0;
    $esteri += $z['estero'] ?? 0;
    $giornate += $z['giornata'] ?? 0;
    $festivi += $z['Festivo'] ?? 0;
    $straordinari += $z['straordinari'] ?? 0;

    array_key_exists('trasferta', $z) ? $trasferte_num++ : null;
    array_key_exists('pernotto', $z) ? $pernotti_num++ : null;
    array_key_exists('presidio', $z) ? $presidi_num++ : null;
    array_key_exists('trasferta_lunga', $z) ? $trasferte_lunghe_num++ : null;
    array_key_exists('trasferta_breve', $z) ? $trasferte_brevi_num++ : null;
    array_key_exists('estero', $z) ? $esteri_num++ : null;
    array_key_exists('giornata', $z) ? $giornate_num++ : null;
    array_key_exists('Festivo', $z) ? $festivi_num++ : null;
    if(array_key_exists('straordinari', $z)) {
        if($z['straordinari'] > 0) {
            $straordinari_num++;
        }
    } else {
        $straordinari_num == null;
    }
}

$totale = $trasferte + $pernotti + $presidi + $trasferte_lunghe + $trasferte_brevi + $esteri + $giornate + $festivi + $straordinari;



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
                    <th class="px-4 py-2">Festivi</th>
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
                    <td class="px-4 py-2">{{ $festivi_num }}</td>
                    <td class="px-4 py-2">{{ $straordinari_num }}</td>
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
                    <td class="px-4 py-2">{{ $festivi }} €</td>
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
    <h2 class="text-lg text-gray-800 dark:text-gray-200 leading-tight mb-4">
        <strong>Foglio Orario Complessivo:</strong>
    </h2>
    <form class="gsv-form" method="POST" action="{{ route('timesheets.update', $ts) }}">
        @csrf
        @method('PATCH')
        <x-timesheets.show-edit-timesheet-table :timesheet="$ts" :months="$months" :cols="$cols" />
        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Salva') }}
            </x-primary-button>
        </div>
    </form>
</div>