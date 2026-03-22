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
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('completed');
            $table->timestamp('column_updated_at')->nullable();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('count_completed_tasks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('completed')->default(false);
            $table->dropColumn('column_updated_at');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->integer('count_completed_tasks')->default(0);
        });
    }
};
