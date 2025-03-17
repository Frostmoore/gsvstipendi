<?php

namespace App\Http\Controllers;

use App\Models\Companies;
use Illuminate\Http\Request;

class CompaniesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = Companies::all();
        return view('companies.index', ['companies' => $companies]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('companies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $companies = Companies::create($request->all());
        return redirect()->route('companies.index')->with('success', 'Azienda Aggiunta con successo!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Companies $companies)
    {
        return view('companies.show', ['company' => $companies, 'title' => 'Gestione Aziende']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Companies $companies)
    {
        return view('companies.edit', compact('companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Companies $companies)
    {
        $companies->update($request->all());
        return redirect()->route('companies.index')->with('success', 'Azienda Aggiornata con successo!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Companies $companies)
    {
        $company = Companies::find($companies->id);
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Azienda Eliminata con successo!');
    }
}
