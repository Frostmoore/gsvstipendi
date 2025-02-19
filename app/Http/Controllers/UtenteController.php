<?php

namespace App\Http\Controllers;

use App\Models\Utente;
use App\Models\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class UtenteController extends Controller
{
    public function passwordReset(Request $request, Utente $user)
    {
        // Assicurati che l'email esista e sia valida
        if (!$user->email) {
            return redirect()->route('utenti.index')->withErrors(['email' => 'Utente senza email registrata!']);
        }
    
        // Invia l'email di reset
        $status = Password::sendResetLink(['email' => $user->email]);
    
        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->route('utenti.index')->with('success', 'Email di reset password inviata con successo!');
        }
    
        return redirect()->route('utenti.index')->withErrors(['email' => 'Errore nell\'invio dell\'email!']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Roles::all();
        $utenti = Utente::all();
        return view('utenti.index', ['users' => $utenti, 'roles' => $roles]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $utenti = Utente::all();
        $roles = Roles::all();
        return view('utenti.create', ['users' => $utenti, 'roles' => $roles, 'title' => 'Gestione Utenti']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $utenti = Utente::create($request->all());
        return redirect()->route('utenti.index')->with('success', 'Utente Aggiunto con successo!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Utente $utente)
    {
        $roles = Roles::all();
        return view('utenti.show', ['utente' => $utente, 'roles'=>$roles, 'title' => 'Gestione Utenti']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Utente $utente)
    {
        $roles = Roles::all();
        $utente = Utente::find($utente->id);
        return view('utenti.edit', ['user' => $utente, 'roles'=>$roles, 'title' => 'Gestione Utenti']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Utente $utente)
    {
        $utente->update($request->all());
        return redirect()->route('utenti.index')->with('success', 'Utente Aggiornato con successo!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Utente $utente)
    {
        $utente = Utente::find($utente->id);
        $utente->delete();
        return redirect()->route('utenti.index')->with('success', 'Utente Eliminato con successo!');
    }
}
