<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\InstitutionController;

use App\Http\Controllers\TeacherController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\AuthController;

// --------------------------------------------------------------------------
// Rutas de Autenticación y Perfil
// --------------------------------------------------------------------------
Route::middleware('web')->group(function () {
    // Estas rutas usan 'web' para soporte de cookies/sesión en la Web SPA,
    // pero también devuelven Tokens para aplicaciones móviles (Flutter).
    
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);
        
        // Cursos (Privado)
        Route::post('/courses/{slug}/enroll', [\App\Http\Controllers\Api\CourseController::class, 'enroll']);
        Route::post('/courses/{slug}/progress', [\App\Http\Controllers\Api\CourseController::class, 'toggleProgress']);
        Route::get('/my-courses', [\App\Http\Controllers\Api\CourseController::class, 'myCourses']);

        // Evaluaciones
        Route::get('/evaluations/{id}', [\App\Http\Controllers\Api\EvaluationController::class, 'show']);
        Route::get('/evaluations/{id}/attempts', [\App\Http\Controllers\Api\EvaluationController::class, 'attempts']);
        Route::post('/evaluations/{id}/start', [\App\Http\Controllers\Api\EvaluationController::class, 'start']);
        Route::post('/evaluations/{id}/attempts/{attemptId}/finish', [\App\Http\Controllers\Api\EvaluationController::class, 'finish']);
        
        // Certificados y Pagos (Privado)
        Route::get('/my-certificates', [CertificateController::class, 'myCertificates']);
        Route::post('/payments', [\App\Http\Controllers\Api\PaymentController::class, 'store']);
        Route::get('/my-payments', [\App\Http\Controllers\Api\PaymentController::class, 'myPayments']);
    });

    // --------------------------------------------------------------------------
    // Rutas Públicas (Catálogo y Contenidos)
    // --------------------------------------------------------------------------
    Route::get('/courses', [\App\Http\Controllers\Api\CourseController::class, 'index']);
    Route::get('/courses/{slug}', [\App\Http\Controllers\Api\CourseController::class, 'show']);
    Route::get('/courses/{slug}/brochure', [\App\Http\Controllers\Api\CourseController::class, 'brochure']);
    Route::get('/modules/{id}/material/{filename}', [\App\Http\Controllers\Api\ModuleController::class, 'material']);
    
    // Pagos (Público)
    Route::get('/payment-methods', [\App\Http\Controllers\Api\PaymentController::class, 'index']);
    Route::get('/payment-methods/{id}/qr', [\App\Http\Controllers\Api\PaymentController::class, 'qrImage']);
});

// --------------------------------------------------------------------------
// Rutas de Utilidad (Sin middleware de sesión)
// --------------------------------------------------------------------------
Route::get('/validate-certificates', [CertificateController::class, 'search']);
Route::get('/institutions/{institution}/logo', [InstitutionController::class, 'logo']);
Route::get('/certificates/{id}/download', [CertificateController::class, 'download']);
Route::get('/certificates/{id}/view', [CertificateController::class, 'view']);

// Docentes
Route::get('/teachers', [TeacherController::class, 'index']);
Route::get('/teachers/{teacher}/image', [TeacherController::class, 'image']);
Route::get('/teachers/{id}', [TeacherController::class, 'show']);

// Libro de Reclamaciones
Route::post('/claims', [\App\Http\Controllers\ClaimController::class, 'store']);
Route::get('/claims/{ticket_code}', [\App\Http\Controllers\ClaimController::class, 'show']);
