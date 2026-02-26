<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_role_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role'); // role name (matches timesheets.role)
            $table->decimal('fissa', 10, 2)->nullable();
            $table->decimal('tariffa_sabato', 10, 2)->nullable();
            $table->unique(['user_id', 'role']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role_rates');
    }
};
