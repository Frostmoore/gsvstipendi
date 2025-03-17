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

class TimesheetController extends Controller
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
        // @dd($timesheets);
        $a = Utente::all();
        $b = Timesheet::all();
        return view('timesheets.index', ['timesheets' => $b, 'users' => $a, 'timesheets_worked' => $timesheets]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       // Recupera dati standard
        $users = Utente::all();
        $roles = Roles::all();
        $companies = Companies::all();
        $compensations = Compensation::all();

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
        return view('timesheets.create', [
            'users'           => $users,
            'roles'           => $roles,
            'compensations'   => $compensations,
            'companies'       => $companies,
            'months'          => $months,
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
        return redirect()->route('timesheets.index')->with('success', 'Foglio Orario Aggiunto con successo!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Timesheet $timesheet)
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
        $timesheet = Timesheet::find($timesheet->id);
        return view('timesheets.show', ['timesheet' => $timesheet, 'users' => $users, 'roles' => $roles, 'compensations' => $compensations, 'months' => $months]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Timesheet $timesheet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Timesheet $timesheet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Timesheet $timesheet)
    {
        $timesheet = Timesheet::find($timesheet->id);
        $timesheet->delete();
        return redirect()->route('timesheets.index')->with('success', 'Foglio Orario Eliminato con successo!');
    }
}
