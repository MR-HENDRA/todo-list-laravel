<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::get('/', [TaskController::class, 'index']);
Route::post('/tasks', [TaskController::class, 'store']);
Route::put('/tasks/{task}', [TaskController::class, 'update']);
Route::put('/tasks/{task}/edit', [TaskController::class, 'updateTask']);
Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
Route::get('/tasks/by-date', [TaskController::class, 'getTasksByDate']);
Route::get('/tasks/all', [TaskController::class, 'getAllTasks']);
