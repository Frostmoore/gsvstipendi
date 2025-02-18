<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Roles::all();
        return view('roles.index', ['roles' => $roles]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $roles = Roles::create($request->all());
        return redirect()->route('roles.index')->with('success', 'Ruolo Aggiunto con successo!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Roles $roles)
    {
        return view('roles.show', ['role' => $roles, 'title' => 'Gestione Ruoli']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Roles $roles)
    {
        return view('roles.edit', compact('roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Roles $roles)
    {
        $roles->update($request->all());
        return redirect()->route('roles.index')->with('success', 'Ruolo Aggiornato con successo!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Roles $roles)
    {
        $role = Roles::find($roles->id);
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Ruolo Eliminato con successo!');
    }
}
