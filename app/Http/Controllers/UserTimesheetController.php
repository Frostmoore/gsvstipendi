<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Models\Utente;
use App\Models\Companies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserTimesheetController extends Controller
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
            ->where('timesheets.user', auth()->user()->id)
            ->select(
                'timesheets.id',
                'timesheets.month',
                'timesheets.year',
                DB::raw("COALESCE(CONCAT(users.surname, ' ', users.name), 'Sconosciuto') as user_fullname"),
                'timesheets.link',
                'timesheets.user as user_id'
            )
            ->get()
            ->map(function ($t) use ($months) {
                $t->month = $months[$t->month] ?? 'Mese sconosciuto';
                return $t;
            });

        $a = Utente::all();
        $b = Timesheet::all();
        return view('user-timesheets.index', ['timesheets' => $b, 'users' => $a, 'timesheets_worked' => $timesheets]);
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

        $userRates = \App\Models\UserRoleRate::where('user_id', auth()->user()->id)->where('role', 'user')->first();

        return view('user-timesheets.create', [
            'users'     => $users,
            'companies' => $companies,
            'months'    => $months,
            'years'     => $years,
            'userRates' => $userRates,
        ]);
    }

    public function store(Request $request)
    {
        Timesheet::create($request->all());
        return redirect()->route('user-timesheets.index')->with('success', 'Foglio Orario Aggiunto con successo!');
    }

    public function show(Timesheet $userTimesheet)
    {
        $months = [
            '1' => 'Gennaio', '2' => 'Febbraio', '3' => 'Marzo',
            '4' => 'Aprile', '5' => 'Maggio', '6' => 'Giugno',
            '7' => 'Luglio', '8' => 'Agosto', '9' => 'Settembre',
            '10' => 'Ottobre', '11' => 'Novembre', '12' => 'Dicembre'
        ];
        $users = Utente::all();
        $companies = Companies::all();
        $timesheet_one = Timesheet::where('id', $userTimesheet->id)->first();

        return view('user-timesheets.show', [
            'timesheet_one' => $timesheet_one,
            'companies'     => $companies,
            'timesheet'     => $userTimesheet,
            'users'         => $users,
            'months'        => $months,
        ]);
    }

    public function edit(Timesheet $userTimesheet)
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

        $userRates = \App\Models\UserRoleRate::where('user_id', auth()->user()->id)->where('role', 'user')->first();

        return view('user-timesheets.edit', [
            'timesheet' => $userTimesheet,
            'users'     => $users,
            'companies' => $companies,
            'months'    => $months,
            'years'     => $years,
            'userRates' => $userRates,
        ]);
    }

    public function update(Request $request, Timesheet $userTimesheet)
    {
        $userTimesheet->update($request->all());
        return redirect()->route('user-timesheets.index')->with('success', 'Foglio Orario aggiornato con successo!');
    }

    public function destroy(Timesheet $userTimesheet)
    {
        if (!in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            abort(403);
        }
        $userTimesheet = Timesheet::find($userTimesheet->id);
        $userTimesheet->delete();
        return redirect()->route('user-timesheets.index')->with('success', 'Foglio Orario Eliminato con successo!');
    }
}
