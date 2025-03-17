<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Companies extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'companies';
    protected $fillable = [
        'name'
    ];
}
