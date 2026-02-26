<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCompensation extends Model
{
    protected $table = 'user_compensations';
    protected $fillable = ['user_id', 'compensation_id', 'value'];
}
