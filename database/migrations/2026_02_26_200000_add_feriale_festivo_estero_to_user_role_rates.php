<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_role_rates', function (Blueprint $table) {
            $table->renameColumn('estero', 'feriale_estero');
            $table->decimal('festivo_estero', 10, 2)->nullable()->after('festivo');
        });
    }

    public function down(): void
    {
        Schema::table('user_role_rates', function (Blueprint $table) {
            $table->dropColumn('festivo_estero');
            $table->renameColumn('feriale_estero', 'estero');
        });
    }
};
