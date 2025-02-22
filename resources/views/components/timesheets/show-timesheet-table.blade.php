<?php

use App\Helpers\DateHelper;

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
        $role = $u->role;
    }
}

foreach($roles as $r) {
    if($r->id == $role) {
        $role_id = $r->id;
        $role = $r->name;
    }
}

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

    //-----------------------------------------------------------------------//
    //------------------------ Calcolo Festivi ------------------------------//
    //-----------------------------------------------------------------------//
    $day = explode(' ', $t['Data'])[1];
    $_month = str_pad($_month, 2, '0', STR_PAD_LEFT);
    $day = str_pad($day, 2, '0', STR_PAD_LEFT);
    $true_date_str = $year . '-' . $_month . '-' . $day;
    if(DateHelper::isHoliday($true_date_str)) {
        $festivo = true;
    }
    $rowCompensi['data'] = $true_date_str;

    //-----------------------------------------------------------------------//
    //------------------------ Calcolo Compensi -----------------------------//
    //-----------------------------------------------------------------------//
    $entrata = $t['Entrata'];
    $uscita = $t['Uscita'];
    $trasferta = $t['Trasferta'];
    $pernotto = $t['Pernotto'];
    $presidio = $t['Presidio'];
    $trasferta_lunga = $t['Trasf. Lunga'];
    $estero = $t['Estero'];

    // Creazione oggetti DateTime
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
        if($estero == 1) {
            foreach($compensations as $c) {
                if(!$festivo) {
                    if($c->name == 'Feriale Estero') {
                        $rowCompensi['giornata'] = (float)$c->value;
                    }
                    if($estero > 0) {
                        $rowCompensi['estero'] = (float)$c->value;
                    }
                } else {
                    if($c->name == 'Festivo Estero') {
                        $rowCompensi['giornata'] = (float)$c->value;
                        $rowCompensi['Festivo'] = (float)$c->value;
                    }
                    if($estero > 0) {
                        $rowCompensi['estero'] = (float)$c->value;
                    }
                }
            }
        } else {
            foreach($compensations as $c) {
                if(!$festivo){
                    if($c->name == 'Feriale Italia') {
                        $rowCompensi['giornata'] = (float)$c->value;
                    }
                } else {
                    if($c->name == 'Festivo Italia') {
                        $rowCompensi['giornata'] = (float)$c->value;
                        $rowCompensi['Festivo'] = 0;
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
$esteri = 0;
$giornate = 0;
$festivi = 0;
$straordinari = 0;

$trasferte_num = 0;
$pernotti_num = 0;
$presidi_num = 0;
$trasferte_lunghe_num = 0;
$esteri_num = 0;
$giornate_num = 0;
$festivi_num = 0;
$straordinari_num = 0;

foreach($compensi as $y => $z) {
    $trasferte += $z['trasferta'] ?? 0;
    $pernotti += $z['pernotto'] ?? 0;
    $presidi += $z['presidio'] ?? 0;
    $trasferte_lunghe += $z['trasferta_lunga'] ?? 0;
    $esteri += $z['estero'] ?? 0;
    $giornate += $z['giornata'] ?? 0;
    $festivi += $z['Festivo'] ?? 0;
    $straordinari += $z['straordinari'] ?? 0;

    array_key_exists('trasferta', $z) ? $trasferte_num++ : null;
    array_key_exists('pernotto', $z) ? $pernotti_num++ : null;
    array_key_exists('presidio', $z) ? $presidi_num++ : null;
    array_key_exists('trasferta_lunga', $z) ? $trasferte_lunghe_num++ : null;
    array_key_exists('estero', $z) ? $esteri_num++ : null;
    array_key_exists('giornata', $z) ? $giornate_num++ : null;
    array_key_exists('Festivo', $z) ? $festivi_num++ : null;
    array_key_exists('straordinari', $z) ? $straordinari_num++ : null;
}

$totale = $trasferte + $pernotti + $presidi + $trasferte_lunghe + $esteri + $giornate + $festivi + $straordinari;



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
                    <td class="px-4 py-2">{{ $esteri }} €</td>
                </tr>
            </tbody>
        </table>
    </div>
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
                <tr class="odd:bg-white odd:dark:bg-gray-700 even:bg-gray-50 even:dark:bg-gray-800 even:color-gray-700 dark:text-gray-200">
                    @foreach($row as $key => $value)
                        <td class="px-4 py-2">
                            @switch($key)
                                @case('Trasferta')
                                    {{ $value == 1 ? '✔️' : '' }}
                                    @break
                                @case('Pernotto')
                                    {{ $value == 1 ? '✔️' : '' }}
                                    @break
                                @case('Presidio')
                                    {{ $value == 1 ? '✔️' : '' }}
                                    @break
                                @case('Trasf. Lunga')
                                    {{ $value == 1 ? '✔️' : '' }}
                                    @break
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
</div>