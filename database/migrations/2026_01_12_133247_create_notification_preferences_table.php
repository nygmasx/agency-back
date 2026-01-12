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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collaborator_id')->constrained('client_collaborators')->cascadeOnDelete();
            $table->boolean('email_enabled')->default(true);
            $table->boolean('email_task_assigned')->default(true);
            $table->boolean('email_task_completed')->default(true);
            $table->boolean('email_task_comment')->default(true);
            $table->boolean('email_task_due_soon')->default(true);
            $table->boolean('email_daily_digest')->default(false);
            $table->boolean('push_enabled')->default(false);
            $table->timestamps();

            $table->unique('collaborator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
