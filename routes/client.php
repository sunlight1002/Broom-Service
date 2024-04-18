<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\Auth\AuthController;
use App\Http\Controllers\Client\ClientCardController;
use App\Http\Controllers\Client\ClientEmailController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\JobCommentController;
use App\Http\Controllers\Client\JobController;
use App\Http\Controllers\Client\WorkerController;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['auth:client-api', 'scopes:client']], function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('get-time', [DashboardController::class, 'getTime']);

    // Dashboard Routes
    Route::post('dashboard', [DashboardController::class, 'dashboard']);
    Route::post('schedule', [DashboardController::class, 'meetings'])->name('schedule');
    Route::post('offers', [DashboardController::class, 'offers'])->name('offers');
    Route::post('view-offer', [DashboardController::class, 'viewOffer'])->name('view-offer');
    Route::post('contracts', [DashboardController::class, 'contracts'])->name('contracts');
    Route::post('view-contract', [DashboardController::class, 'viewContract'])->name('view-contract');
    Route::post('get-contract/{id}', [DashboardController::class, 'getContract'])->name('get-contract');

    //job APis
    Route::post('jobs', [JobController::class, 'index']);
    Route::get('jobs/{id}', [JobController::class, 'show']);
    Route::put('jobs/{id}/cancel', [JobController::class, 'cancel']);
    Route::post('jobs/{id}/change-worker-request', [JobController::class, 'changeWorkerRequest']);
    Route::post('jobs/{id}/review', [JobController::class, 'saveReview']);

    // My Account Api
    Route::get('my-account', [DashboardController::class, 'getAccountDetails']);
    Route::post('my-account', [DashboardController::class, 'saveAccountDetails']);

    // Change Password Api
    Route::post('change-password', [DashboardController::class, 'changePassword']);

    Route::resource('job-comments', JobCommentController::class)->only(['index', 'store', 'destroy']);

    Route::get('get-card', [ClientCardController::class, 'index']);
    Route::post('cards/initialize-adding', [ClientCardController::class, 'createCardSession']);
    Route::delete('cards/{id}', [ClientCardController::class, 'destroy']);
    Route::put('cards/{id}/mark-default', [ClientCardController::class, 'markDefault']);

    Route::get('workers', [WorkerController::class, 'index']);
});

Route::post('login', [AuthController::class, 'login']);

// Emails Routes
Route::post('get-client', [ClientEmailController::class, 'getClient'])->name('get-client');
Route::get('get-schedule/{id}', [ClientEmailController::class, 'getSchedule'])->name('get-schedule');
Route::post('add-meet', [ClientEmailController::class, 'addMeet'])->name('add-meet');
Route::post('meeting', [ClientEmailController::class, 'ShowMeeting']);
Route::post('accept-meeting', [ClientEmailController::class, 'acceptMeeting']);
Route::post('reject-meeting', [ClientEmailController::class, 'rejectMeeting']);
Route::post('meeting/{id}/reschedule', [ClientEmailController::class, 'rescheduleMeeting']);
Route::post('meetings/{id}/slot-save', [ClientEmailController::class, 'saveMeetingSlot']);
Route::post('get-offer/{id}', [ClientEmailController::class, 'GetOffer'])->name('get-offer');
Route::post('accept-offer', [ClientEmailController::class, 'AcceptOffer'])->name('accept-offer');
Route::post('reject-offer', [ClientEmailController::class, 'RejectOffer'])->name('accept-offer');
Route::post('contracts/{hash}', [ClientEmailController::class, 'contractByHash']);
Route::post('contracts/{hash}/initialize-card', [ClientCardController::class, 'createCardSession']);
Route::post('contracts/{hash}/check-card', [ClientCardController::class, 'checkContractCard']);
Route::post('accept-contract', [ClientEmailController::class, 'AcceptContract']);
Route::post('reject-contract', [ClientEmailController::class, 'RejectContract']);
Route::post('get-service-template', [ClientEmailController::class, 'serviceTemplate'])->name('get-service-template');
Route::post('add-file', [DashboardController::class, 'addfile'])->name('add-file');
Route::post('delete-file', [DashboardController::class, 'deletefile'])->name('delete-file');
Route::post('get-files', [DashboardController::class, 'getfiles'])->name('get-files');
