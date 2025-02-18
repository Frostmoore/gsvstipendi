<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Compensation extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'compensations';
    protected $fillable = [
        'role',
        'value',
        'name'
    ];
}
