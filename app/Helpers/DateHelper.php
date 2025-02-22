<?php
namespace App\Helpers;

use Carbon\Carbon;

class DateHelper {
    public static function getItalianHolidays(int $year): array
    {
        // Date fisse
        $holidays = [
            "{$year}-01-01" => "Capodanno",
            "{$year}-01-06" => "Epifania",
            "{$year}-04-25" => "Festa della Liberazione",
            "{$year}-05-01" => "Festa dei Lavoratori",
            "{$year}-06-02" => "Festa della Repubblica",
            "{$year}-08-15" => "Ferragosto",
            "{$year}-11-01" => "Ognissanti",
            "{$year}-12-08" => "Immacolata Concezione",
            "{$year}-12-25" => "Natale",
            "{$year}-12-26" => "Santo Stefano",
        ];

        // Aggiungere Pasqua e Lunedì dell'Angelo
        $easterSunday = self::getEasterDate($year);
        $easterMonday = $easterSunday->copy()->addDay(); // Lunedì dell'Angelo

        $holidays[$easterSunday->toDateString()] = "Pasqua";
        $holidays[$easterMonday->toDateString()] = "Lunedì dell'Angelo";

        // Aggiungere tutte le domeniche dell'anno
        $currentDate = Carbon::create($year, 1, 1);
        while ($currentDate->year == $year) {
            if ($currentDate->isSunday()) {
                $holidays[$currentDate->toDateString()] = "Domenica";
            }
            $currentDate->addDay();
        }

        return $holidays;
    }

    // Funzione per calcolare la data di Pasqua (algoritmo di Gauss)
    public static function getEasterDate(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = ($h + $l - 7 * $m + 114) % 31 + 1;

        return Carbon::create($year, $month, $day);
    }

    
    // Funzione per verificare se un giorno è festivo
    public static function isHoliday(string $date): bool
    {
        $year = Carbon::parse($date)->year;
        $holidays = self::getItalianHolidays($year);

        return array_key_exists($date, $holidays);
    }


}