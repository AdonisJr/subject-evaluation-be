<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadedTorController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TesseractOcrController;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::get('/test-cloudinary', function () {
    return [
        'env' => env('CLOUDINARY_URL'),
        'config' => config('cloudinary.cloud_url'),
    ];
});

// routes/web.php
Route::get('/check-cloudinary', function () {
    $cfg = config('cloudinary');
    return response()->json([
        'exists' => $cfg !== null,
        'type'   => gettype($cfg),
        'keys'   => array_keys($cfg ?? []),
        'cloud_url' => $cfg['cloud_url'] ?? 'missing',
    ]);
});
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
    Route::post('/process-tor/{id}/{curriculum_id}', [TesseractOcrController::class, 'analyzeTor']);
});
