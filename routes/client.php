<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\Auth\AuthController;
use App\Http\Controllers\Client\ClientCardController;
use App\Http\Controllers\Client\ClientEmailController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\JobCommentController;
use App\Http\Controllers\Client\JobController;
use App\Http\Controllers\Client\WorkerController;
use App\Http\Controllers\ScheduleChangeController;

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
    Route::get('dashboard', [DashboardController::class, 'dashboard']);
    Route::get('schedule', [DashboardController::class, 'meetings']);
    Route::get('offers', [DashboardController::class, 'offers']);
    Route::post('view-offer', [DashboardController::class, 'viewOffer']);
    Route::get('contracts', [DashboardController::class, 'contracts']);
    Route::post('view-contract', [DashboardController::class, 'viewContract']);
    Route::post('get-contract/{id}', [DashboardController::class, 'getContract']);

    //job APis
    Route::get('jobs', [JobController::class, 'index']);
    Route::get('jobs/{id}', [JobController::class, 'show']);
    Route::put('jobs/{id}/cancel', [JobController::class, 'cancel']);
    Route::post('jobs/{id}/change-worker', [JobController::class, 'changeWorker']);
    Route::post('jobs/{id}/review', [JobController::class, 'saveReview']);
    Route::get('jobs/{id}/total-amount-by-group', [JobController::class, 'getOpenJobAmountByGroup']);
    Route::resource('jobs/{id}/comments', JobCommentController::class)->only(['index', 'store', 'destroy']);

    // Route::post('jobs/{id}/change-worker-request', [JobController::class, 'changeWorkerRequest']);


    Route::post('jobs/request-to-change', [ScheduleChangeController::class, 'requestToChange']);
    Route::get('/schedule-changes', [ScheduleChangeController::class, 'getAllScheduleChanges']);
    Route::put('/schedule-changes/{id}', [ScheduleChangeController::class, 'updateScheduleChange']);

    Route::get('jobs/{id}/comments', [JobCommentController::class, 'language']);

    // My Account Api
    Route::get('my-account', [DashboardController::class, 'getAccountDetails']);
    Route::post('my-account', [DashboardController::class, 'saveAccountDetails']);

    // Change Password Api
    Route::post('change-password', [DashboardController::class, 'changePassword']);

    Route::post('save-card',[ClientEmailController::class,'saveCard'])->name('save-card');
    Route::get('get-card', [ClientCardController::class, 'index']);
    Route::post('cards/initialize-adding', [ClientCardController::class, 'createCardSession']);
    Route::delete('cards/{id}', [ClientCardController::class, 'destroy']);
    Route::put('cards/{id}/mark-default', [ClientCardController::class, 'markDefault']);

    Route::get('workers', [WorkerController::class, 'index']);

    Route::get('invoices', [InvoiceController::class, 'index']);

});
Route::post('jobs/speak-to-manager', [JobController::class, 'addProblems']);
Route::post('jobs/get-problem', [JobController::class, 'getProblems']);
Route::delete('jobs/delete-problem/{id}', [JobController::class, 'deleteProblem']);

Route::post('login', [AuthController::class, 'login']);
Route::post('verifyOtp', [AuthController::class, 'verifyOtp']);
Route::post('resendOtp', [AuthController::class, 'resendOtp']);
Route::post('change-password', [AuthController::class, 'changePassword']);

// Emails Routes
Route::get('{id}/info', [ClientEmailController::class, 'getClientInfo']);
Route::get('get-schedule/{id}', [ClientEmailController::class, 'getSchedule']);
Route::post('add-meet', [ClientEmailController::class, 'addMeet']);
Route::post('meeting', [ClientEmailController::class, 'ShowMeeting']);
Route::post('accept-meeting', [ClientEmailController::class, 'acceptMeeting']);
Route::post('reject-meeting', [ClientEmailController::class, 'rejectMeeting']);
Route::post('meeting/{id}/reschedule', [ClientEmailController::class, 'rescheduleMeeting']);
Route::post('meetings/{id}/slot-save', [ClientEmailController::class, 'saveMeetingSlot']);
Route::post('get-offer/{id}', [ClientEmailController::class, 'GetOffer']);

Route::post('accept-offer', [ClientEmailController::class, 'AcceptOffer']);
Route::post('reject-offer', [ClientEmailController::class, 'RejectOffer']);
Route::post('contracts/{hash}', [ClientEmailController::class, 'contractByHash']);
Route::post('contracts/{hash}/initialize-card', [ClientCardController::class, 'createCardSession']);
Route::post('contracts/{hash}/check-card', [ClientCardController::class, 'checkContractCard']);
Route::post('accept-contract', [ClientEmailController::class, 'AcceptContract']);
Route::post('reject-contract', [ClientEmailController::class, 'RejectContract']);
Route::post('get-service-template', [ClientEmailController::class, 'serviceTemplate']);
Route::post('add-file', [DashboardController::class, 'addfile']);
Route::post('delete-file', [DashboardController::class, 'deletefile']);
Route::post('get-files', [DashboardController::class, 'getfiles']);
