<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_role_rates', function (Blueprint $table) {
            $table->decimal('sielte', 10, 2)->nullable()->after('pernotto');
            $table->decimal('pernotto_sielte', 10, 2)->nullable()->after('sielte');
        });
    }

    public function down(): void
    {
        Schema::table('user_role_rates', function (Blueprint $table) {
            $table->dropColumn(['sielte', 'pernotto_sielte']);
        });
    }
};
