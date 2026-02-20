<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Models\Roles;
use App\Models\Utente;
use App\Models\Compensation;
use App\Models\Companies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserTimesheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
        // @dd($timesheets);
        $a = Utente::all();
        $b = Timesheet::all();
        return view('user-timesheets.index', ['timesheets' => $b, 'users' => $a, 'timesheets_worked' => $timesheets]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       // Recupera dati standard
        $users = Utente::all();
        $roles = Roles::all();
        $compensations = Compensation::all();
        $companies = Companies::all();

        $months = [
            '1' => 'Gennaio', '2' => 'Febbraio', '3' => 'Marzo',
            '4' => 'Aprile', '5' => 'Maggio', '6' => 'Giugno',
            '7' => 'Luglio', '8' => 'Agosto', '9' => 'Settembre',
            '10' => 'Ottobre', '11' => 'Novembre', '12' => 'Dicembre'
        ];

        for($i = -1; $i < 2; $i++) {
            $years[Carbon::now()->subYears($i)->year] = Carbon::now()->subYears($i)->year;
        }

        // Passa tutti i dati alla view
        return view('user-timesheets.create', [
            'users'           => $users,
            'roles'           => $roles,
            'compensations'   => $compensations,
            'months'          => $months,
            'companies'       => $companies,
            'years'           => $years
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $utenti = Timesheet::create($request->all());
        return redirect()->route('user-timesheets.index')->with('success', 'Foglio Orario Aggiunto con successo!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Timesheet $userTimesheet)
    {
        $months = [
            '1' => 'Gennaio', '2' => 'Febbraio', '3' => 'Marzo',
            '4' => 'Aprile', '5' => 'Maggio', '6' => 'Giugno',
            '7' => 'Luglio', '8' => 'Agosto', '9' => 'Settembre',
            '10' => 'Ottobre', '11' => 'Novembre', '12' => 'Dicembre'
        ];
        $users = Utente::all();
        $roles = Roles::all();
        $compensations = Compensation::all();
        $companies = Companies::all();
        $timesheet_one = Timesheet::where('id', $userTimesheet->id)->first();

        // dd(get_defined_vars());

        return view('user-timesheets.show', ['timesheet_one'=>$timesheet_one, 'companies' => $companies, 'timesheet' => $userTimesheet, 'users' => $users, 'roles' => $roles, 'compensations' => $compensations, 'months' => $months]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Timesheet $userTimesheet)
    {
        $users = Utente::all();
        $roles = Roles::all();
        $compensations = Compensation::all();
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

        return view('user-timesheets.edit', [
            'timesheet'     => $userTimesheet,
            'users'         => $users,
            'roles'         => $roles,
            'compensations' => $compensations,
            'months'        => $months,
            'companies'     => $companies,
            'years'         => $years,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Timesheet $userTimesheet)
    {
        $userTimesheet->update($request->all());
        return redirect()->route('user-timesheets.index')->with('success', 'Foglio Orario aggiornato con successo!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Timesheet $userTimesheet)
    {
        $userTimesheet = Timesheet::find($userTimesheet->id);
        $userTimesheet->delete();
        return redirect()->route('user-timesheets.index')->with('success', 'Foglio Orario Eliminato con successo!');
    }
}
