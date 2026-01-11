<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ClientCollaboratorController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
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

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

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

    // Client Collaborators
    Route::get('/clients/{client}/collaborators', [ClientCollaboratorController::class, 'index']);
    Route::post('/clients/{client}/collaborators', [ClientCollaboratorController::class, 'store']);
    Route::delete('/clients/{client}/collaborators/{collaborator}', [ClientCollaboratorController::class, 'destroy']);
});

// Client Portal (token-based auth)
Route::prefix('portal')->group(function () {
    Route::post('/auth', [ClientPortalController::class, 'auth']);
    Route::get('/projects', [ClientPortalController::class, 'projects']);
    Route::get('/projects/{project}/tasks', [ClientPortalController::class, 'projectTasks']);
});
