<?php

use App\Http\Controllers\Admin\ChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ExpanseController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\User\Auth\AuthController;
use App\Http\Controllers\Api\LeadWebhookController;
use App\Http\Controllers\Api\WorkerLeadWebhookController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\iCountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Webhook\TwilioController;
use App\Models\ShortUrl;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/zcredit/callback', [PaymentController::class, 'callback']);
Route::post('/icount/webhook', [iCountController::class, 'webhook']);

Route::get('/google/callback', [GoogleController::class, 'callback']);
Route::post('/wallybox/callback', [ExpanseController::class, 'wallyboxCallback']);


Route::any('/webhook_fb', [LeadWebhookController::class, 'fbWebhookCurrentLive'])->name('webhook_fb');
Route::any('/webhook_active_clients', [LeadWebhookController::class, 'fbActiveClientsWebhookCurrentLive'])->name('webhook_active_clients');
Route::any('/webhook_active_client_monday', [LeadWebhookController::class, 'activeClientsMonday'])->name('webhook_active_client_monday');
Route::any('/webhook_client_review', [LeadWebhookController::class, 'clientReview'])->name('webhook_client_review');
Route::any('/webhook_active_wednesday', [LeadWebhookController::class, 'activeClientsWednesday'])->name('webhook_active_wednesday');
Route::any('/webhook_active_workers', [WorkerLeadWebhookController::class, 'fbActiveWorkersWebhookCurrentLive'])->name('webhook_active_workers');
Route::any('/webhook_worker_lead', [WorkerLeadWebhookController::class, 'fbWebhookCurrentLive'])->name('webhook_worker_lead');
Route::any('/webhook_active_worker_monday', [WorkerLeadWebhookController::class, 'activeWorkersMonday'])->name('webhook_active_worker_monday');

Route::any('/twilio/worker-lead-webhook', [WorkerLeadWebhookController::class, 'fbWebhookCurrentLive']);
Route::any('/twilio/webhook', [LeadWebhookController::class, 'fbWebhookCurrentLive']);

Route::any('/whapi-group', [LeadWebhookController::class, 'whapiGroup']);
Route::any('/twilio/status-callback', [LeadWebhookController::class, 'statusCallback']);

Route::any('/twilio/voice/webhook', [TwilioController::class, 'webhook']);
Route::any('/facebook/webhook', [LeadController::class, 'facebookWebhook']);

Route::get('/view-invoice/{id}', [InvoiceController::class, 'viewInvoice']);
Route::get('/thanks/{id}', [InvoiceController::class, 'displayThanks']);
Route::get('ads-leads', [LeadController::class, 'fbAdsLead'])->name('adsLead');
Route::get('response-import', [ChatController::class, 'responseImport']);
Route::post('/newlead', [LeadWebhookController::class, 'saveLeadFromContactForm']);


Route::domain(config('services.short_url.domain'))->group(function () {
    // This route handles valid tokens.
    Route::get('/{token}', function ($token) {
        $shortUrl = ShortUrl::where('token', $token)->first();

        if ($shortUrl) {
            return redirect($shortUrl->url);
        }

        // If the token is not found, redirect to broomservice.
        return redirect('https://crm.broomservice.co.il/', 302);
    })
    // Optionally, if your tokens follow a specific pattern you can add a constraint.
    ->where('token', '[A-Za-z0-9]+');

    // This fallback route catches any other URL (e.g. '/', '/foo/bar', etc.)
    Route::fallback(function () {
         return redirect('https://crm.broomservice.co.il/', 302);
    });
});

// Route::domain(config("services.short_url.domain"))->group(function () {
//     Route::get('/c/{token}', function ($token) {
//         $shortUrl = ShortUrl::where('token', $token)->first();

//         if ($shortUrl) {
//             return redirect($shortUrl->url);
//         }

//         return abort(404);
//     });
// });

// Route::domain(config("services.short_url.domain"))->group(function () {
// Route::get('/a/{token}', function ($token) {
//     $shortUrl = ShortUrl::where('token', $token)->first();

//     if ($shortUrl) {
//         return redirect($shortUrl->url);
//     }

//     return abort(404);
// });
// });


// Route::domain(config("services.short_url.domain"))->group(function () {
// Route::get('/w/{token}', function ($token) {
//     $shortUrl = ShortUrl::where('token', $token)->first();

//     if ($shortUrl) {
//         return redirect($shortUrl->url);
//     }

//     return abort(404);
// });
// });


// Route::get('/brmsrvc.c/{token}', function ($token) {
//     $shortUrl = ShortUrl::where('token', $token)->first();

//     if ($shortUrl) {
//         return redirect($shortUrl->url);
//     }

//     return abort(404);
// });

// Route::get('/{token}', function ($token) {
//     $shortUrl = ShortUrl::where('token', $token)->first();

//     if ($shortUrl) {
//         return redirect($shortUrl->url);
//     }

//     return abort(404);
// });



// Auth::routes();
Route::any('/{path?}', function () {
    return view('index');
})->where('path', '.*');

Route::any('/login', function () {
    return view('index');
})->where('path', '.*');

Route::any('/vendor/login', function () {
    return view('index');
})->where('path', '.*');

Route::any('/register', function () {
    return view('index');
})->where('path', '.*');
