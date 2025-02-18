<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Roles extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'roles';
    protected $fillable = [
        'role',
        'fields'
    ];
}
