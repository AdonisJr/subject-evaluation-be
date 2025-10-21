<?php

use App\Http\Controllers\AdvisingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadedTorController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TesseractOcrController;
use App\Http\Controllers\TorApprovalController;
use App\Http\Controllers\TorGradeController;
use App\Http\Controllers\UserOtherInfoController;
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
Route::post('/register', [AuthController::class, 'register']);
// Protected routes
Route::middleware('auth-ocr')->group(function () {

    Route::apiResource('/users', UserController::class);
    Route::get('/me', [UserController::class, 'getMyInfo']);

    // curriculums
    Route::get('/curriculums', [CurriculumController::class, 'index']);
    Route::post('/curriculums', [CurriculumController::class, 'store']);
    Route::put('/curriculums/{id}', [CurriculumController::class, 'update']);
    Route::delete('/curriculums/{id}', [CurriculumController::class, 'destroy']);
    Route::get('/curriculums/{curriculum_id}/subjects', [SubjectController::class, 'getByCurriculum']);

    Route::post('/tor/upload/{curriculum_id}', [UploadedTorController::class, 'storeWithCurriculum']);
    Route::apiResource('/tor', UploadedTorController::class);
    Route::get('/fetchMyTors', [UploadedTorController::class, 'fetchMyTors']);

    Route::apiResource('courses', CourseController::class);
    // Route::apiResource('curriculums', CurriculumController::class);

    Route::apiResource('subjects', SubjectController::class);
    Route::get('/curriculums/{curriculum_id}/subjects', [SubjectController::class, 'getByCurriculum']);

    // Route::get('/dashboard', [AuthController::class, 'dashboard']);

    Route::post('/process-tor/{id}/{curriculum_id}', [TesseractOcrController::class, 'analyzeTor']);
    Route::apiResource('subjects', SubjectController::class);

    // TOR grade
    Route::apiResource('grades', TorGradeController::class);

    Route::get('/users/other-info', [UserOtherInfoController::class, 'show']);
    Route::post('/users/other-info', [UserOtherInfoController::class, 'storeOrUpdate']);

    // advising
    Route::post('/advising', [AdvisingController::class, 'store']);
    Route::get('/advising/{torId}', [AdvisingController::class, 'show']);
    Route::post('/new-student/advising', [AdvisingController::class, 'store']);

    Route::post('/new-student/advising', [AdvisingController::class, 'newStudentAdvising']);

    // notification
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Approver route
    Route::post('/tors/approve', [TorApprovalController::class, 'approve']);
    Route::post('/tors/reject/{tor_id}', [TorApprovalController::class, 'rejectTor']);
    
});
