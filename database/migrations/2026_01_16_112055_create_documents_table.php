<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->longText('content')->nullable();

            // Polymorphic creator (can be user or collaborator)
            $table->unsignedBigInteger('created_by_id');
            $table->string('created_by_type'); // 'user' or 'collaborator'

            // Polymorphic updater (can be user or collaborator)
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->string('updated_by_type')->nullable();

            $table->timestamps();

            $table->index(['client_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
