<?php

use App\Http\Controllers\Admin\ClientController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ClientCardController;
use App\Http\Controllers\Admin\ClientPropertyAddressController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\WorkerController;
use App\Http\Controllers\Admin\WorkerLeadsController;
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
use App\Http\Controllers\Admin\ContractCommentController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\ManpowerCompaniesController;
use App\Http\Controllers\Admin\WorkerAffectedAvailabilitiesController;
use App\Http\Controllers\Admin\WhatsappTemplateController;
use App\Http\Controllers\Api\LeadWebhookController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\Admin\LeadChartsController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\AdvanceLoanController;
use App\Http\Controllers\SickLeaveController;
use App\Http\Controllers\PayrollReportController;
use App\Http\Controllers\RefundClaimController;
use App\Http\Controllers\LeadActivityController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\HearingInvitationController;
use App\Http\Controllers\User\WorkerHearingController;
use App\Http\Controllers\HearingProtocolController;
use App\Http\Controllers\ScheduleChangeController;
use App\Http\Controllers\HearingCommentController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\WhapiController;
// use App\Http\Controllers\Admin\ChangeWorkerController;

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
Route::post('verifyOtp', [AuthController::class, 'verifyOtp']);
Route::post('resendOtp', [AuthController::class, 'resendOtp']);

Route::post('register', [AuthController::class, 'register']);
Route::get('countries', [SettingController::class, 'getCountries']);
Route::get('get_services', [ServicesController::class, 'create']);
Route::any('save-lead', [LeadWebhookController::class, 'saveLead']);

Route::get('clients-sample-file', [ClientController::class, 'sampleFileExport']);
Route::get('workers/import/sample', [WorkerController::class, 'sampleFileExport']);

