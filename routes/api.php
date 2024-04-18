<?php

use App\Http\Controllers\Client\MeetingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\Auth\AuthController;
use App\Http\Controllers\User\JobController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\JobCommentController;
use App\Http\Controllers\DocumentController;
/*
|--------------------------------------------------------------------------
| Employee API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Unauthenticated Routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::get('showPdf/{id}', [AuthController::class, 'showPdf']);
Route::post('worker-detail', [AuthController::class, 'getWorkerDetail']);
Route::post('work-contract', [AuthController::class, 'WorkContract']);
Route::get('work-contract/{id}', [AuthController::class, 'getWorkContract']);
Route::post('form101', [AuthController::class, 'form101']);
Route::get('get101/{id}', [AuthController::class, 'get101']);
Route::post('worker/{wid}/jobs/{jid}', [JobController::class, 'workerJob']);
Route::post('worker/{wid}/jobs/{jid}/approve', [JobController::class, 'approveWorkerJob']);
Route::get('teams/availability/{id}/date/{date}', [MeetingController::class, 'availabilityByDate']);

// Authenticated Routes
Route::group(['middleware' => ['auth:api', 'scopes:user']], function () {
    Route::post('dashboard', [DashboardController::class, 'dashboard']);
    Route::get('get-time', [DashboardController::class, 'getTime']);
    // not Available date
    Route::get('not-available-dates', [DashboardController::class, 'notAvailableDates']);
    Route::post('not-available-date', [DashboardController::class, 'addNotAvailableDates']);
    Route::post('delete-not-available-date', [DashboardController::class, 'deleteNotAvailableDates']);

    Route::resource('jobs', JobController::class)->only(['index', 'show', 'update']);
    Route::post('job-start-time', [JobController::class, 'JobStartTime']);
    Route::post('job-end-time', [JobController::class, 'JobEndTime']);
    Route::post('get-job-time', [JobController::class, 'getJobTime']);
    Route::post('job-opening-timestamp', [JobController::class, 'setJobOpeningTimestamp']);

    Route::resource('job-comments', JobCommentController::class)->only(['index', 'store', 'destroy']);
    Route::get('availabilities', [JobController::class, 'getAvailability']);
    Route::post('availabilities', [JobController::class, 'updateAvailability']);

    Route::post('upload/{id}', [AuthController::class, 'upload']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('details', [AuthController::class, 'details']);
    Route::post('profile', [AuthController::class, 'updateProfile']);

    Route::get('document/{id}', [DocumentController::class, 'documents']);
});