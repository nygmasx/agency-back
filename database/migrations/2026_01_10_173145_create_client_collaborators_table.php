<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('name');
            $table->string('token')->unique();
            $table->json('permissions')->nullable(); // ['view', 'comment', 'edit']
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_collaborators');
    }
};
