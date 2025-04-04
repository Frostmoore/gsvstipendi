<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    protected $table = 'timesheets';
    protected $fillable = [
        'month',
        'year',
        'user',
        'role',
        'link',
        'company',
        'override_compensation',
        'override_fascia',
    ];
}
