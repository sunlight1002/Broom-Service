<?php

use App\Http\Controllers\Admin\ClientController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\ChangeWorkerController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ClientCardController;
use App\Http\Controllers\Admin\ClientPropertyAddressController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\WorkerController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\JobCommentController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ServicesController;
use App\Http\Controllers\Admin\ServiceSchedulesController;
use App\Http\Controllers\Admin\OfferController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Api\LeadWebhookController;
use App\Http\Controllers\DocumentController;

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

// Unauthenticated Routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::get('countries', [SettingController::class, 'getCountries']);
Route::get('get_services', [ServicesController::class, 'create']);
Route::any('save-lead', [LeadWebhookController::class, 'saveLead']);

Route::get('clients-sample-file', [ClientController::class, 'sampleFileExport']);

// Authenticated Routes
Route::group(['middleware' => ['auth:admin-api', 'scopes:admin']], function () {

    // Admin Details Api
    Route::get('details', [AuthController::class, 'details']);

    // Admin Dashboard Api
    Route::get('dashboard', [DashboardController::class, 'dashboard']);
    Route::get('pending-data/{for}', [DashboardController::class, 'pendingData']);
    Route::get('latest-clients', [ClientController::class, 'latestClients']);

    Route::get('jobs/change-worker-requests', [ChangeWorkerController::class, 'index']);
    Route::post('jobs/change-worker-requests/{id}/accept', [ChangeWorkerController::class, 'accept']);
    Route::post('jobs/change-worker-requests/{id}/reject', [ChangeWorkerController::class, 'reject']);

    // Jobs Api
    Route::resource('jobs', JobController::class)->only(['index', 'show']);
    Route::get('get-all-jobs', [JobController::class, 'getAllJob']);
    Route::post('create-job', [JobController::class, 'createJob']);
    Route::post('jobs/{id}/change-worker', [JobController::class, 'changeJobWorker']);
    Route::post('jobs/{id}/change-shift', [JobController::class, 'changeJobShift']);
    Route::post('clients/{id}/jobs', [JobController::class, 'getJobByClient']);
    Route::post('get-worker-jobs', [JobController::class, 'getJobWorker']);
    Route::put('jobs/{id}/cancel', [JobController::class, 'cancelJob']);
    Route::get('job-worker/{id}', [JobController::class, 'AvlWorker']);
    Route::get('shift-change-worker/{sid}/{date}', [JobController::class, 'shiftChangeWorker']);
    Route::resource('job-comments', JobCommentController::class)->only(['index', 'store', 'destroy']);

    Route::post('get-job-time', [JobController::class, 'getJobTime']);
    Route::post('add-job-time', [JobController::class, 'addJobTime']);
    Route::post('update-job-time', [JobController::class, 'updateJobTime']);
    Route::delete('delete-job-time/{id}', [JobController::class, 'deleteJobTime']);
    Route::get('jobs/{id}/worker-to-switch', [JobController::class, 'workersToSwitch']);
    Route::post('jobs/{id}/switch-worker', [JobController::class, 'switchWorker']);
    Route::post('jobs/{id}/update-worker-actual-time', [JobController::class, 'updateWorkerActualTime']);
    Route::post('jobs/{id}/update-job-done', [JobController::class, 'updateJobDone']);

    // Lead Api
    Route::resource('leads', LeadController::class)->except(['create', 'show']);
    Route::post('leads/save-property-address', [LeadController::class, 'savePropertyAddress']);
    Route::delete('leads/remove-property-address/{id}', [LeadController::class, 'removePropertyAddress']);

    // Client Property Address Comments
    Route::get('property-addresses/{id}/comments', [ClientPropertyAddressController::class, 'getComments']);
    Route::post('property-addresses/{id}/comments', [ClientPropertyAddressController::class, 'saveComment']);
    Route::delete('property-addresses/{service_id}/comments/{id}', [ClientPropertyAddressController::class, 'deleteComment']);

    // workers Api
    Route::resource('workers', WorkerController::class)->except(['create', 'show']);
    Route::get('all-workers', [WorkerController::class, 'AllWorkers']);
    Route::post('workers/{id}/freeze-shift', [WorkerController::class, 'updateFreezeShift']);
    Route::post('workers/freeze-shift', [WorkerController::class, 'updateFreezeShiftWorkers']);
    Route::get('workers/freeze-shift/{id}', [WorkerController::class, 'getFreezeShiftWorkers']);
    Route::post('workers/{id}/leave-job', [WorkerController::class, 'updateLeaveJob']);
    Route::get('worker_availability/{id}', [WorkerController::class, 'getWorkerAvailability']);
    Route::post('update_availability/{id}', [WorkerController::class, 'updateAvailability']);
    // Route::post('upload/{id}', [WorkerController::class, 'upload']);
    Route::post('present-workers-for-job', [WorkerController::class, 'presentWorkersForJob']);

    // not Available date
    Route::post('get-not-available-dates', [WorkerController::class, 'getNotAvailableDates']);
    Route::post('add-not-available-date', [WorkerController::class, 'addNotAvailableDates']);
    Route::post('delete-not-available-date', [WorkerController::class, 'deleteNotAvailableDates']);

    // Clients Api
    Route::resource('clients', ClientController::class)->except('create');
    Route::get('all-clients', [ClientController::class, 'AllClients']);
    Route::post('import-clients', [ClientController::class, 'import']);

    // Client Comments
    Route::get('clients/{id}/comments', [ClientController::class, 'getComments']);
    Route::post('clients/{id}/comments', [ClientController::class, 'saveComment']);
    Route::delete('clients/{client_id}/comments/{id}', [ClientController::class, 'deleteComment']);

    // Services Api
    Route::resource('services', ServicesController::class)->except('show');
    Route::get('all-services', [ServicesController::class, 'AllServices']);
    Route::post('all-services', [ServicesController::class, 'AllServicesByLng']);

    // Services Comments
    Route::get('services/{id}/comments', [ServicesController::class, 'getComments']);
    Route::post('services/{id}/comments', [ServicesController::class, 'saveComment']);
    Route::delete('services/{service_id}/comments/{id}', [ServicesController::class, 'deleteComment']);

    // Services schedule Api
    Route::resource('service-schedule', ServiceSchedulesController::class)->except(['create', 'show']);
    Route::get('all-service-schedule', [ServiceSchedulesController::class, 'allSchedules'])->name('all-service-schedule');
    Route::post('all-service-schedule', [ServiceSchedulesController::class, 'allSchedulesByLng'])->name('all-service-schedule');

    // Offer Api
    Route::resource('offers', OfferController::class)->except('create');
    Route::post('client-offers', [OfferController::class, 'ClientOffers'])->name('client-offers');
    Route::post('latest-client-offer', [OfferController::class, 'getLatestClientOffer']);

    // Contract Api
    Route::resource('contract', ContractController::class)->except(['create', 'store', 'edit', 'update']);
    Route::post('client-contracts', [ContractController::class, 'clientContracts'])->name('client-contracts');
    Route::post('get-contract/{id}', [ContractController::class, 'getContract']);
    Route::post('verify-contract', [ContractController::class, 'verify']);
    Route::get('get-contract-by-client/{id}', [ContractController::class, 'getContractByClient']);
    Route::post('cancel-contract-jobs', [ContractController::class, 'cancelJob']);
    Route::post('contract-file/save', [ContractController::class, 'saveContractFile']);

    // TeamMembers
    Route::resource('teams', TeamMemberController::class)->except(['create', 'show']);
    Route::post('teams/{id}/availability', [TeamMemberController::class, 'updateAvailability']);
    Route::get('teams/availability/{id}', [TeamMemberController::class, 'availability']);
    Route::get('teams/availability/{id}/date/{date}', [TeamMemberController::class, 'availabilityByDate']);

    // Notes
    Route::post('get-notes', [ClientController::class, 'getNotes']);
    Route::post('add-note', [ClientController::class, 'addNote']);
    Route::post('delete-note', [ClientController::class, 'deleteNote']);

    // Lead Comment
    Route::post('get-comments', [LeadController::class, 'getComments']);
    Route::post('add-comment', [LeadController::class, 'addComment']);
    Route::post('delete-comment', [LeadController::class, 'deleteComment']);

    // Meeting Schedules
    Route::resource('schedule', ScheduleController::class)->except(['create', 'edit']);
    Route::post('schedule/{id}/create-event', [ScheduleController::class, 'createScheduleCalendarEvent']);
    Route::post('client-schedules', [ScheduleController::class, 'clientSchedules']);
    Route::get('teams/{id}/schedule-events', [ScheduleController::class, 'getTeamEvents']);
    Route::post('latest-client-schedule', [ScheduleController::class, 'latestClientSchedule']);

    // client files
    Route::post('add-file', [ClientController::class, 'addfile'])->name('add-file');
    Route::get('clients/{id}/files', [ClientController::class, 'files']);
    Route::post('delete-file', [ClientController::class, 'deletefile'])->name('delete-file');

    // Report
    Route::post('export_report', [JobController::class, 'exportReport'])->name('export_report');

    // Income 
    Route::post('income', [DashboardController::class, 'income'])->name('income');

    // Invoice
    // Route::post('add-invoice', [InvoiceController::class, 'AddInvoice']);
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('get-invoice/{id}', [InvoiceController::class, 'getInvoice']);
    Route::post('update-invoice/{id}', [InvoiceController::class, 'updateInvoice']);
    Route::post('invoice-jobs', [InvoiceController::class, 'invoiceJobs']);
    Route::post('invoice-jobs-order', [InvoiceController::class, 'invoiceJobOrder']);
    Route::post('order-jobs', [InvoiceController::class, 'orderJobs']);
    Route::get('payments', [InvoiceController::class, 'payments']);
    Route::get('client/{id}/unpaid-invoice', [InvoiceController::class, 'clientUnpaidInvoice']);
    Route::post('client/{id}/update-invoice', [InvoiceController::class, 'closeClientInvoicesWithReceipt']);
    Route::post('client/{id}/close-for-payment', [InvoiceController::class, 'closeClientForPayment']);

    Route::get('client/{id}/cards', [ClientCardController::class, 'index']);
    Route::post('client/{id}/initialize-card', [ClientCardController::class, 'createClientCardSession']);
    Route::post('client/{id}/check-card-by-session', [ClientCardController::class, 'checkTranxBySessionId']);
    Route::delete('client/{client_id}/cards/{id}', [ClientCardController::class, 'destroy']);
    Route::put('client/{client_id}/cards/{id}/mark-default', [ClientCardController::class, 'markDefault']);

    Route::get('clients_export', [ClientController::class, 'export']);

    Route::get('close-doc/{id}/{type}', [InvoiceController::class, 'closeDoc']);
    Route::post('cancel-doc', [InvoiceController::class, 'cancelDoc']);

    Route::get('order-manual-invoice/{id}', [InvoiceController::class, 'manualInvoice']);
    Route::get('client-invoices/{id}', [InvoiceController::class, 'getClientInvoices']);

    Route::get('client-payments', [InvoiceController::class, 'paymentClientWise']);
    Route::get('client-payments/{id}', [InvoiceController::class, 'clientPayments']);

    // Orders
    Route::get('orders', [InvoiceController::class, 'getOrders']);
    Route::get('client-orders/{id}', [InvoiceController::class, 'getClientOrders']);
    Route::post('get-codes-order', [InvoiceController::class, 'getCodesOrders']);
    Route::post('create-order', [InvoiceController::class, 'createOrder']);

    // ManualInvoice
    Route::get('client-invoice-job', [InvoiceController::class, 'getClientInvoiceJob']);
    Route::post('clients/{id}/invorders', [InvoiceController::class, 'clientInvoiceOrders']);

    // Multiple Orders
    Route::post('multiple-orders', [InvoiceController::class, 'multipleOrders']);
    Route::post('multiple-invoices', [InvoiceController::class, 'multipleInvoices']);

    // Notifications
    Route::get('head-notice', [DashboardController::class, 'headNotice'])->name('head-notice');
    Route::post('notice', [DashboardController::class, 'Notice'])->name('notice');
    Route::post('seen', [DashboardController::class, 'seen'])->name('seen');
    Route::post('clear-notices', [DashboardController::class, 'clearNotices'])->name('clear-notices');

    // View Password
    Route::post('viewpass', [DashboardController::class, 'viewPass']);

    // ManageTime
    Route::post('update-time', [DashboardController::class, 'updateTime'])->name('update-time');
    Route::get('get-time', [DashboardController::class, 'getTime'])->name('get-time');

    // My Account Api
    Route::get('my-account', [SettingController::class, 'getAccountDetails']);
    Route::post('my-account', [SettingController::class, 'saveAccountDetails']);

    // Change Password Api
    Route::post('change-password', [SettingController::class, 'changePassword']);

    // Languages
    Route::resource('languages', LanguageController::class);

    // Admin Logout Api
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('chats', [ChatController::class, 'chats']);
    Route::get('chat-message/{no}', [ChatController::class, 'chatsMessages']);
    Route::post('chat-reply', [ChatController::class, 'chatReply']);
    Route::post('save-response', [ChatController::class, 'saveResponse']);
    Route::get('chat-responses', [ChatController::class, 'chatResponses']);
    Route::post('chat-restart', [ChatController::class, 'chatRestart']);
    Route::get('chat-search', [ChatController::class, 'search'])->name('chat-search');
    Route::post('delete-conversation', [ChatController::class, 'deleteConversation']);

    Route::get('messenger-participants', [ChatController::class, 'participants']);
    Route::get('messenger-message/{id}', [ChatController::class, 'messengerMessage']);
    Route::post('messenger-reply', [ChatController::class, 'messengerReply']);

    // settings
    Route::get('settings', [SettingController::class, 'allSettings']);
    Route::post('settings', [SettingController::class, 'updateSettings']);

    //documents
    Route::get('documents/{id}', [DocumentController::class, 'documents']);
    Route::delete('document/remove/{id}/{user_id}', [DocumentController::class, 'remove']);
    Route::post('document/save', [DocumentController::class, 'save']);
    Route::get('get-doc-types', [DocumentController::class, 'getDocumentTypes']);
});
