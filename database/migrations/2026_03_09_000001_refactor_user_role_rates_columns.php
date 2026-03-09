<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_role_rates', function (Blueprint $table) {
            $table->renameColumn('giornata', 'figc_feriale_italia');
            $table->renameColumn('festivo', 'figc_festivo_italia');
            $table->renameColumn('presidio', 'presidio_autisti');
            $table->decimal('figc_trasp_autista', 8, 2)->default(0)->after('festivo_estero');
            $table->decimal('figc_trasp_accompagnatore', 8, 2)->default(0)->after('figc_trasp_autista');
            $table->decimal('presidio_accompagnatori', 8, 2)->default(0)->after('presidio_autisti');
            $table->decimal('autista_no_figc', 8, 2)->default(0)->after('presidio_accompagnatori');
            $table->decimal('trasferta_media', 8, 2)->default(0)->after('trasferta');
        });
    }

    public function down(): void
    {
        Schema::table('user_role_rates', function (Blueprint $table) {
            $table->renameColumn('figc_feriale_italia', 'giornata');
            $table->renameColumn('figc_festivo_italia', 'festivo');
            $table->renameColumn('presidio_autisti', 'presidio');
            $table->dropColumn(['figc_trasp_autista', 'figc_trasp_accompagnatore', 'presidio_accompagnatori', 'autista_no_figc', 'trasferta_media']);
        });
    }
};
