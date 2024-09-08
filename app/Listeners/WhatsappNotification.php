<?php

namespace App\Listeners;

use App\Events\WhatsappNotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Enums\WhatsappMessageTemplateEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappNotification
{
    protected $whapiApiEndpoint, $whapiApiToken;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\WhatsappNotificationEvent  $event
     * @return void
     */
    public function handle(WhatsappNotificationEvent $event)
    {
        try {
            $eventData = $event;
            $eventType = $eventData->type;
            $eventData = $eventData->notificationData;

            $headers = array();
            $url = "https://graph.facebook.com/v18.0/" . config('services.whatsapp_api.from_id') . "/messages";
            $headers[] = 'Authorization: Bearer ' . config('services.whatsapp_api.auth_token');
            $headers[] = 'Content-Type: application/json';

            $receiverNumber = NULL;
            $text = NULL;
            switch ($eventType) {
                case WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE:
                    $clientData = $eventData['client'];

                    $receiverNumber = $clientData['phone'];
                    App::setLocale($clientData['lng']);

                    $propertyAddress = $eventData['property_address'];
                    if ($eventData['purpose'] == "Price offer") {
                        $eventData['purpose'] = trans('mail.meeting.price_offer');
                    } else if ($eventData['purpose'] == "Quality check") {
                        $eventData['purpose'] = trans('mail.meeting.quality_check');
                    } else {
                        $eventData['purpose'] = $eventData['purpose'];
                    }

                    $address = isset($propertyAddress) && isset($propertyAddress['address_name']) && !empty($propertyAddress['address_name']) ? $propertyAddress['address_name'] : "NA";

                    $text = __('mail.wa-message.client_meeting_schedule.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $clientData['firstname'] . ' ' . $clientData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_meeting_schedule.content', [
                        'date'          => Carbon::parse($eventData['start_date'])->format('d-m-Y'),
                        'start_time'    => date("H:i", strtotime($eventData['start_time'])),
                        'end_time'      => date("H:i", strtotime($eventData['end_time'])),
                        'address'       => $address,
                        'purpose'       => $eventData['purpose'] ? $eventData['purpose'] : " "
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.accept_reject') . ": " . url("meeting-schedule/" . base64_encode($eventData['id']));

                    $text .= "\n\n" . __('mail.wa-message.button-label.upload_file') . ": " . url("meeting-files/" . base64_encode($eventData['id']));

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER:
                    $receiverNumber = $eventData['phone'];
                    App::setLocale($eventData['lng']);

                    $propertyAddress = $eventData['property_address'];
                    if ($eventData['purpose'] == "Price offer") {
                        $eventData['purpose'] =  trans('mail.meeting.price_offer');
                    } else if ($eventData['purpose'] == "Quality check") {
                        $eventData['purpose'] =  trans('mail.meeting.quality_check');
                    } else {
                        $eventData['purpose'] = $eventData['purpose'];
                    }

                    $address = isset($propertyAddress) && isset($propertyAddress['address_name']) && !empty($propertyAddress['address_name']) ? $propertyAddress['address_name'] : "NA";

                    $text = __('mail.wa-message.client_meeting_reminder.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $eventData['firstname'] . ' ' . $eventData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_meeting_schedule.content', [
                        'date'          => Carbon::parse($eventData['start_date'])->format('d-m-Y'),
                        'start_time'    => date("H:i", strtotime($eventData['start_time'])),
                        'end_time'      => date("H:i", strtotime($eventData['end_time'])),
                        'address'       => $address,
                        'purpose'       => $eventData['purpose'] ? $eventData['purpose'] : " "
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.accept_reject') . ": " . url("meeting-schedule/" . base64_encode($eventData['id']));

                    $text .= "\n\n" . __('mail.wa-message.button-label.upload_file') . ": " . url("meeting-files/" . base64_encode($eventData['id']));

                    break;

                case WhatsappMessageTemplateEnum::OFFER_PRICE:
                    $clientData = $eventData['client'];

                    $receiverNumber = $clientData['phone'];
                    App::setLocale($clientData['lng']);

                    $text = __('mail.wa-message.offer_price.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $clientData['firstname'] . ' ' . $clientData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.offer_price.content', [
                        'service_names' => isset($eventData['service_names'])
                            ? $eventData['service_names']
                            : ' '
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.price_offer') . ": " . url("price-offer/" . base64_encode($eventData['id']));

                    break;
                case WhatsappMessageTemplateEnum::CONTRACT:
                    $clientData = $eventData['client'];

                    $receiverNumber = $clientData['phone'];
                    App::setLocale($clientData['lng']);

                    $text = __('mail.wa-message.contract.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $clientData['firstname'] . ' ' . $clientData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.contract.content');

                    $text .= "\n\n" . __('mail.wa-message.button-label.check_contract') . ": " . url("work-contract/" . $eventData['contract_id']);

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED:
                    $jobData = $eventData['job'];
                    $clientData = $jobData['client'];

                    $receiverNumber = $clientData['phone'];

                    App::setLocale($clientData['lng']);

                    $text = __('mail.wa-message.client_job_updated.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $clientData['firstname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_job_updated.content', [
                        'date' => Carbon::parse($jobData['start_date'])->format('M d Y'),
                        'service_name' => $clientData['lng'] == 'heb'
                            ? $jobData['jobservice']['heb_name']
                            : $jobData['jobservice']['name'],
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.review') . ": " . url("client/jobs/" . base64_encode($jobData['id']) . "/review");

                    break;

                case WhatsappMessageTemplateEnum::CREATE_JOB:

                    $jobData = $eventData['job'];
                    $clientData = $jobData['client'];

                    $receiverNumber = $clientData['phone'];
                    App::setLocale($clientData['lng']);

                    $text = __('mail.wa-message.create_job.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $clientData['firstname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.create_job.content', [
                        'date' => Carbon::parse($jobData['start_date'])->format('M d Y'),
                        'service_name' => $clientData['lng'] == 'heb'
                            ? $jobData['jobservice']['heb_name']
                            : $jobData['jobservice']['name'],
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.review') . ": " . url("client/jobs/" . base64_encode($jobData['id']) . "/review");


                    break;

                case WhatsappMessageTemplateEnum::DELETE_MEETING:
                    $clientData = $eventData['client'];

                    $receiverNumber = $clientData['phone'];
                    App::setLocale($clientData['lng']);

                    $text = __('mail.wa-message.delete_meeting.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $clientData['firstname'] . ' ' . $clientData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.delete_meeting.content', [
                        'team_name' => isset($eventData['team']) && !empty($eventData['team']['name'])
                            ? $eventData['team']['name']
                            : ' ',
                        'date' => Carbon::parse($eventData['start_date'])->format('d-m-Y'),
                        'start_time' => date("H:i", strtotime($eventData['start_time'])),
                        'end_time' => date("H:i", strtotime($eventData['end_time']))
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::FORM101:
                    $workerData = $eventData;

                    $receiverNumber = $workerData['phone'];
                    App::setLocale($workerData['lng']);

                    $text = __('mail.wa-message.form101.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $workerData['firstname'] . ' ' . $workerData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.form101.content');

                    $text .= "\n\n" . __('mail.wa-message.button-label.form101') . ": " . url("form101/" . base64_encode($workerData['id']) . "/" . base64_encode($workerData['formId']));

                    break;

                case WhatsappMessageTemplateEnum::NEW_JOB:
                    $jobData = $eventData['job'];
                    $workerData = $jobData['worker'];
                    $clientData = $jobData['client'];

                    $receiverNumber = $workerData['phone'];
                    App::setLocale($workerData['lng']);

                    $text = __('mail.wa-message.new_job.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $workerData['firstname'] . ' ' . $workerData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.new_job.content', [
                        'content_txt' => $eventData['content_data'] ? $eventData['content_data'] : ' ',
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'service_name' => $workerData['lng'] == 'heb'
                            ? ($jobData['jobservice']['heb_name'] . ', ')
                            : ($jobData['jobservice']['name'] . ', '),
                        'address' => $jobData['property_address']['address_name'] . " " . ($jobData['property_address']['parking']
                            ? ("[" . $jobData['property_address']['parking'] . "]")
                            :  " "),
                        'status' => ucfirst($jobData['status'])
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/login");

                    break;

                case WhatsappMessageTemplateEnum::WORKER_CONTRACT:
                    $workerData = $eventData;

                    $receiverNumber = $workerData['phone'];
                    App::setLocale($workerData['lng']);

                    $text = __('mail.wa-message.worker_contract.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $workerData['firstname'] . ' ' . $workerData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_contract.content');

                    $text .= "\n\n" . __('mail.wa-message.button-label.check_contract') . ": " . url("worker-contract/" . base64_encode($workerData['worker_id']));

                    break;

                case WhatsappMessageTemplateEnum::WORKER_JOB_APPROVAL:
                    // $adminData = $eventData['admin'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_job_approval.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_job_approval.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $jobData['client']['firstname'] . " " . $jobData['client']['lastname'],
                        'worker_name' => $jobData['worker']['firstname'] . " " . $jobData['worker']['lastname'],
                        'service_name' => ($jobData['jobservice']['name'] . ', '),
                        'address' => $jobData['property_address']['address_name']
                            ? $jobData['property_address']['address_name']
                            : 'NA',
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_NOT_APPROVED_JOB:
                    // $adminData = $eventData['admin'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_not_approved_job.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_not_approved_job.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $jobData['client']['firstname'] . " " . $jobData['client']['lastname'],
                        'worker_name' => $jobData['worker']['firstname'] . " " . $jobData['worker']['lastname'],
                        'service_name' => ($jobData['jobservice']['name'] . ', '),
                        'address' => $jobData['property_address']['address_name']
                            ? $jobData['property_address']['address_name']
                            : 'NA',
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.change_worker') . ": " . url("admin/jobs/" . $jobData['id'] . "/change-worker");

                    $text .= "\n\n" . __('mail.wa-message.button-label.change_shift') . ": " . url("admin/jobs/" . $jobData['id'] . "/change-shift");

                    break;

                case WhatsappMessageTemplateEnum::WORKER_NOT_LEFT_FOR_JOB:
                    // $adminData = $eventData['admin'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_not_left_for_job.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_not_left_for_job.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $jobData['client']['firstname'] . " " . $jobData['client']['lastname'],
                        'worker_name' => $jobData['worker']['firstname'] . " " . $jobData['worker']['lastname'],
                        'service_name' => ($jobData['jobservice']['name'] . ', '),
                        'address' => $jobData['property_address']['address_name']
                            ? $jobData['property_address']['address_name']
                            : 'NA',
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.change_worker') . ": " . url("admin/jobs/" . $jobData['id'] . "/change-worker");

                    $text .= "\n\n" . __('mail.wa-message.button-label.change_shift') . ": " . url("admin/jobs/" . $jobData['id'] . "/change-shift");

                    break;

                case WhatsappMessageTemplateEnum::WORKER_NOT_STARTED_JOB:
                    // $adminData = $eventData['admin'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_not_started_job.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_not_started_job.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $jobData['client']['firstname'] . " " . $jobData['client']['lastname'],
                        'worker_name' => $jobData['worker']['firstname'] . " " . $jobData['worker']['lastname'],
                        'service_name' => ($jobData['jobservice']['name'] . ', '),
                        'address' => $jobData['property_address']['address_name']
                            ? $jobData['property_address']['address_name']
                            : 'NA',
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_worker') . ": " . url("admin/workers/view/" . $jobData['worker']['id']);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_NOT_FINISHED_JOB_ON_TIME:
                    // $adminData = $eventData['admin'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_not_finished_job_on_time.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_not_finished_job_on_time.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $jobData['client']['firstname'] . " " . $jobData['client']['lastname'],
                        'worker_name' => $jobData['worker']['firstname'] . " " . $jobData['worker']['lastname'],
                        'service_name' => ($jobData['jobservice']['name'] . ', '),
                        'address' => $jobData['property_address']['address_name']
                            ? $jobData['property_address']['address_name']
                            : 'NA',
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_worker') . ": " . url("admin/workers/view/" . $jobData['worker']['id']);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_EXCEED_JOB_TIME:
                    // $adminData = $eventData['admin'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_exceed_job_time.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_exceed_job_time.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $jobData['client']['firstname'] . " " . $jobData['client']['lastname'],
                        'worker_name' => $jobData['worker']['firstname'] . " " . $jobData['worker']['lastname'],
                        'service_name' => ($jobData['jobservice']['name'] . ', '),
                        'address' => $jobData['property_address']['address_name']
                            ? $jobData['property_address']['address_name']
                            : 'NA',
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_worker') . ": " . url("admin/workers/view/" . $jobData['worker']['id']);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_REMIND_JOB:
                    $jobData = $eventData['job'];
                    $workerData = $jobData['worker'];
                    $clientData = $jobData['client'];

                    $receiverNumber = $workerData['phone'];
                    App::setLocale($workerData['lng']);

                    $text = __('mail.wa-message.worker_remind_job.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $workerData['firstname'] . ' ' . $workerData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_remind_job.content', [
                        'date' => Carbon::parse($jobData['start_date'])->format('M d Y'),
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'service_name' => $workerData['lng'] == 'heb'
                            ? ($jobData['jobservice']['heb_name'] . ', ')
                            : ($jobData['jobservice']['name'] . ', '),
                        'address' => $jobData['property_address']['address_name'] . " " . ($jobData['property_address']['parking']
                            ? ("[" . $jobData['property_address']['parking'] . "]")
                            :  " "),
                        'start_time' => Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'status' => ucfirst($jobData['status'])
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.approve') . ": " . url("worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve");

                    break;

                case WhatsappMessageTemplateEnum::WORKER_UNASSIGNED:
                    $jobData = $eventData['job'];
                    $oldWorkerData = $eventData['old_worker'];
                    $oldJobData = $eventData['old_job'];

                    $receiverNumber = $oldWorkerData['phone'];
                    App::setLocale($oldWorkerData['lng']);

                    $text = __('mail.wa-message.worker_unassigned_job.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $oldWorkerData['firstname'] . ' ' . $oldWorkerData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_unassigned_job.content', [
                        'date' => Carbon::parse($oldJobData['start_date'])->format('M d Y'),
                        'client_name' => $jobData['client']['firstname'] . ' ' . $jobData['client']['lastname'],
                        'service_name' => $oldWorkerData['lng'] == 'heb' ? ($jobData['jobservice']['heb_name'] . ', ') : ($jobData['jobservice']['name'] . ', '),
                        'start_time' => Carbon::today()->setTimeFromTimeString($oldJobData['start_time'])->format('H:i')
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION:
                    $by = isset($eventData['by']) ? $eventData['by'] : 'client';
                    $adminData = $eventData['admin'];
                    $jobData = $eventData['job'];

                    $receiverNumber = $jobData['client']['phone'];
                    App::setLocale($jobData['client']['lng']);

                    $text = __('mail.wa-message.client_job_status_notification.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $jobData['client']['firstname'] . " " . $jobData['client']['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_job_status_notification.content', [
                        'date' => Carbon::parse($jobData['start_date'])->format('M d Y')  . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => ($jobData['client'] ? ($jobData['client']['firstname'] . " " . $jobData['client']['lastname']) : "NA"),
                        'service_name' => $jobData['jobservice']['name'],
                        'comment' => ($by == 'client' ? ("Client changed the Job status to " . ucfirst($jobData['status']) . "." . ($jobData['cancellation_fee_amount']) ? ("With Cancellation fees " . $jobData['cancellation_fee_amount'] . " ILS.") : " ") : ("Job is marked as " . ucfirst($jobData['status'])))
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("client/login");

                    break;

                case WhatsappMessageTemplateEnum::WORKER_JOB_OPENING_NOTIFICATION:
                    $workerData = $eventData['worker'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_job_opening_notification.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_job_opening_notification.content', [
                        'client_name' => $workerData['firstname'] . " " . $workerData['lastname']
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/jobs/view/" . $jobData['id']);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_worker') . ": " . url("admin/workers/view/" . $jobData['id']);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_JOB_STATUS_NOTIFICATION:
                    $comment = $eventData['comment'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_job_status_notification.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_job_status_notification.content', [
                        'status' => ucfirst($jobData['status']),
                        'date' => Carbon::parse($jobData['start_date'])->format('M d Y') . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'worker_name' => ($jobData['worker'] ? ($jobData['worker']['firstname'] . " " . $jobData['worker']['lastname']) : "NA"),
                        'client_name' => ($jobData['client'] ? ($jobData['client']['firstname'] . " " . $jobData['client']['lastname']) : "NA"),
                        'service_name' => $jobData['jobservice']['name'],
                        'status' => ucfirst($jobData['status'])
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/jobs/view/" . $jobData["id"]);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_SAFE_GEAR:
                    $workerData = $eventData;

                    $receiverNumber = $workerData['phone'];
                    App::setLocale($workerData['lng']);

                    $text = __('mail.wa-message.worker_safe_gear.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $workerData['firstname'] . ' ' . $workerData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_safe_gear.content');

                    $text .= "\n\n" . __('mail.wa-message.button-label.safety_and_gear') . ": " . url("worker-safe-gear/" . base64_encode($workerData["id"]));

                    break;

                case WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING:
                    if ($eventData['purpose'] == "Price offer") {
                        $eventData['purpose'] =  trans('mail.meeting.price_offer');
                    } else if ($eventData['purpose'] == "Quality check") {
                        $eventData['purpose'] =  trans('mail.meeting.quality_check');
                    } else {
                        $eventData['purpose'] = $eventData['purpose'];
                    }

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    $text = __('mail.wa-message.admin_reschedule_meeting.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.admin_reschedule_meeting.content', [
                        'client_name' => $eventData['client']['firstname'] . ' ' . $eventData['client']['lastname'],
                        'date' => Carbon::parse($eventData['start_date'])->format('d-m-Y')  . ($eventData['start_time'] && $eventData['end_time'] ? (" ( " . date("H:i", strtotime($eventData['start_time'])) . " to " . date("H:i", strtotime($eventData['end_time'])) . " ) ") : " "),
                        'address' => isset($eventData['property_address']) ? $eventData['property_address']['address_name'] : 'NA',
                        'purpose' => $eventData['purpose'] ? $eventData['purpose'] : "NA",
                        'meet_link' => $eventData['meet_link'] ? $eventData['meet_link'] : "NA"
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_RESCHEDULE_MEETING:
                    if ($eventData['purpose'] == "Price offer") {
                        $eventData['purpose'] =  trans('mail.meeting.price_offer');
                    } else if ($eventData['purpose'] == "Quality check") {
                        $eventData['purpose'] =  trans('mail.meeting.quality_check');
                    } else {
                        $eventData['purpose'] = $eventData['purpose'];
                    }
                    $teamData = $eventData['team'];
                    $clientData = $eventData['client'];

                    $receiverNumber = $clientData['phone'];
                    App::setLocale($clientData['lng']);

                    $text = __('mail.wa-message.client_reschedule_meeting.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $clientData['firstname'] . ' ' . $clientData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_reschedule_meeting.content', [
                        'team_name' => $clientData['lng'] == 'heb' ? $teamData['heb_name'] : $teamData['name'],
                        'date' => Carbon::parse($eventData['start_date'])->format('d-m-Y')  . ($eventData['start_time'] && $eventData['end_time'] ? (" ( " . date("H:i", strtotime($eventData['start_time'])) . " to " . date("H:i", strtotime($eventData['end_time'])) . " ) ") : " "),
                        'address' => isset($eventData['property_address']) ? $eventData['property_address']['address_name'] : 'NA',
                        'purpose' => $eventData['purpose'] ? $eventData['purpose'] : "NA",
                        'meet_link' => $eventData['meet_link'] ? $eventData['meet_link'] : "NA"
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::ADMIN_LEAD_FILES:
                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    $text = __('mail.wa-message.admin_lead_files.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.admin_lead_files.content', [
                        'client_name' => $eventData['client']['firstname'] . ' ' . $eventData['client']['lastname'],
                        'date' => Carbon::parse($eventData['start_date'])->format('d-m-Y')  . ($eventData['start_time'] && $eventData['end_time'] ? (" ( " . date("H:i", strtotime($eventData['start_time'])) . " to " . date("H:i", strtotime($eventData['end_time'])) . " ) ") : " ")
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.check_file') . ": " . url("storage/uploads/ClientFiles/" . $eventData["file_name"]);

                    break;

                case WhatsappMessageTemplateEnum::LEAD_NEED_HUMAN_REPRESENTATIVE:
                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    $text = __('mail.wa-message.lead_need_human_representative.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.lead_need_human_representative.content', [
                        'client_name' => $eventData['client']['firstname'] . ' ' . $eventData['client']['lastname'],
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_client') . ": " . url("admin/clients/view/" . $eventData['client']['id']);

                    break;

                case WhatsappMessageTemplateEnum::NO_SLOT_AVAIL_CALLBACK:
                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    $text = __('mail.wa-message.no_slot_avail_callback.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.no_slot_avail_callback.content', [
                        'client_name' => $eventData['client']['firstname'] . ' ' . $eventData['client']['lastname'],
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_client') . ": " . url("admin/clients/view/" . $eventData['client']['id']);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_FORMS:
                    $workerData = $eventData;

                    $receiverNumber = $workerData['phone'];
                    App::setLocale($workerData['lng']);

                    $text = __('mail.wa-message.worker_forms.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $workerData['firstname'] . ' ' . $workerData['lastname']
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_forms.content');

                    $text .= "\n\n" . __('mail.wa-message.button-label.check_form') . ": " . url("worker-forms/" . base64_encode($workerData['id']));

                    break;

                case WhatsappMessageTemplateEnum::ADMIN_JOB_STATUS_NOTIFICATION:
                    $by = $eventData['by'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    App::setLocale('en');

                    $text = __('mail.wa-message.admin_job_status_notification.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.admin_job_status_notification.content', [
                        'date' => Carbon::parse($jobData['start_date'])->format('M d Y')  . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'worker_name' => ($jobData['worker'] ? ($jobData['worker']['firstname'] . " " . $jobData['worker']['lastname']) : "NA"),
                        'client_name' => ($jobData['client'] ? ($jobData['client']['firstname'] . " " . $jobData['client']['lastname']) : "NA"),
                        'service_name' => $jobData['jobservice']['name'],
                        'status' => ucfirst($jobData['status']),
                        'comment' => ($by == 'client' ? ("Client changed the Job status to " . ucfirst($jobData['status']) . "." . ($jobData['cancellation_fee_amount']) ? ("With Cancellation fees " . $jobData['cancellation_fee_amount'] . " ILS.") : " ") : ("Job is marked as " . ucfirst($jobData['status'])))
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("admin/jobs/view/" . $jobData["id"]);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_CHANGED_AVAILABILITY_AFFECT_JOB:
                    $workerData = $eventData['worker'];

                    $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_changed_availability_affect_job.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_changed_availability_affect_job.content', [
                        'name' => $workerData['firstname'] . ' ' . $workerData['lastname'],
                        'date' => Carbon::parse($eventData['date'])->format('M d Y'),
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_LEAVES_JOB:
                    $workerData = $eventData['worker'];

                    $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_leaves_job.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_leaves_job.content', [
                        'name' => $workerData['firstname'] . ' ' . $workerData['lastname'],
                        'date' => $workerData['date'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED:
                    $clientData = $eventData['client'];
                    $cardData = $eventData['card'];

                    $receiverNumber = $clientData["phone"];
                    if (!$receiverNumber) {
                        return;
                    }

                    App::setLocale('en');

                    $text = __('mail.wa-message.client_payment_failed.header');
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.common.salutation', ['name' => 'everyone']);
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.client_payment_failed.content', [
                        'name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'card_number' => $cardData['card_number']
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::ORDER_CANCELLED:
                    $clientData = $eventData['client'];
                    $orderData = $eventData['order'];

                    $receiverNumber = config('services.whatsapp_groups.payment_status');
                    App::setLocale('en');

                    $text = __('mail.wa-message.order_cancelled.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.order_cancelled.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'order_id' => $orderData['order_id']
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::PAYMENT_PAID:
                case WhatsappMessageTemplateEnum::PAYMENT_PARTIAL_PAID:
                    $clientData = $eventData['client'];
                    // $amountData = $eventData['amount'];

                    $receiverNumber = config('services.whatsapp_groups.payment_status');
                    App::setLocale('en');

                    $text = __('mail.wa-message.payment_paid.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.payment_paid.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY:
                    $clientData = $eventData['client'];
                    $invoiceData = $eventData['invoice'];

                    $receiverNumber = config('services.whatsapp_groups.payment_status');
                    App::setLocale('en');

                    $text = __('mail.wa-message.client_invoice_created_and_sent_to_pay.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_invoice_created_and_sent_to_pay.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'invoice_id' => $invoiceData['invoice_id']
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_INVOICE_PAID_CREATED_RECEIPT:
                    $clientData = $eventData['client'];
                    $invoiceData = $eventData['invoice'];

                    $receiverNumber = config('services.whatsapp_groups.payment_status');
                    App::setLocale('en');

                    $text = __('mail.wa-message.client_invoice_paid_created_receipt.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_invoice_paid_created_receipt.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'invoice_id' => $invoiceData['invoice_id']
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_EXTRA:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.payment_status');
                    App::setLocale('en');

                    $text = __('mail.wa-message.order_created_with_extra.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.order_created_with_extra.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'order_id' => $eventData['order_id'],
                        'extra' => $eventData['extra'],
                        'total' => $eventData['total_amount'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_DISCOUNT:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.payment_status');
                    App::setLocale('en');

                    $text = __('mail.wa-message.order_created_with_discount.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.order_created_with_discount.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'order_id' => $eventData['order_id'],
                        'discount' => $eventData['discount'],
                        'total' => $eventData['total_amount'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_REVIEWED:
                    $clientData = $eventData['client'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                    App::setLocale('en');

                    $text = __('mail.wa-message.client_reviewed.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_reviewed.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $clientData['firstname'] . " " . $clientData['lastname'],
                        // 'rating' => $jobData['rating'],
                        // 'review' => $jobData['review'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_CHANGED_JOB_SCHEDULE:
                    $clientData = $eventData['client'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                    App::setLocale('en');

                    $text = __('mail.wa-message.client_changed_job_schedule.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_changed_job_schedule.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $clientData['firstname'] . " " . $clientData['lastname'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_COMMENTED:
                    $clientData = $eventData['client'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                    App::setLocale('en');

                    $text = __('mail.wa-message.client_commented.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_commented.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'client_name' => $clientData['firstname'] . " " . $clientData['lastname'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::ADMIN_COMMENTED:
                    $adminData = $eventData['admin'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                    App::setLocale('en');

                    $text = __('mail.wa-message.admin_commented.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.admin_commented.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'admin_name' => $adminData['name'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::WORKER_COMMENTED:
                    $workerData = $eventData['worker'];
                    $jobData = $eventData['job'];

                    $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                    App::setLocale('en');

                    $text = __('mail.wa-message.worker_commented.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.worker_commented.content', [
                        'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        'worker_name' => $workerData['firstname'] . " " . $workerData['lastname'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    $text = __('mail.wa-message.new_lead_arrived.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.new_lead_arrived.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'contact' => $clientData['phone'],
                        'email' => $clientData['email'],
                        'address' => $clientData['geo_address'],
                    ]);

                    $text .= "\n\n" . __('mail.wa-message.button-label.view') . ": " . url("admin/leads/view/" . $clientData['id']);

                    break;

                case WhatsappMessageTemplateEnum::USER_STATUS_CHANGED:
                    // $clientData = $eventData['client'];

                    // $receiverNumber = $clientData["phone"];
                    // if (!$receiverNumber) {
                    //     return;
                    // }

                    // // Set locale if needed
                    // App::setLocale('en');

                    // // Build the WhatsApp message content
                    // $text = __('mail.wa-message.user_status_changed.header');
                    // $text .= "\n\n";
                    // $text .= __('mail.wa-message.common.salutation', ['name' => $clientData['firstname']]);
                    // $text .= "\n\n";
                    // $text .= __('mail.wa-message.user_status_changed.content', [
                    //     'name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    //     'status' => $eventData['status']
                    // ]);

                    break;

                case WhatsappMessageTemplateEnum::UNANSWERED_LEAD:
                    $clientData = $eventData['client'];

                    // $receiverNumber = config('services.whatsapp_groups.lead_client');
                    $receiverNumber = $clientData["phone"];
                    App::setLocale('en');

                    $text = __('mail.wa-message.tried_to_contact_you.header');
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.common.salutation', ['name' => $clientData['firstname']]);
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.tried_to_contact_you.content', [
                        'name' => $clientData['firstname'],
                    ]);
                    $text .= "\n\n";

                    $text .= __('mail.wa-message.tried_to_contact_you.availability');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.tried_to_contact_you.contact_details');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.closing');

                    $text .= "\n\n";
                    $text .= __('mail.wa-message.common.signature');

                    break;

                case WhatsappMessageTemplateEnum::INQUIRY_RESPONSE:
                    $clientData = $eventData['client'];

                    $receiverNumber = $clientData["phone"];
                    App::setLocale('en');

                    // Build the WhatsApp message content
                    $text = __('mail.wa-message.inquiry_response.header');
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.common.salutation', ['name' => $clientData['firstname']]);
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.inquiry_response.content', [
                        'name' => $clientData['firstname'],
                    ]);
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.inquiry_response.service_areas');
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.common.closing');
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.common.signature');

                    break;

                case WhatsappMessageTemplateEnum::FOLLOW_UP_REQUIRED:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    // Build the WhatsApp message content
                    $text = __('mail.wa-message.follow_up_required.header');
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.follow_up_required.salutation');
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.follow_up_required.content', [
                        'lead_name' => $clientData['firstname']. " " .$clientData['lastname'],
                        'contact_info' => $clientData['phone'],
                        'inquiry_date' => Carbon::now()->format('M d Y'),
                    ]);
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.follow_up_required.common.closing');
                    $text .= "\n\n";
                    $text .= __('mail.wa-message.follow_up_required.common.signature');

                    break;

                case WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    // Create the message
                    $text = __('mail.wa-message.follow_up_price_offer.header', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.follow_up_price_offer.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::FINAL_FOLLOW_UP_PRICE_OFFER:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en'); // Adjust the locale if needed

                    // Create the message
                    $text = __('mail.wa-message.final_follow_up_price_offer.header', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.final_follow_up_price_offer.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::LEAD_ACCEPTED_PRICE_OFFER:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    // Create the message
                    $text = __('mail.wa-message.lead_accepted_price_offer.header', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.lead_accepted_price_offer.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::BOOK_CLIENT_AFTER_SIGNED_CONTRACT:
                    $clientData = $eventData['client'];
                    // $serviceData = $eventData['service'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    // Create the message
                    $text = __('mail.wa-message.book_client_after_signed_contract.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'Team',
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.book_client_after_signed_contract.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'client_contact_info' =>$clientData['email'],
                    ]);

                    break;

                case WhatsappMessageTemplateEnum::LEAD_DECLINED_PRICE_OFFER:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en'); // Ensure this matches the locale key used in your translation files

                    // Create the message
                    $text = __('mail.wa-message.lead_declined_price_offer.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'Team',
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.lead_declined_price_offer.content');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.lead_declined_price_offer.details', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'reason' => $clientData['reason'] ?? __('mail.wa-message.lead_declined_price_offer.no_reason_provided'),
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.lead_declined_price_offer.assistance');

                    $text .= __('mail.common.regards');

                    $text .= "\n";

                    $text .= __('mail.common.company');

                    break;

                case WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en'); // Ensure this matches the locale key used in your translation files

                    // Create the message
                    $text = __('mail.wa-message.file_submission_request.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => $clientData['firstname'],
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.file_submission_request.content');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.file_submission_request.details', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.file_submission_request.assistance');

                    $text .= "\n\n";

                    $text .= __('mail.common.regards');

                    $text .= "\n";

                    $text .= __('mail.common.company');

                    break;


                case WhatsappMessageTemplateEnum::LEAD_DECLINED_CONTRACT:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('he');

                    // Create the message
                    $text = __('mail.wa-message.lead_declined_contract.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'Team',
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.lead_declined_contract.content');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.lead_declined_contract.details', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'reason' => $clientData['reason'] ?? __('mail.wa-message.lead_declined_contract.no_reason_provided'),
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.lead_declined_contract.assistance');

                    $text .= __('mail.common.regards');

                    $text .= "\n";

                    $text .= __('mail.common.company');

                    break;

                case WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    // Create the message
                    $text = __('mail.wa-message.client_in_freeze_status.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_in_freeze_status.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_in_freeze_status.action_required');

                    $text .= "\n\n";

                    $text .= __('mail.common.regards');

                    $text .= "\n";

                    $text .= __('mail.common.company');

                    break;

                case WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    // Create the message
                    $text = __('mail.wa-message.status_not_updated.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'Team',
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.status_not_updated.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.status_not_updated.action_required');

                    break;


                case WhatsappMessageTemplateEnum::CLIENT_LEAD_STATUS_CHANGED:
                    $clientData = $eventData['client'];

                    $receiverNumber = config('services.whatsapp_groups.lead_client');
                    App::setLocale('en');

                    $text = __('mail.wa-message.client_lead_status_changed.header');

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.common.salutation', [
                        'name' => 'everyone'
                    ]);

                    $text .= "\n\n";

                    $text .= __('mail.wa-message.client_lead_status_changed.content', [
                        'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                        'new_status' => $eventData['new_status']
                    ]);

                    break;
            }

            if ($receiverNumber && $text) {
                Log::info('SENDING WA to ' . $receiverNumber);

                $response = Http::withToken($this->whapiApiToken)
                    ->post($this->whapiApiEndpoint . 'messages/text', [
                        'to' => $receiverNumber,
                        'body' => $text
                    ]);

                Log::info($response->json());
            }
        } catch (\Throwable $th) {
            // dd($th);
            // throw $th;
            Log::alert('WA NOTIFICATION ERROR');
            Log::alert($th->getMessage());
        }
    }
}
