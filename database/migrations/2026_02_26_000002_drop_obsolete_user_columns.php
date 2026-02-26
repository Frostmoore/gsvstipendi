<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fascia', 'trasferta', 'incremento', 'special']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fascia')->nullable();
            $table->string('trasferta')->nullable();
            $table->string('incremento')->nullable();
            $table->string('special')->nullable();
        });
    }
};
