<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleRate extends Model
{
    protected $table = 'user_role_rates';
    protected $fillable = [
        'user_id', 'role',
        'figc_feriale_italia', 'feriale_estero',
        'figc_festivo_italia', 'festivo_estero',
        'figc_trasp_autista', 'figc_trasp_accompagnatore',
        'presidio_autisti', 'presidio_accompagnatori',
        'autista_no_figc',
        'trasferta', 'trasferta_media', 'trasferta_lunga',
        'pernotto', 'straordinari',
        'fissa', 'tariffa_sabato',
    ];
}
