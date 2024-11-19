<?php

use App\Http\Controllers\Client\MeetingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\Auth\AuthController;
use App\Http\Controllers\User\JobController;
use App\Http\Controllers\User\JobCommentController;
use App\Http\Controllers\User\DocumentController;
use App\Http\Controllers\TwimlController;
use App\Http\Controllers\Api\LeadTwilioController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\PayrollReportController;
use App\Http\Controllers\SickLeaveController;
use App\Http\Controllers\Admin\AdvanceLoanController;
use App\Http\Controllers\User\SkippedCommentController;
use App\Http\Controllers\RefundClaimController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\ScheduleChangeController;
use App\Http\Controllers\HearingProtocolController;
use App\Http\Controllers\HearingCommentController;

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
Route::post('twiml', [TwimlController::class, 'index']);
Route::post('twiml/handlelanguage', [TwimlController::class, 'handleLanguage']);
Route::post('twiml/handleSelection', [TwimlController::class, 'handleSelection']);
Route::post('twiml/handleName', [TwimlController::class, 'handleName']);


Route::post('login', [AuthController::class, 'login']);
Route::post('verifyOtp', [AuthController::class, 'verifyOtp']);
Route::post('resendOtp', [AuthController::class, 'resendOtp']);
Route::post('register', [AuthController::class, 'register']);
Route::get('showPdf/{id}', [AuthController::class, 'showPdf']);
Route::post('worker-detail', [AuthController::class, 'getWorkerDetail']);
Route::post('{id}/work-contract', [AuthController::class, 'WorkContract']);
Route::get('work-contract/{id}', [AuthController::class, 'getWorkContract']);
Route::post('form101/{id}', [AuthController::class, 'form101']);
Route::get('get101/{id}/{formId?}', [AuthController::class, 'get101']);
Route::get('getAllForms/{id}', [AuthController::class, 'getAllForms']);
Route::post('{id}/safegear', [AuthController::class, 'safegear']);
Route::get('getSafegear/{id}', [AuthController::class, 'getSafegear']);
Route::get('worker/{id}/insurance-form', [AuthController::class, 'getInsuranceForm']);
Route::post('worker/{id}/insurance-form', [AuthController::class, 'saveInsuranceForm']);
Route::get('worker/{id}', [AuthController::class, 'getWorker']);
Route::get('worker-invitation/{id}', [AuthController::class, 'getWorkerInvitation']);
Route::post('worker-invitation-update/{id}', [AuthController::class, 'getWorkerInvitationUpdate']);

Route::post('worker/{wid}/jobs/{jid}', [JobController::class, 'workerJob']);
Route::post('guest/{wid}/jobs/{jid}/approve', [JobController::class, 'approveWorkerJob']);
Route::get('teams/availability/{id}/date/{date}', [MeetingController::class, 'availabilityByDate']);

