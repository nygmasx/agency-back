<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_login_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('email');
            $table->index(['email', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_login_codes');
    }
};
