<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\CourseOfferingController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\CourseDoctorController;
use App\Http\Controllers\ContentTypeController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| المسارات العامة (متاحة للجميع بدون تسجيل دخول أو توكن)
|--------------------------------------------------------------------------
*/

// المصادقة والحسابات الأساسية
Route::post('/login', [AuthController::class, 'login']);
Route::post('/check-user', [UserController::class, 'checkUser']);
Route::post('/add-admin', [UserController::class, 'addAdmin']);
Route::post('/add-contextMangment', [UserController::class, 'addContextMangmet']);

// مسارات القراءة والاستعراض العام (GET فقط)
Route::apiResource('departments', DepartmentController::class)->only(['index', 'show']);
Route::apiResource('levels', LevelController::class)->only(['index', 'show']);
Route::apiResource('semesters', SemesterController::class)->only(['index', 'show']);
Route::apiResource('courses', CourseController::class)->only(['index', 'show']);
Route::apiResource('doctors', DoctorController::class)->only(['index', 'show']);
Route::apiResource('course-offerings', CourseOfferingController::class)->only(['index', 'show']);
Route::apiResource('course-doctors', CourseDoctorController::class)->only(['index', 'show']);
Route::apiResource('contents', ContentController::class)->only(['index', 'show']);
Route::get('/content-types', [ContentTypeController::class, 'index']);


/*
|--------------------------------------------------------------------------
| المسارات المحمية بواسطة JWT (تتطلب Bearer Token للإضافة والتعديل والحذف)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    // 1. إدارة الجلسة والمصادقة
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // 2. الهيكل الأكاديمي (الإنشاء والتعديل والحذف فقط)
    Route::apiResource('departments', DepartmentController::class)->except(['index', 'show']);
    Route::apiResource('levels', LevelController::class)->except(['index', 'show']);
    Route::apiResource('semesters', SemesterController::class)->except(['index', 'show']);

    // 3. المواد والكادر الأكاديمي (الإنشاء والتعديل والحذف فقط)
    Route::apiResource('courses', CourseController::class)->except(['index', 'show']);
    Route::apiResource('doctors', DoctorController::class)->except(['index', 'show']);

    // 4. طرح المقررات وإسناد الدكاترة (الإنشاء والتعديل والحذف فقط)
    Route::apiResource('course-offerings', CourseOfferingController::class)->except(['index', 'show']);
    Route::apiResource('course-doctors', CourseDoctorController::class)->except(['index', 'show']);

    // 5. إدارة المحتوى والملفات (رفع المحاضرات والتعديل والحذف فقط)
    Route::apiResource('contents', ContentController::class)->except(['index', 'show']);

    // 6. إدارة المشرفين والصلاحيات (محمية بالكامل)
    Route::get('/staff', [UserController::class, 'indexStaff']);
    Route::put('/staff/{id}', [UserController::class, 'updateStaff']);
    Route::delete('/staff/{id}', [UserController::class, 'destroyStaff']);
});
