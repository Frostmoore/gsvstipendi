<?php

namespace App\Http\Controllers;

use App\Models\OperatorRoles;
use Illuminate\Http\Request;

class OperatorRolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $operatorRoles = OperatorRoles::all();
        return view('operatorRoles.index', ['operatorRoles' => $operatorRoles]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('operatorRoles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $operatorRoles = OperatorRoles::create($request->all());
        return redirect()->route('operatorRoles.index')->with('success', 'Ruolo Aggiunto con successo!');
    }

    /**
     * Display the specified resource.
     */
    public function show(OperatorRoles $operatorRoles)
    {
        return view('operatorRoles.show', ['operatorRole' => $operatorRoles, 'title' => 'Gestione Ruoli']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OperatorRoles $operatorRoles)
    {
        return view('operatorRoles.edit', compact('operatorRoles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OperatorRoles $operatorRoles)
    {
        $operatorRoles->update($request->all());
        return redirect()->route('operatorRoles.index')->with('success', 'Ruolo Aggiornato con successo!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OperatorRoles $operatorRoles)
    {
        $operatorRole = OperatorRoles::find($operatorRoles->id);
        $operatorRole->delete();
        return redirect()->route('operatorRoles.index')->with('success', 'Ruolo Eliminato con successo!');
    }
}
