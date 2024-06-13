<?php

namespace App\Providers;

use App\Events\AdminCommented;
use App\Events\AdminLeadFilesNotificationJob;
use App\Events\AdminReScheduleMeetingJob;
use App\Events\ClientCommented;
use App\Events\ClientInvoiceCreated;
use App\Events\ClientInvRecCreated;
use App\Events\ClientLeadStatusChanged;
use App\Events\ClientOrderCancelled;
use App\Events\ClientOrderWithExtraOrDiscount;
use App\Events\ClientPaymentFailed;
use App\Events\ClientPaymentPaid;
use App\Events\ClientPaymentPartiallyPaid;
use App\Events\ClientReviewed;
use App\Events\ContractFormSigned;
use App\Events\ContractSigned;
use App\Events\Form101Signed;
use App\Events\InsuranceFormSigned;
use App\Events\JobReviewRequest;
use App\Events\JobShiftChanged;
use App\Events\JobWorkerChanged;
use App\Events\ReScheduleMeetingJob;
use App\Events\SafetyAndGearFormSigned;
use App\Events\WorkerApprovedJob;
use App\Events\WorkerCreated;
use App\Events\WorkerNotApprovedJob;
use App\Events\WorkerUpdatedJobStatus;
use App\Events\JobNotificationToAdmin;
use App\Events\JobNotificationToWorker;
use App\Events\JobNotificationToClient;
use App\Events\MeetingReminderEvent;
use App\Events\NewLeadArrived;
use App\Events\OfferAccepted;
use App\Events\OfferSaved;
use App\Events\WorkerChangeAffectedAvailability;
use App\Events\WorkerCommented;
use App\Events\WorkerForm101Requested;
use App\Events\WorkerLeaveJob;
use App\Listeners\AdminLeadFilesNotification;
use App\Listeners\AdminReScheduleMeetingNotification;
use App\Listeners\NotifyForClientPaymentFailed;
use App\Listeners\NotifyForContractFormSigned;
use App\Listeners\NotifyForForm101Signed;
use App\Listeners\NotifyForInsuranceFormSigned;
use App\Listeners\NotifyForSafetyAndGearFormSigned;
use App\Listeners\ReScheduleMeetingNotification;
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
use App\Listeners\NotifyForAdminCommented;
use App\Listeners\NotifyForClientCommented;
use App\Listeners\NotifyForClientInvoice;
use App\Listeners\NotifyForClientInvRec;
use App\Listeners\NotifyForClientOrderWithExtraOrDiscount;
use App\Listeners\NotifyForClientPaymentPaid;
use App\Listeners\NotifyForClientPaymentPartiallyPaid;
use App\Listeners\NotifyForClientReviewed;
use App\Listeners\NotifyForContract;
use App\Listeners\NotifyForLeadStatusChanged;
use App\Listeners\NotifyForNewLead;
use App\Listeners\NotifyForOffer;
use App\Listeners\NotifyForOrderCancelled;
use App\Listeners\NotifyForWorkerCommented;
use App\Listeners\NotifyForWorkerLeave;
use App\Listeners\SendClientCredentials;
use App\Listeners\SendWorkerChangedAffectedAvailability;
use App\Listeners\SendWorkerForm101Notification;
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
        WorkerForm101Requested::class => [
            SendWorkerForm101Notification::class
        ],
        ReScheduleMeetingJob::class => [
            ReScheduleMeetingNotification::class,
        ],
        AdminReScheduleMeetingJob::class => [
            AdminReScheduleMeetingNotification::class,
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
        WorkerChangeAffectedAvailability::class => [
            SendWorkerChangedAffectedAvailability::class
        ],
        OfferSaved::class => [
            NotifyForOffer::class
        ],
        OfferAccepted::class => [
            NotifyForContract::class
        ],
        ContractSigned::class => [
            SendClientCredentials::class
        ],
        ClientReviewed::class => [
            NotifyForClientReviewed::class
        ],
        ClientCommented::class => [
            NotifyForClientCommented::class
        ],
        AdminCommented::class => [
            NotifyForAdminCommented::class
        ],
        WorkerCommented::class => [
            NotifyForWorkerCommented::class
        ],
        NewLeadArrived::class => [
            NotifyForNewLead::class
        ],
        ClientLeadStatusChanged::class => [
            NotifyForLeadStatusChanged::class
        ],
        WorkerLeaveJob::class => [
            NotifyForWorkerLeave::class
        ],
        ClientOrderCancelled::class => [
            NotifyForOrderCancelled::class
        ],
        ClientPaymentPaid::class => [
            NotifyForClientPaymentPaid::class
        ],
        ClientPaymentPartiallyPaid::class => [
            NotifyForClientPaymentPartiallyPaid::class
        ],
        ClientInvoiceCreated::class => [
            NotifyForClientInvoice::class
        ],
        ClientInvRecCreated::class => [
            NotifyForClientInvRec::class
        ],
        ClientOrderWithExtraOrDiscount::class => [
            NotifyForClientOrderWithExtraOrDiscount::class
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
