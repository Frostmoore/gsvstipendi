<?php

namespace App\Http\Controllers;

use App\Models\Compensation;
use App\Models\Roles;
use Illuminate\Http\Request;

class CompensationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Roles::all();
        $compensations = Compensation::leftJoin('roles', 'compensations.role', '=', 'roles.id')
            ->select(
                'compensations.id',
                'compensations.name',
                'roles.id as role_id',
                'roles.role as role_name',
                'compensations.value'
            )
            ->get()->sortByDesc('role_id');

        return view('compensations.index', [
            'compensations' => $compensations,
            'roles' => $roles
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Roles::all();
        return view('compensations.create', ['roles' => $roles]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $compensation = Compensation::create($request->all());
        return redirect()->route('compensations.index')->with('success', 'Compenso aggiunto con successo!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Compensation $compensation)
    {
        return view('compensations.show', ['compensation' => $compensation]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Compensation $compensation)
    {
        $compensations = Compensation::all();
        $roles = Roles::all();
        $compensationsRoles = Compensation::leftJoin('roles', 'compensations.role', '=', 'roles.id')
            ->select(
                'compensations.id',
                'compensations.name',
                'roles.id as role_id',
                'roles.role as role_name',
                'compensations.value'
            )
        ->get();

        return view('compensations.edit', [
            'compensation' => $compensation,
            'compensations' => $compensations,
            'compensationsWithRoles' => $compensationsRoles,
            'roles' => $roles
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Compensation $compensation)
    {
        $compensations = Compensation::all();
        $roles = Roles::all();
        $compensationsRoles = Compensation::leftJoin('roles', 'compensations.role', '=', 'roles.id')
            ->select(
                'compensations.id',
                'compensations.name',
                'roles.id as role_id',
                'roles.role as role_name',
                'compensations.value'
            )
        ->get();
        $compensation->update($request->all());
        return redirect()->route('compensations.index')->with('success', 'Compenso aggiornato con successo!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Compensation $compensation)
    {
        // $compensation = Compensation::find($compensation->id);
        // dd($compensation);
        $compensation->delete();
        return redirect()->route('compensations.index')->with('success', 'Compenso eliminato con successo!');
    }
}
