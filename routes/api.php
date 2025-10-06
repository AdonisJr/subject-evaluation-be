<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadedTorController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\SubjectController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Public route
Route::post('/login', [AuthController::class, 'login']);
// Protected routes
Route::middleware('auth-ocr')->group(function () {

    Route::apiResource('/users', UserController::class);
    Route::apiResource('/tor', UploadedTorController::class);

    Route::apiResource('courses', CourseController::class);
    Route::apiResource('curriculums', CurriculumController::class);
    Route::apiResource('subjects', SubjectController::class);
    // Route::get('/dashboard', [AuthController::class, 'dashboard']);
});
