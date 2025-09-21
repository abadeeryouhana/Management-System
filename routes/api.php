<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDependencyController;

Route::get('/login', function () {
    return response()->json(['error' => 'Please use the API login endpoint'], 401);
})->name('login');

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

});

Route::group(['middleware' => 'jwt.auth'], function () {
    Route::group(['prefix' => 'auth'], function () {
         Route::post('me', [AuthController::class, 'userProfile']);
    });
    // Task routes
    Route::apiResource('tasks', TaskController::class);

    // Task dependency routes
    Route::post('dependencies', [TaskDependencyController::class, 'store']);
    Route::delete('dependencies/{id}', [TaskDependencyController::class, 'destroy']);
});
