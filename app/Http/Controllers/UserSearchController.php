<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Utente;

class UserSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->query('query');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $users = Utente::where('name', 'like', '%' . $query . '%')
            ->orWhere('surname', 'like', '%' . $query . '%')
            ->orWhere('email', 'like', '%' . $query . '%')
            ->orWhere('role', 'like', '%' . $query . '%') // Cerca anche per ruolo
            ->limit(10)
            ->get(['id', 'name', 'surname', 'email', 'role']); // Restituisce solo questi campi

        return response()->json($users);
    }
}
