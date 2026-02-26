<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_role_rates', function (Blueprint $table) {
            $table->decimal('trasferta', 10, 2)->nullable()->after('tariffa_sabato');
            $table->decimal('trasferta_lunga', 10, 2)->nullable()->after('trasferta');
            $table->decimal('pernotto', 10, 2)->nullable()->after('trasferta_lunga');
            $table->decimal('presidio', 10, 2)->nullable()->after('pernotto');
            $table->decimal('festivo', 10, 2)->nullable()->after('presidio');
            $table->decimal('straordinari', 10, 2)->nullable()->after('festivo');
            $table->decimal('estero', 10, 2)->nullable()->after('straordinari');
        });
    }

    public function down(): void
    {
        Schema::table('user_role_rates', function (Blueprint $table) {
            $table->dropColumn(['trasferta', 'trasferta_lunga', 'pernotto', 'presidio', 'festivo', 'straordinari', 'estero']);
        });
    }
};
