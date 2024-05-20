<?php

namespace App\Providers;

use App\Events\AdminLeadFilesNotificationJob;
use App\Events\AdminReScheduleMettingJob;
use App\Events\ClientPaymentFailed;
use App\Events\ContractFormSigned;
use App\Events\Form101Signed;
use App\Events\InsuranceFormSigned;
use App\Events\JobReviewRequest;
use App\Events\JobShiftChanged;
use App\Events\JobWorkerChanged;
use App\Events\ReScheduleMettingJob;
use App\Events\SafetyAndGearFormSigned;
use App\Events\WorkerApprovedJob;
use App\Events\WorkerCreated;
use App\Events\WorkerNotApprovedJob;
use App\Events\WorkerUpdatedJobStatus;
use App\Events\JobNotificationToAdmin;
use App\Events\JobNotificationToWorker;
use App\Events\JobNotificationToClient;
use App\Events\MeetingReminderEvent;
use App\Listeners\AdminLeadFilesNotification;
use App\Listeners\AdminReScheduleMettingNotification;
use App\Listeners\NotifyForClientPaymentFailed;
use App\Listeners\NotifyForContractFormSigned;
use App\Listeners\NotifyForForm101Signed;
use App\Listeners\NotifyForInsuranceFormSigned;
use App\Listeners\NotifyForSafetyAndGearFormSigned;
use App\Listeners\ReScheduleMettingNotification;
use App\Listeners\SendJobApprovedNotification;
use App\Listeners\SendJobNotApprovedNotification;
use App\Listeners\SendJobReviewRequestNotification;
use App\Listeners\SendShiftChangedNotification;
use App\Listeners\SendWorkerChangedNotification;
use App\Listeners\SendWorkerFormsNotification;
use App\Listeners\SendWorkerUpdatedJobStatusNotification;
use App\Listeners\SendJobNotificationToAdmin;
use App\Listeners\SendJobNotificationToWorker;
use App\Listeners\SendJobNotificationToClient;
use App\Listeners\MeetingReminderNotification;
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
        ],
        JobShiftChanged::class => [
            SendShiftChangedNotification::class
        ],
        WorkerCreated::class => [
            SendWorkerFormsNotification::class
        ],
        ReScheduleMettingJob::class => [
            ReScheduleMettingNotification::class,
        ],
        AdminReScheduleMettingJob::class => [
            AdminReScheduleMettingNotification::class,
        ],
        AdminLeadFilesNotificationJob::class => [
            AdminLeadFilesNotification::class,
        ],
        Form101Signed::class => [
            NotifyForForm101Signed::class
        ],
        SafetyAndGearFormSigned::class => [
            NotifyForSafetyAndGearFormSigned::class
        ],
        ContractFormSigned::class => [
            NotifyForContractFormSigned::class
        ],
        InsuranceFormSigned::class => [
            NotifyForInsuranceFormSigned::class
        ],
        JobReviewRequest::class => [
            SendJobReviewRequestNotification::class
        ],
        JobNotificationToAdmin::class => [
            SendJobNotificationToAdmin::class
        ],
        ClientPaymentFailed::class => [
            NotifyForClientPaymentFailed::class
        ],
        JobNotificationToWorker::class => [
            SendJobNotificationToWorker::class
        ],
        JobNotificationToClient::class => [
            SendJobNotificationToClient::class
        ],
        MeetingReminderEvent::class => [
            MeetingReminderNotification::class
        ],
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