// Authenticated Routes
Route::group(['middleware' => ['auth:api', 'scopes:user']], function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard']);
    Route::get('get-time', [DashboardController::class, 'getTime']);
    // not Available date
    Route::get('not-available-dates', [DashboardController::class, 'notAvailableDates']);
    Route::post('not-available-date', [DashboardController::class, 'addNotAvailableDates']);
    Route::post('delete-not-available-date', [DashboardController::class, 'deleteNotAvailableDates']);
    Route::post('worker/contact-manager/{id}', [JobController::class, 'ContactManager']);

    Route::get('jobs/today', [JobController::class, 'todayJobs']);
    Route::resource('jobs', JobController::class)->only(['index', 'show']);
    Route::post('jobs/{id}/start-time', [JobController::class, 'JobStartTime']);
    Route::post('jobs/{id}/end-time', [JobController::class, 'JobEndTime']);
    Route::post('get-job-time', [JobController::class, 'getJobTime']);
    Route::post('worker/{wid}/jobs/{jid}/approve', [JobController::class, 'approveWorkerJob']);
    Route::post('job-opening-timestamp', [JobController::class, 'setJobOpeningTimestamp']);
    Route::get('jobs/{id}/comments', [JobCommentController::class, 'index']);
    Route::post('jobs/need-extra-time/{job_id}', [JobController::class, 'NeedExtraTime']);

    Route::resource('job-comments', JobCommentController::class)->only(['store', 'destroy']);
    Route::post('job-comments/mark-complete', [JobCommentController::class, 'markComplete']);
    // Route::post('jobs/{id}/adjust-time', [JobCommentController::class, 'adjustJobCompleteTime']);

    Route::post('job-comments/skip-comment', [SkippedCommentController::class, 'store']);
    Route::get('job-comments/skipped-comments', [SkippedCommentController::class, 'index']);
    Route::post('job-comments/update-status', [SkippedCommentController::class, 'updateStatus']);

    Route::get('/schedule', [DashboardController::class, 'index']);

    Route::get('/protocol', [HearingProtocolController::class, 'show']);
    Route::post('/comments', [HearingCommentController::class, 'store']);

    Route::get('availabilities', [JobController::class, 'getAvailability']);
    Route::post('availabilities', [JobController::class, 'updateAvailability']);

    Route::get('doc-types', [DocumentController::class, 'getDocumentTypes']);
    Route::post('upload', [DocumentController::class, 'upload']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('details', [AuthController::class, 'details']);
    Route::post('profile', [AuthController::class, 'updateProfile']);

    Route::get('documents', [DocumentController::class, 'documents']);
    Route::get('forms', [DocumentController::class, 'forms']);

    //task and comment
    Route::apiResource('/phase', PhaseController::class)->only(['index', 'show']);
    Route::apiResource('/tasks', TaskController::class);
    Route::post('/tasks/{taskId}/comments', [TaskController::class, 'addComment']);
    Route::delete('/comments/{commentId}', [TaskController::class, 'deleteComment']);
    Route::put('/tasks/{taskId}/comments/{commentId}', [TaskController::class, 'updateComment']);
    Route::get('/tasks/list', [TaskController::class, 'tasksByPhase']);

    Route::get('tasks/worker/{workerId}', [TaskController::class, 'showWorkerTasks']);
    Route::post('/tasks/change-worker-status', [TaskController::class, 'changeWorkerStatus']);
    Route::delete('/worker-comment/{commentId}', [TaskController::class, 'deleteWorkerComment']);

    //sick-leaves
    Route::apiResource('sick-leaves', SickLeaveController::class);
    Route::get('/advance-loans', [AdvanceLoanController::class, 'index']);

    //refund-claim
    Route::get('/refund-claims', [RefundClaimController::class, 'index']);
    Route::post('/refund-claims', [RefundClaimController::class, 'store']);
    Route::get('/refund-claims/{id}', [RefundClaimController::class, 'show']);
    Route::post('/refund-claims/{id}', [RefundClaimController::class, 'update']);
    Route::delete('/refund-claims/{id}', [RefundClaimController::class, 'destroy']);

    Route::post('jobs/request-to-change', [ScheduleChangeController::class, 'requestToChange']);
    // Route::get('/schedule-changes', [ScheduleChangeController::class, 'getAllScheduleChanges']);
    Route::put('/schedule-changes/{id}', [ScheduleChangeController::class, 'updateScheduleChange']);

});

Route::post('/twilio/initiate-call', [LeadTwilioController::class, 'initiateCall']);
Route::post('/twilio/handle-call', [LeadTwilioController::class, 'handleCall'])->name('twilio.handleCall');
Route::post('/twilio/handle-language', [LeadTwilioController::class, 'handleLanguage'])->name('twilio.handleLanguage');
Route::post('/twilio/handle-call-flow', [LeadTwilioController::class, 'handleCallFlow'])->name('twilio.handleCallFlow');
Route::post('/twilio/handle-response', [LeadTwilioController::class, 'handleResponse'])->name('twilio.handleResponse');
Route::post('/twilio/main-menu', [LeadTwilioController::class, 'handleResponse'])->name('twilio.mainMenu');


