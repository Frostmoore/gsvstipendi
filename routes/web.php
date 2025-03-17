<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\CompensationController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\UtenteController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\UserTimesheetController;
use App\Http\Controllers\OperatorRolesController;
use App\Http\Controllers\CompaniesController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/profile/users', function () {
    $users = User::all();
    return view('profile.users', ['users' => $users]);
})->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users');

//
    // Route::get('/profile/users/{user}', function (User $user) {
    //     return view('profile.show', ['user' => $user]);
    // })->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.show');

    // Route::get('/profile/users/{user}/edit', function (User $user) {
    //     return view('profile.edit', ['user' => $user]);
    // })->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.edit');

    // Route::patch('/profile/users/{user}', function (User $user) {
    //     $user->update(request()->all());
    //     return redirect()->route('profile.users')->with('success', 'Utente Aggiornato con successo!');
    // })->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.update');

    // Route::delete('/profile/users/{user}', function (User $user) {
    //     $user->delete();
    //     return redirect()->route('profile.users')->with('success', 'Utente Eliminato con successo!');
    // })->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.destroy');

    // Route::get('/profile/users/create', function () {
    //     return view('profile.create');
    // })->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.create');

    // Route::controller(ProfileController::class)
    // ->prefix('profile')
    // ->name('profile.')
    // ->middleware(['auth', AdminMiddleware::class])
    // ->group(function () {
    //     Route::get('/', 'edit')->name('edit');
    //     Route::patch('/', 'update')->name('update');
    //     Route::delete('/', 'destroy')->name('destroy');
    //     Route::get('/users', 'users')->name('users');
    //     Route::get('/users/{user}', 'show')->name('users.show');
    //     Route::get('/users/{user}/edit', 'edit')->name('users.edit');
    //     Route::patch('/users/{user}', 'update')->name('users.update');
    //     Route::delete('/users/{user}', 'destroy')->name('users.destroy');
    //     Route::get('/users/create', 'create')->name('users.create');
    // });
//

Route::controller(RolesController::class)
->prefix('roles')
->name('roles.')
->middleware(['auth', AdminMiddleware::class])
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{roles}', 'show')->name('show');
    Route::get('/{roles}/edit', 'edit')->name('edit');
    Route::patch('/{roles}/update', 'update')->name('update');
    Route::delete('/{roles}/destroy', 'destroy')->name('destroy');
});

Route::controller(CompensationController::class)
->prefix('compensations')
->name('compensations.')
->middleware(['auth', AdminMiddleware::class])
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{compensation}', 'show')->name('show');
    Route::get('/{compensation}/edit', 'edit')->name('edit');
    Route::patch('/{compensation}/update', 'update')->name('update');
    Route::delete('/{compensation}/destroy', 'destroy')->name('destroy');
});

Route::controller(UtenteController::class)
->prefix('utenti')
->name('utenti.')
->middleware(['auth', AdminMiddleware::class])
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/{utente}/passwordReset', [UtenteController::class, 'passwordReset'])->name('passwordReset');
    Route::post('/', 'store')->name('store');
    Route::get('/{utente}', 'show')->name('show');
    Route::get('/{utente}/edit', 'edit')->name('edit');
    Route::patch('/{utente}/update', 'update')->name('update');
    Route::delete('/{utente}/destroy', 'destroy')->name('destroy');
});

Route::controller(TimesheetController::class)
->prefix('timesheets')
->name('timesheets.')
->middleware(['auth', AdminMiddleware::class])
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{timesheet}', 'show')->name('show');
    Route::get('/{timesheet}/edit', 'edit')->name('edit');
    Route::patch('/{timesheet}/update', 'update')->name('update');
    Route::delete('/{timesheet}/destroy', 'destroy')->name('destroy');
});

Route::controller(UserTimesheetController::class)
->prefix('user-timesheets')
->name('user-timesheets.')
->middleware(['auth', AdminMiddleware::class])
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{userTimesheet}', 'show')->name('show');
    Route::get('/{userTimesheet}/edit', 'edit')->name('edit');
    Route::patch('/{userTimesheet}/update', 'update')->name('update');
    Route::delete('/{userTimesheet}/destroy', 'destroy')->name('destroy');
});

Route::controller(OperatorRolesController::class)
->prefix('operatorRoles')
->name('operatorRoles.')
->middleware(['auth', AdminMiddleware::class])
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{operatorRoles}', 'show')->name('show');
    Route::get('/{operatorRoles}/edit', 'edit')->name('edit');
    Route::patch('/{operatorRoles}/update', 'update')->name('update');
    Route::delete('/{operatorRoles}/destroy', 'destroy')->name('destroy');
});

Route::controller(CompaniesController::class)
->prefix('companies')
->name('companies.')
->middleware(['auth', AdminMiddleware::class])
->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{companies}', 'show')->name('show');
    Route::get('/{companies}/edit', 'edit')->name('edit');
    Route::patch('/{companies}/update', 'update')->name('update');
    Route::delete('/{companies}/destroy', 'destroy')->name('destroy');
});

Route::get('/search-users', [UserSearchController::class, 'search'])->name('search.users');

require __DIR__.'/auth.php';
