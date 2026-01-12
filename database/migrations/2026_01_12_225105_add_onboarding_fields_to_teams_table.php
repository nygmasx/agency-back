<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('subdomain')->unique()->nullable()->after('slug');
            $table->string('business_type')->nullable();
            $table->string('client_count')->nullable();
            $table->boolean('onboarding_completed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['subdomain', 'business_type', 'client_count', 'onboarding_completed']);
        });
    }
};
