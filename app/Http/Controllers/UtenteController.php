<?php

namespace App\Http\Controllers;

use App\Models\Utente;
use App\Models\UserRoleRate;
use Illuminate\Support\Facades\Password;
use App\Models\User;
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
        $utenti = Utente::all();
        return view('utenti.index', ['users' => $utenti]);
    }

    public function create()
    {
        return view('utenti.create', ['title' => 'Gestione Utenti']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'surname'  => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'surname'  => $request->surname,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'fissa'    => $request->fissa,
        ]);

        $this->saveUserRates($user->id, $request);

        return redirect()->route('utenti.index')->with('success', 'Utente Aggiunto con successo!');
    }

    public function show(Utente $utente)
    {
        return view('utenti.show', ['utente' => $utente, 'title' => 'Gestione Utenti']);
    }

    public function edit(Utente $utente)
    {
        $userRates = UserRoleRate::where('user_id', $utente->id)->where('role', 'user')->first();
        $utente = Utente::find($utente->id);
        return view('utenti.edit', [
            'user'      => $utente,
            'userRates' => $userRates,
            'title'     => 'Gestione Utenti',
        ]);
    }

    public function update(Request $request, Utente $utente)
    {
        $utente->update([
            'name'     => $request->name,
            'surname'  => $request->surname,
            'username' => $request->username,
            'email'    => $request->email,
            'role'     => $request->role,
            'fissa'    => $request->fissa,
        ]);

        UserRoleRate::where('user_id', $utente->id)->delete();
        $this->saveUserRates($utente->id, $request);

        return redirect()->route('utenti.index')->with('success', 'Utente Aggiornato con successo!');
    }

    public function destroy(Utente $utente)
    {
        $utente = Utente::find($utente->id);
        $utente->delete();
        return redirect()->route('utenti.index')->with('success', 'Utente Eliminato con successo!');
    }

    private function saveUserRates(int $userId, Request $request): void
    {
        $fields = ['giornata', 'feriale_estero', 'festivo', 'festivo_estero', 'straordinari', 'trasferta', 'trasferta_lunga', 'pernotto', 'presidio', 'tariffa_sabato', 'fissa'];
        $rates = $request->input('rates', []);

        $data = [];
        foreach ($fields as $f) {
            $val = $rates[$f] ?? null;
            $data[$f] = ($val !== null && $val !== '') ? $val : null;
        }

        if (array_filter($data, fn($v) => $v !== null)) {
            UserRoleRate::create(array_merge(['user_id' => $userId, 'role' => 'user'], $data));
        }
    }
}