// Authenticated Routes
Route::group(['middleware' => ['auth:admin-api', 'scopes:admin']], function () {

    // Admin Details Api
    Route::get('details', [AuthController::class, 'details']);

    // Admin Dashboard Api
    Route::get('dashboard', [DashboardController::class, 'dashboard']);
    Route::get('pending-data/{for}', [DashboardController::class, 'pendingData']);
    Route::get('latest-clients', [ClientController::class, 'latestClients']);

    //Authentication Api
    Route::get('/google/auth', [GoogleController::class, 'auth']);
    Route::delete('/google/disconnect', [GoogleController::class, 'disconnect']);

    //Calendar Api
    Route::get('/calendar-list', [GoogleCalendarController::class, 'getGoogleCalendarList']);
    Route::post('/calendar/save', [GoogleCalendarController::class, 'saveCalendar']);


    // Jobs Api
    Route::resource('jobs', JobController::class)->only(['index', 'show']);
    Route::get('get-all-jobs', [JobController::class, 'getAllJob']);
    Route::post('create-job', [JobController::class, 'createJob']);
    Route::post('jobs/{id}/change-worker', [JobController::class, 'changeJobWorker']);
    Route::post('jobs/{id}/change-shift', [JobController::class, 'changeJobShift']);
    Route::get('clients/{id}/jobs', [JobController::class, 'getJobByClient']);
    Route::post('get-worker-jobs', [JobController::class, 'getJobWorker']);
    Route::put('jobs/{id}/cancel', [JobController::class, 'cancelJob']);
    Route::get('job-worker/{id}', [JobController::class, 'AvlWorker']);
    Route::get('shift-change-worker/{sid}/{date}', [JobController::class, 'shiftChangeWorker']);
    Route::resource('job-comments', JobCommentController::class)->only(['index', 'store', 'destroy']);
    Route::post('jobs/{id}/adjust-time', [JobCommentController::class, 'adjustJobCompleteTime']);

    Route::get('jobs/{id}/total-amount-by-group', [JobController::class, 'getOpenJobAmountByGroup']);
    Route::post('worker/{wid}/jobs/{jid}/approve', [JobController::class, 'approveWorkerJob']);
    Route::post('job-opening-timestamp', [JobController::class, 'setJobOpeningTimestamp']);
    Route::post('jobs/start-time', [JobController::class, 'JobStartTime']);

    Route::post('get-job-time', [JobController::class, 'getJobTime']);
    Route::post('add-job-time', [JobController::class, 'addJobTime']);
    Route::post('update-job-time', [JobController::class, 'updateJobTime']);
    Route::delete('delete-job-time/{id}', [JobController::class, 'deleteJobTime']);
    Route::get('jobs/{id}/worker-to-switch', [JobController::class, 'workersToSwitch']);

    Route::get('jobs/{id}', [JobController::class, 'show']);

    Route::post('jobs/{id}/switch-worker', [JobController::class, 'switchWorker']);
    Route::post('jobs/{id}/update-worker-actual-time', [JobController::class, 'updateWorkerActualTime']);
    Route::post('jobs/{id}/update-job-done', [JobController::class, 'updateJobDone']);
    Route::post('jobs/{id}/discount', [JobController::class, 'saveDiscount']);
    Route::post('jobs/{id}/extra-amount', [JobController::class, 'saveExtraAmount']);


    // Lead Api
    Route::resource('leads', LeadController::class)->except(['create', 'show']);
    Route::post('leads/save-property-address', [LeadController::class, 'savePropertyAddress']);
    Route::delete('leads/remove-property-address/{id}', [LeadController::class, 'removePropertyAddress']);

    //  Routes for Lead Activity
    Route::get('/lead-activities/{id}', [LeadActivityController::class, 'getLeadActivities']);
    // Client Property Address Comments
    Route::get('property-addresses/{id}/comments', [ClientPropertyAddressController::class, 'getComments']);
    Route::post('property-addresses/{id}/comments', [ClientPropertyAddressController::class, 'saveComment']);
    Route::delete('property-addresses/{service_id}/comments/{id}', [ClientPropertyAddressController::class, 'deleteComment']);

    // workers Api
    Route::resource('workers', WorkerController::class)->except(['create', 'show']);
    Route::get('all-workers', [WorkerController::class, 'AllWorkers']);
    Route::post('workers/{id}/freeze-shift', [WorkerController::class, 'updateFreezeShift']);
    Route::post('workers/freeze-shift', [WorkerController::class, 'updateFreezeShiftWorkers']);
    Route::get('workers/workers/freeze-shift/{id}', [WorkerController::class, 'getFreezeShiftWorkers']);
    Route::post('workers/{id}/leave-job', [WorkerController::class, 'updateLeaveJob']);
    Route::get('worker_availability/{id}', [WorkerController::class, 'getWorkerAvailability']);
    Route::post('update_availability/{id}', [WorkerController::class, 'updateAvailability']);
    Route::post('form/save', [WorkerController::class, 'formSave']);
    Route::post('present-workers-for-job', [WorkerController::class, 'presentWorkersForJob']);
    Route::get('workers/working-hours', [WorkerController::class, 'workingHoursReport']);
    Route::post('workers/working-hours/export', [WorkerController::class, 'exportWorkingHoursReport']);
    Route::post('workers/working-hours/pdf', [WorkerController::class, 'generateWorkerHoursPDF']);
    Route::post('form/send', [WorkerController::class, 'formSend']);
    Route::post('workers/import', [WorkerController::class, 'import']);

    //worker-leads api
    Route::get('worker-leads', [WorkerLeadsController::class, 'index'])->name('worker-leads.index');
    Route::post('worker-leads/add', [WorkerLeadsController::class, 'store'])->name('worker-leads.store');
    Route::get('worker-leads/{id}/edit', [WorkerLeadsController::class, 'edit'])->name('worker-leads.edit');
    Route::put('worker-leads/{id}', [WorkerLeadsController::class, 'update'])->name('worker-leads.update');
    Route::delete('worker-leads/{id}', [WorkerLeadsController::class, 'destroy'])->name('worker-leads.destroy');
    Route::post('worker-leads/{id}/status', [WorkerLeadsController::class, 'changeStatus'])->name('worker-leads.changeStatus');

    // not Available date
    Route::post('get-not-available-dates', [WorkerController::class, 'getNotAvailableDates']);
    Route::post('add-not-available-date', [WorkerController::class, 'addNotAvailableDates']);
    Route::post('delete-not-available-date', [WorkerController::class, 'deleteNotAvailableDates']);

    // Clients Api
    Route::resource('clients', ClientController::class)->except('create');
    Route::get('all-clients', [ClientController::class, 'AllClients']);
    Route::post('import-clients', [ClientController::class, 'import']);
    Route::post('client-status-log', [ClientController::class, 'clienStatusLog']);
    Route::delete('/client-meta/{clientId}', [ClientController::class, 'deleteClientMetaIfExists']);

    // Client Comments
    Route::get('clients/{id}/comments', [ClientController::class, 'getComments']);
    Route::post('clients/{id}/comments', [ClientController::class, 'saveComment']);
    Route::delete('clients/{client_id}/comments/{id}', [ClientController::class, 'deleteComment']);

    // Services Api
    Route::resource('services', ServicesController::class)->except('show');
    Route::get('all-services', [ServicesController::class, 'AllServices']);
    Route::post('all-services', [ServicesController::class, 'AllServicesByLng']);
    Route::post('add-sub-service/{id}', [ServicesController::class, 'addSubService']);
    Route::delete('remove-sub-service/{id}', [ServicesController::class, 'removeSubService']);
    Route::get('get-sub-services/{id}', [ServicesController::class, 'getSubServices']);
    Route::put('edit-sub-service/{id}', [ServicesController::class, 'editSubService']);

    // Services Comments
    Route::get('services/{id}/comments', [ServicesController::class, 'getComments']);
    Route::post('services/{id}/comments', [ServicesController::class, 'saveComment']);
    Route::delete('services/{service_id}/comments/{id}', [ServicesController::class, 'deleteComment']);

    // Services schedule Api
    Route::resource('service-schedule', ServiceSchedulesController::class)->except(['create', 'show']);
    Route::get('all-service-schedule', [ServiceSchedulesController::class, 'allSchedules']);
    Route::post('all-service-schedule', [ServiceSchedulesController::class, 'allSchedulesByLng']);

    // Offer Api
    Route::resource('offers', OfferController::class)->except('create');
    Route::get('clients/{id}/offers', [OfferController::class, 'ClientOffers']);
    Route::post('latest-client-offer', [OfferController::class, 'getLatestClientOffer']);
    Route::post('offer-reopen/{id}', [OfferController::class, 'reopen']);

    // Contract Api
    Route::resource('contract', ContractController::class)->except(['create', 'store', 'edit', 'update']);
    Route::post('client-contracts', [ContractController::class, 'clientContracts']);
    Route::post('get-contract/{id}', [ContractController::class, 'getContract']);
    Route::post('verify-contract', [ContractController::class, 'verify']);
    Route::get('get-contract-by-client/{id}', [ContractController::class, 'getContractByClient']);
    Route::post('cancel-contract-jobs', [ContractController::class, 'cancelJob']);
    Route::post('contract-file/save', [ContractController::class, 'saveContractFile']);

    // TeamMembers
    Route::resource('teams', TeamMemberController::class)->except(['create', 'show']);
    Route::get('teams/all', [TeamMemberController::class, 'getAll']);
    Route::get('my-availability', [TeamMemberController::class, 'myAvailability']);
    Route::post('my-availability', [TeamMemberController::class, 'updateMyAvailability']);
    Route::get('teams/{id}/availability', [TeamMemberController::class, 'availability']);
    Route::post('teams/{id}/availability', [TeamMemberController::class, 'updateAvailability']);
    Route::get('teams/availability/{id}/date/{date}', [TeamMemberController::class, 'availabilityByDate']);

    // Notes
    Route::post('get-notes', [ClientController::class, 'getNotes']);
    Route::post('add-note', [ClientController::class, 'addNote']);
    Route::post('delete-note', [ClientController::class, 'deleteNote']);

    // Lead Comment
    Route::post('get-comments', [LeadController::class, 'getComments']);
    Route::post('add-comment', [LeadController::class, 'addComment']);
    Route::post('delete-comment', [LeadController::class, 'deleteComment']);

    Route::get('/facebook-campaigns', [LeadController::class, 'getFacebookInsights']);


    // Meeting Schedules
    Route::resource('schedule', ScheduleController::class)->except(['create', 'edit']);
    Route::post('schedule/{id}/create-event', [ScheduleController::class, 'createScheduleCalendarEvent']);
    Route::post('client-schedules', [ScheduleController::class, 'clientSchedules']);
    Route::get('teams/{id}/schedule-events', [ScheduleController::class, 'getTeamEvents']);
    Route::post('latest-client-schedule', [ScheduleController::class, 'latestClientSchedule']);

    // client files
    Route::post('add-file', [ClientController::class, 'addfile']);
    Route::get('clients/{id}/files', [ClientController::class, 'files']);
    Route::post('delete-file', [ClientController::class, 'deletefile']);

    // Report
    Route::post('worker/hours/export', [JobController::class, 'exportTimeReport']);
    Route::post('jobs/{id}/worker/hours/export', [JobController::class, 'exportJobTrackedReport']);

    // Income
    Route::post('income', [DashboardController::class, 'income']);

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
    Route::post('client/{id}/generate-invoice', [InvoiceController::class, 'generateClientInvoices']);
    Route::post('client/{id}/close-without-payment', [InvoiceController::class, 'closeClientWithoutPayment']);

    Route::get('client/{id}/cards', [ClientCardController::class, 'index']);
    Route::post('client/{id}/initialize-card', [ClientCardController::class, 'createClientCardSession']);
    Route::post('client/{id}/check-card-by-session', [ClientCardController::class, 'checkTranxBySessionId']);
    Route::delete('client/{client_id}/cards/{id}', [ClientCardController::class, 'destroy']);
    Route::put('client/{client_id}/cards/{id}/mark-default', [ClientCardController::class, 'markDefault']);

    Route::get('clients_export', [ClientController::class, 'export']);

    Route::get('close-doc/{id}/{type}', [InvoiceController::class, 'closeDoc']);
    Route::post('cancel-doc', [InvoiceController::class, 'cancelDoc']);
    Route::post('refund-doc', [InvoiceController::class, 'refundDoc']);


    Route::get('order-manual-invoice/{id}', [InvoiceController::class, 'manualInvoice']);
    Route::get('client/{id}/invoices', [InvoiceController::class, 'getClientInvoices']);

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
    Route::get('head-notice', [DashboardController::class, 'headNotice']);
    Route::get('notice', [DashboardController::class, 'Notice']);
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
    Route::post('change-bank-details', [SettingController::class, 'changeBankDetails']);

    // Change Password Api
    Route::post('change-password', [SettingController::class, 'changePassword']);

    // Languages
    Route::resource('languages', LanguageController::class);

    // Manpower Companies
    Route::get('manpower-companies-list', [ManpowerCompaniesController::class, 'allCompanies']);
    Route::post('manpower-companies/{id}', [ManpowerCompaniesController::class, 'update']);
    Route::resource('manpower-companies', ManpowerCompaniesController::class)->except(['create', 'show', 'edit', 'update']);

    // Admin Logout Api
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('webhook-responses', [ChatController::class, 'index']);
    Route::get('chats', [ChatController::class, 'chats']);
    Route::get('chat-message/{no}', [ChatController::class, 'chatsMessages']);
    Route::post('chat-message', [ChatController::class, 'storeWebhookResponse']);
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
    Route::post('settings/payment', [SettingController::class, 'updatePaymentRate']);

    //Payslip settings
    Route::get('/settings/get', [SettingsController::class, 'getSettings']);
    Route::post('/settings/save', [SettingsController::class, 'saveSettings']);

    //documents
    Route::get('documents/{id}', [DocumentController::class, 'documents']);
    Route::delete('document/remove/{id}/{user_id}', [DocumentController::class, 'remove']);
    Route::delete('document/remove-admin/{id}/{user_id}', [DocumentController::class, 'adminRemoveDoc']);
    Route::post('document/save', [DocumentController::class, 'save']);
    Route::get('document/admin/{id}', [DocumentController::class, 'adminDocuments']);
    Route::post('document/admin-save', [DocumentController::class, 'AdminDocssave']);
    Route::get('get-doc-types', [DocumentController::class, 'getDocumentTypes']);
    Route::post('document/reset/{form_id}', [DocumentController::class, 'resetForm']);

    Route::get('worker-affected-availability/{id}', [WorkerAffectedAvailabilitiesController::class, 'show']);
    Route::post('worker-affected-availability/{id}/approve', [WorkerAffectedAvailabilitiesController::class, 'approve']);
    Route::post('worker-affected-availability/{id}/reject', [WorkerAffectedAvailabilitiesController::class, 'reject']);

    //termination api -- schedule hearing 
    Route::get('hearing-invitations', [HearingInvitationController::class, 'index']);
    // Route::get('/hearing-invitations/{id}', [HearingInvitationController::class, 'show']);
    Route::post('/hearing-invitations/create', [HearingInvitationController::class, 'store']);
    Route::put('/hearing-invitations/{id}', [HearingInvitationController::class, 'update']);
    Route::post('/hearing-invitations/{id}/create-event', [HearingInvitationController::class, 'createEvent']);

    Route::get('/scheduled-hearings/{id}', [HearingInvitationController::class, 'getScheduledHearings']);
    Route::delete('/hearing/{id}', [HearingInvitationController::class, 'destroy']);

    Route::post('/hearing-protocol', [HearingProtocolController::class, 'store']);
    Route::get('/hearing-protocol/comments', [HearingCommentController::class, 'getComments']);

    Route::post('/claims', [ClaimController::class, 'store']);

    //holidays add or update
    Route::get('holidays', [HolidayController::class, 'index']);
    Route::post('holidays', [HolidayController::class, 'store']);
    Route::post('holidays/{id}', [HolidayController::class, 'update']);
    Route::delete('holidays/{id}', [HolidayController::class, 'destroy']);
    Route::get('holidays/{id}', [HolidayController::class, 'show']);

    //phase and task management
    Route::apiResource('/tasks', TaskController::class);
    Route::apiResource('/phase', PhaseController::class);
    Route::post('tasks/sort', [TaskController::class, 'sort']);
    Route::post('/tasks/{taskId}/comments', [TaskController::class, 'addComment']);
    Route::delete('/comments/{commentId}', [TaskController::class, 'deleteComment']);
    Route::get('/tasks/list', [TaskController::class, 'tasksByPhase']);
    Route::post('/tasks/{task}/move', [TaskController::class, 'moveTaskToPhase']);

    //holidays add or update
    Route::get('holidays', [HolidayController::class, 'index']);
    Route::get('all-holidays', [HolidayController::class, 'getAll']);
    Route::post('holidays', [HolidayController::class, 'store']);
    Route::post('holidays/{id}', [HolidayController::class, 'update']);
    Route::delete('holidays/{id}', [HolidayController::class, 'destroy']);
    Route::get('holidays/{id}', [HolidayController::class, 'show']);

    //sick-leaves approve
    Route::get('sick-leaves/list', [SickLeaveController::class, 'allLeaves']);
    Route::post('sick-leaves/{sick_leave}/approve', [SickLeaveController::class, 'approve']);

    //refund-claims approve
    Route::get('refund-claims/list', [RefundClaimController::class, 'allRequests']);
    Route::post('refund-claims/{refund_claim}/approve', [RefundClaimController::class, 'approve']);

    //advance or loan amount
     Route::get('/advance-loans', [AdvanceLoanController::class, 'index']);
    Route::post('/advance-loans', [AdvanceLoanController::class, 'store']);
    Route::get('/advance-loans/{worker_id}', [AdvanceLoanController::class, 'show']);
    Route::post('/advance-loans/{id}', [AdvanceLoanController::class, 'update']);
    Route::delete('/advance-loans/{id}', [AdvanceLoanController::class, 'destroy']);
    Route::post('/advance-loans/{id}/confirm', [AdvanceLoanController::class, 'confirmUpdate']);

    Route::get('/generate-monthly-report', [PayrollReportController::class, 'generateMonthlyReport']);

    //whatsapp templates routes
    Route::get('/whatsapp-templates', [WhatsappTemplateController::class, 'index']);
    Route::get('/whatsapp-templates/{id}', [WhatsappTemplateController::class, 'show']);
    Route::post('/whatsapp-templates', [WhatsappTemplateController::class, 'store']);
    Route::put('/whatsapp-templates/{id}', [WhatsappTemplateController::class, 'update']);
    Route::delete('/whatsapp-templates/{id}', [WhatsappTemplateController::class, 'destroy']);
    Route::post('custom-message-send', [WhatsappTemplateController::class, 'customMessageSend']);

    Route::get('/schedule-changes', [ScheduleChangeController::class, 'index'])->name('schedule-changes.index');
    Route::put('/schedule-changes/{id}', [ScheduleChangeController::class, 'updateScheduleChange']);
    Route::get('/schedule-change/{id}', [ScheduleChangeController::class, 'getScheduleChange']);


    // Route::get('jobs/change-worker-requests', [ChangeWorkerController::class, 'index']);
    // Route::post('jobs/change-worker-requests/{id}/accept', [ChangeWorkerController::class, 'accept']);
    // Route::post('jobs/change-worker-requests/{id}/reject', [ChangeWorkerController::class, 'reject']);

    //whapi routes
    Route::get('/get-all-chats', [WhapiController::class, 'getAllChats']);
    Route::get('/get-chat/{chatId}', [WhapiController::class, 'getChatById']);
    Route::get('/get-conversations/{chatId}', [WhapiController::class, 'getConversationsByNumber']);
    Route::delete('/delete-message/{messageId}', [WhapiController::class, 'deleteMessage']);


});



Route::post('/hearing', [WorkerHearingController::class, 'getHearingDetails']);
Route::post('/accept-hearing', [WorkerHearingController::class, 'acceptHearing']);
Route::post('/reject-hearing', [WorkerHearingController::class, 'rejectHearing']);
Route::post('/hearing/{id}/reschedule', [WorkerHearingController::class, 'rescheduleHearing']);

// Route::get('/lead-charts', [LeadChartsController::class, 'lineGraphData']);

Route::get('/facebook/campaigns', [LeadChartsController::class, 'index']);
Route::get('/facebook/campaigns/{id}/cost', [LeadChartsController::class, 'cost']);
// Route::get('/facebook/campaign-cost', [LeadChartsController::class, 'getCampaignCost'])->name('facebook.api.campaign.cost');

Route::get('/lead-charts', [LeadChartsController::class, 'lineGraphData']);
Route::get('/facebook/campaigns', [LeadChartsController::class, 'index']);
Route::get('/facebook/campaigns/{id}/cost', [LeadChartsController::class, 'cost']);
