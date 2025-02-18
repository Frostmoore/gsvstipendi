<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RolesController;
use App\Http\Middleware\AdminMiddleware;
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

Route::get('/profile/users/{user}', function (User $user) {
    return view('profile.show', ['user' => $user]);
})->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.show');

Route::get('/profile/users/{user}/edit', function (User $user) {
    return view('profile.edit', ['user' => $user]);
})->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.edit');

Route::patch('/profile/users/{user}', function (User $user) {
    $user->update(request()->all());
    return redirect()->route('profile.users')->with('success', 'Utente Aggiornato con successo!');
})->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.update');

Route::delete('/profile/users/{user}', function (User $user) {
    $user->delete();
    return redirect()->route('profile.users')->with('success', 'Utente Eliminato con successo!');
})->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.destroy');

Route::get('/profile/users/create', function () {
    return view('profile.create');
})->middleware(['auth', 'verified', AdminMiddleware::class])->name('profile.users.create');

Route::controller(ProfileController::class)
->prefix('profile')
->name('profile.')
->middleware(['auth', AdminMiddleware::class])
->group(function () {
    Route::get('/', 'edit')->name('edit');
    Route::patch('/', 'update')->name('update');
    Route::delete('/', 'destroy')->name('destroy');
    Route::get('/users', 'users')->name('users');
    Route::get('/users/{user}', 'show')->name('users.show');
    Route::get('/users/{user}/edit', 'edit')->name('users.edit');
    Route::patch('/users/{user}', 'update')->name('users.update');
    Route::delete('/users/{user}', 'destroy')->name('users.destroy');
    Route::get('/users/create', 'create')->name('users.create');
});

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


require __DIR__.'/auth.php';
