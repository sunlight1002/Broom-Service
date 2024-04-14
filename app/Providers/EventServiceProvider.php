<?php

namespace App\Providers;

use App\Events\JobWorkerChanged;
use App\Events\WorkerApprovedJob;
use App\Events\WorkerNotApprovedJob;
use App\Events\WorkerUpdatedJobStatus;
use App\Listeners\SendJobApprovedNotification;
use App\Listeners\SendJobNotApprovedNotification;
use App\Listeners\SendWorkerChangedNotification;
use App\Listeners\SendWorkerUpdatedJobStatusNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\WhatsappNotificationEvent' => [
            'App\Listeners\WhatsappNotification'
        ],
        JobWorkerChanged::class => [
            SendWorkerChangedNotification::class,
        ],
        WorkerApprovedJob::class => [
            SendJobApprovedNotification::class,
        ],
        WorkerNotApprovedJob::class => [
            SendJobNotApprovedNotification::class,
        ],
        WorkerUpdatedJobStatus::class => [
            SendWorkerUpdatedJobStatusNotification::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
