<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fissa')->nullable()->after('role');
            $table->string('fascia')->nullable()->after('fissa');
            $table->string('special')->nullable()->after('fascia');
            $table->string('trasferta')->nullable()->after('special');
            $table->string('incremento')->nullable()->after('trasferta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fissa');
            $table->dropColumn('fascia');
            $table->dropColumn('special');
            $table->dropColumn('trasferta');
            $table->dropColumn('incremento');
        });
    }
};
