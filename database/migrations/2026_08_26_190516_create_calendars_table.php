<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'owner_id']);
        });

        Schema::create('calendar_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'writer', 'reader'])->default('writer');
            $table->timestamps();

            $table->unique(['calendar_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_members');
        Schema::dropIfExists('calendars');
    }
};
