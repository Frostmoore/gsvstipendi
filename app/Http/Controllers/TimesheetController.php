<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Models\Utente;
use App\Models\Companies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimesheetController extends Controller
{
    public function index()
    {
        $months = [
            '1' => 'Gennaio', '2' => 'Febbraio', '3' => 'Marzo',
            '4' => 'Aprile', '5' => 'Maggio', '6' => 'Giugno',
            '7' => 'Luglio', '8' => 'Agosto', '9' => 'Settembre',
            '10' => 'Ottobre', '11' => 'Novembre', '12' => 'Dicembre'
        ];

        $timesheets = DB::table('timesheets')
            ->leftJoin('users', DB::raw('CAST(timesheets.user AS UNSIGNED)'), '=', 'users.id')
            ->select(
                'timesheets.id',
                'timesheets.month',
                'timesheets.year',
                DB::raw("COALESCE(CONCAT(users.surname, ' ', users.name), 'Sconosciuto') as user_fullname"),
                'timesheets.link',
                'timesheets.role',
                'timesheets.user as user_id'
            )
            ->get()
            ->map(function ($t) use ($months) {
                $t->month = $months[$t->month] ?? 'Mese sconosciuto';
                return $t;
            });

        $a = Utente::all();
        $b = Timesheet::all();
        return view('timesheets.index', ['timesheets' => $b, 'users' => $a, 'timesheets_worked' => $timesheets]);
    }

    public function create()
    {
        $users = Utente::all();
        $companies = Companies::all();

        $months = [
            '1' => 'Gennaio', '2' => 'Febbraio', '3' => 'Marzo',
            '4' => 'Aprile', '5' => 'Maggio', '6' => 'Giugno',
            '7' => 'Luglio', '8' => 'Agosto', '9' => 'Settembre',
            '10' => 'Ottobre', '11' => 'Novembre', '12' => 'Dicembre'
        ];

        for ($i = -1; $i < 2; $i++) {
            $years[Carbon::now()->subYears($i)->year] = Carbon::now()->subYears($i)->year;
        }

        $usersRates = \App\Models\UserRoleRate::where('role', 'user')->get()->keyBy('user_id');

        return view('timesheets.create', [
            'users'      => $users,
            'companies'  => $companies,
            'months'     => $months,
            'years'      => $years,
            'usersRates' => $usersRates,
        ]);
    }

    public function store(Request $request)
    {
        Timesheet::create($request->all());
        return redirect()->route('timesheets.index')->with('success', 'Foglio Orario Aggiunto con successo!');
    }

    public function show(Timesheet $timesheet)
    {
        $months = [
            '1' => 'Gennaio', '2' => 'Febbraio', '3' => 'Marzo',
            '4' => 'Aprile', '5' => 'Maggio', '6' => 'Giugno',
            '7' => 'Luglio', '8' => 'Agosto', '9' => 'Settembre',
            '10' => 'Ottobre', '11' => 'Novembre', '12' => 'Dicembre'
        ];
        $users = Utente::all();
        $companies = Companies::all();
        $timesheet = Timesheet::find($timesheet->id);
        return view('timesheets.show', [
            'timesheet' => $timesheet,
            'companies' => $companies,
            'users'     => $users,
            'months'    => $months,
        ]);
    }

    public function edit(Timesheet $timesheet)
    {
        //
    }

    public function update(Request $request, Timesheet $timesheet)
    {
        $timesheet->update($request->all());
        return redirect()->route('timesheets.show', ['timesheet' => $timesheet])->with('success', 'Foglio Orario Aggiornato con successo!');
    }

    public function destroy(Timesheet $timesheet)
    {
        if (!in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            abort(403);
        }
        $timesheet = Timesheet::find($timesheet->id);
        $timesheet->delete();
        return redirect()->route('timesheets.index')->with('success', 'Foglio Orario Eliminato con successo!');
    }
}
