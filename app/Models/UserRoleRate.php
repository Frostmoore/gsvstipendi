<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleRate extends Model
{
    protected $table = 'user_role_rates';
    protected $fillable = ['user_id', 'role', 'giornata', 'fissa', 'tariffa_sabato', 'trasferta', 'trasferta_lunga', 'pernotto', 'presidio', 'festivo', 'festivo_estero', 'straordinari', 'feriale_estero'];
}
