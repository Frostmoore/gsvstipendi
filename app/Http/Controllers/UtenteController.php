<?php

namespace App\Http\Controllers;

use App\Models\Utente;
use App\Models\Roles;
use App\Models\Compensation;
use App\Models\UserCompensation;
use App\Models\UserRoleRate;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UtenteController extends Controller
{
    public function passwordReset(Request $request, Utente $user)
    {
        if (!$user->email) {
            return redirect()->route('utenti.index')->withErrors(['email' => 'Utente senza email registrata!']);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->route('utenti.index')->with('success', 'Email di reset password inviata con successo!');
        } else {
            return redirect()->route('utenti.index')->with('error', 'Errore nell\'invio dell\'email!');
        }
    }

    public function index()
    {
        $roles = Roles::all();
        $utenti = Utente::all();
        return view('utenti.index', ['users' => $utenti, 'roles' => $roles]);
    }

    public function create()
    {
        $utenti = Utente::all();
        $roles = Roles::all();
        $compensations = Compensation::leftJoin('roles', 'compensations.role', '=', 'roles.id')
            ->select('compensations.id', 'compensations.name', 'compensations.value', 'compensations.type', 'roles.role as role_name')
            ->get();
        return view('utenti.create', [
            'users' => $utenti,
            'roles' => $roles,
            'compensations' => $compensations,
            'userRoleRates' => collect(),
            'title' => 'Gestione Utenti',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'fissa' => $request->fissa,
        ]);

        if ($request->has('compensation_overrides')) {
            foreach ($request->compensation_overrides as $compId => $value) {
                if ($value !== null && $value !== '') {
                    UserCompensation::create([
                        'user_id' => $user->id,
                        'compensation_id' => $compId,
                        'value' => $value,
                    ]);
                }
            }
        }

        if ($request->has('role_rates')) {
            foreach ($request->role_rates as $roleName => $rates) {
                $giornata = $rates['giornata'] ?? null;
                $fissa = $rates['fissa'] ?? null;
                $tariffa = $rates['tariffa_sabato'] ?? null;
                if (($giornata !== null && $giornata !== '') || ($fissa !== null && $fissa !== '') || ($tariffa !== null && $tariffa !== '')) {
                    UserRoleRate::create([
                        'user_id' => $user->id,
                        'role' => $roleName,
                        'giornata' => ($giornata !== '' ? $giornata : null),
                        'fissa' => ($fissa !== '' ? $fissa : null),
                        'tariffa_sabato' => ($tariffa !== '' ? $tariffa : null),
                    ]);
                }
            }
        }

        return redirect()->route('utenti.index')->with('success', 'Utente Aggiunto con successo!');
    }

    public function show(Utente $utente)
    {
        $roles = Roles::all();
        return view('utenti.show', ['utente' => $utente, 'roles' => $roles, 'title' => 'Gestione Utenti']);
    }

    public function edit(Utente $utente)
    {
        $roles = Roles::all();
        $compensations = Compensation::leftJoin('roles', 'compensations.role', '=', 'roles.id')
            ->select('compensations.id', 'compensations.name', 'compensations.value', 'compensations.type', 'roles.role as role_name')
            ->get();
        $userCompensations = UserCompensation::where('user_id', $utente->id)
            ->pluck('value', 'compensation_id');
        $userRoleRates = UserRoleRate::where('user_id', $utente->id)
            ->get()->keyBy('role');
        $utente = Utente::find($utente->id);
        return view('utenti.edit', [
            'user' => $utente,
            'roles' => $roles,
            'compensations' => $compensations,
            'userCompensations' => $userCompensations,
            'userRoleRates' => $userRoleRates,
            'title' => 'Gestione Utenti',
        ]);
    }

    public function update(Request $request, Utente $utente)
    {
        $utente->update([
            'name' => $request->name,
            'surname' => $request->surname,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'fissa' => $request->fissa,
        ]);

        UserCompensation::where('user_id', $utente->id)->delete();
        if ($request->has('compensation_overrides')) {
            foreach ($request->compensation_overrides as $compId => $value) {
                if ($value !== null && $value !== '') {
                    UserCompensation::create([
                        'user_id' => $utente->id,
                        'compensation_id' => $compId,
                        'value' => $value,
                    ]);
                }
            }
        }

        UserRoleRate::where('user_id', $utente->id)->delete();
        if ($request->has('role_rates')) {
            foreach ($request->role_rates as $roleName => $rates) {
                $giornata = $rates['giornata'] ?? null;
                $fissa = $rates['fissa'] ?? null;
                $tariffa = $rates['tariffa_sabato'] ?? null;
                if (($giornata !== null && $giornata !== '') || ($fissa !== null && $fissa !== '') || ($tariffa !== null && $tariffa !== '')) {
                    UserRoleRate::create([
                        'user_id' => $utente->id,
                        'role' => $roleName,
                        'giornata' => ($giornata !== '' ? $giornata : null),
                        'fissa' => ($fissa !== '' ? $fissa : null),
                        'tariffa_sabato' => ($tariffa !== '' ? $tariffa : null),
                    ]);
                }
            }
        }

        return redirect()->route('utenti.index')->with('success', 'Utente Aggiornato con successo!');
    }

    public function destroy(Utente $utente)
    {
        $utente = Utente::find($utente->id);
        $utente->delete();
        return redirect()->route('utenti.index')->with('success', 'Utente Eliminato con successo!');
    }
}
