<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ClientCollaboratorController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalCollaboratorController;
use App\Http\Controllers\Portal\PortalConversationController;
use App\Http\Controllers\Portal\PortalFileController;
use App\Http\Controllers\Portal\PortalMessageController;
use App\Http\Controllers\Portal\PortalNotificationController;
use App\Http\Controllers\Portal\PortalProjectController;
use App\Http\Controllers\Portal\PortalTaskCommentController;
use App\Http\Controllers\Portal\PortalTaskController;
use App\Http\Controllers\Portal\PortalTaskFileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Google OAuth
    Route::get('/google/url', [GoogleAuthController::class, 'redirectUrl']);
    Route::post('/google/callback', [GoogleAuthController::class, 'callback']);
    Route::post('/google/token', [GoogleAuthController::class, 'callbackWithToken']);
});

// Public invitation info
Route::get('/invitations/{token}', [TeamInvitationController::class, 'show']);

// Check subdomain availability (public)
Route::get('/check-subdomain/{subdomain}', [OnboardingController::class, 'checkSubdomain']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Onboarding
    Route::post('/onboarding', [OnboardingController::class, 'complete']);

    // Team (auto-created on register)
    Route::get('/team', [TeamController::class, 'myTeam']);
    Route::get('/teams', [TeamController::class, 'index']);

    // Team Members
    Route::get('/teams/{team}/members', [TeamMemberController::class, 'index']);
    Route::put('/teams/{team}/members/{user}', [TeamMemberController::class, 'update']);
    Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy']);
    Route::post('/teams/{team}/leave', [TeamMemberController::class, 'leave']);

    // Team Invitations
    Route::get('/teams/{team}/invitations', [TeamInvitationController::class, 'index']);
    Route::post('/teams/{team}/invitations', [TeamInvitationController::class, 'store']);
    Route::delete('/teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy']);
    Route::post('/invitations/{token}/accept', [TeamInvitationController::class, 'accept']);

    // Team Tasks
    Route::get('/teams/{team}/tasks', [TaskController::class, 'index']);
    Route::post('/teams/{team}/tasks', [TaskController::class, 'store']);
    Route::get('/teams/{team}/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/teams/{team}/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/teams/{team}/tasks/{task}', [TaskController::class, 'destroy']);
    Route::patch('/teams/{team}/tasks/{task}/progress', [TaskController::class, 'updateProgress']);
    Route::patch('/teams/{team}/tasks/reorder', [TaskController::class, 'reorder']);

    // Task Comments
    Route::get('/tasks/{task}/comments', [TaskCommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store']);
    Route::delete('/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy']);

    // Clients
    Route::get('/teams/{team}/clients', [ClientController::class, 'index']);
    Route::post('/teams/{team}/clients', [ClientController::class, 'store']);
    Route::get('/teams/{team}/clients/{client}', [ClientController::class, 'show']);
    Route::put('/teams/{team}/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/teams/{team}/clients/{client}', [ClientController::class, 'destroy']);

    // Projects
    Route::get('/clients/{client}/projects', [ProjectController::class, 'index']);
    Route::post('/clients/{client}/projects', [ProjectController::class, 'store']);
    Route::get('/clients/{client}/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/clients/{client}/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/clients/{client}/projects/{project}', [ProjectController::class, 'destroy']);

    // Project Tasks
    Route::get('/projects/{project}/tasks', [TaskController::class, 'projectTasks']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'storeProjectTask']);

    // Client Tasks (toutes les tâches de tous les projets d'un client)
    Route::get('/clients/{client}/tasks', [TaskController::class, 'clientTasks']);
    Route::post('/clients/{client}/tasks', [TaskController::class, 'storeClientTask']);

    // Client Collaborators
    Route::get('/clients/{client}/collaborators', [ClientCollaboratorController::class, 'index']);
    Route::post('/clients/{client}/collaborators', [ClientCollaboratorController::class, 'store']);
    Route::put('/clients/{client}/collaborators/{collaborator}', [ClientCollaboratorController::class, 'update']);
    Route::post('/clients/{client}/collaborators/{collaborator}/resend-invitation', [ClientCollaboratorController::class, 'resendInvitation']);
    Route::delete('/clients/{client}/collaborators/{collaborator}', [ClientCollaboratorController::class, 'destroy']);
});

// Client Portal (token-based auth)
Route::prefix('portal')->group(function () {
    // Public auth endpoints
    Route::post('/auth', [PortalAuthController::class, 'authenticate']);
    Route::post('/auth/request-code', [PortalAuthController::class, 'requestCode']);
    Route::post('/auth/verify-code', [PortalAuthController::class, 'verifyCode']);

    // Protected portal routes
    Route::middleware('portal.auth')->group(function () {
        // Projects
        Route::get('/projects', [PortalProjectController::class, 'index']);
        Route::get('/projects/{project}/tasks', [PortalProjectController::class, 'tasks']);

        // Tasks
        Route::get('/tasks', [PortalTaskController::class, 'index']);
        Route::get('/tasks/{task}', [PortalTaskController::class, 'show']);
        Route::post('/projects/{project}/tasks', [PortalTaskController::class, 'store']);
        Route::put('/tasks/{task}', [PortalTaskController::class, 'update']);
        Route::delete('/tasks/{task}', [PortalTaskController::class, 'destroy']);

        // Task Comments
        Route::get('/tasks/{task}/comments', [PortalTaskCommentController::class, 'index']);
        Route::post('/tasks/{task}/comments', [PortalTaskCommentController::class, 'store']);
        Route::delete('/tasks/{task}/comments/{comment}', [PortalTaskCommentController::class, 'destroy']);

        // Task Files
        Route::get('/tasks/{task}/files', [PortalTaskFileController::class, 'index']);
        Route::post('/tasks/{task}/files', [PortalTaskFileController::class, 'store']);
        Route::delete('/tasks/{task}/files/{file}', [PortalTaskFileController::class, 'destroy']);

        // Client Files
        Route::get('/files', [PortalFileController::class, 'index']);
        Route::post('/files', [PortalFileController::class, 'store']);
        Route::delete('/files/{file}', [PortalFileController::class, 'destroy']);

        // Conversations
        Route::get('/conversations', [PortalConversationController::class, 'index']);
        Route::post('/conversations', [PortalConversationController::class, 'store']);

        // Messages
        Route::get('/conversations/{conversation}/messages', [PortalMessageController::class, 'index']);
        Route::post('/conversations/{conversation}/messages', [PortalMessageController::class, 'store']);

        // Notifications
        Route::get('/notifications', [PortalNotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [PortalNotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [PortalNotificationController::class, 'markAllRead']);

        // Notification Preferences
        Route::get('/notifications/preferences', [PortalNotificationController::class, 'preferences']);
        Route::put('/notifications/preferences', [PortalNotificationController::class, 'updatePreferences']);

        // Collaborators (portal-side management)
        Route::get('/collaborators', [PortalCollaboratorController::class, 'index']);
        Route::post('/collaborators', [PortalCollaboratorController::class, 'store']);
    });
});
