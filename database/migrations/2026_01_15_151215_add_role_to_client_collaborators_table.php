<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_collaborators', function (Blueprint $table) {
            $table->string('role')->default('viewer')->after('access_type');
        });
    }

    public function down(): void
    {
        Schema::table('client_collaborators', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
