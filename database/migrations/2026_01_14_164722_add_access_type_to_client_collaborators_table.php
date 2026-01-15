<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_collaborators', function (Blueprint $table) {
            $table->enum('access_type', ['link', 'email'])->default('email')->after('permissions');
        });
    }

    public function down(): void
    {
        Schema::table('client_collaborators', function (Blueprint $table) {
            $table->dropColumn('access_type');
        });
    }
};
