<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Utente extends Model
{
    protected $table = 'users';
    protected $fillable = [
        'name',
        'surname',
        'username',
        'email',
        'password',
        'role'
    ];
}
