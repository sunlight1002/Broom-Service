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
use Illuminate\Support\Facades\Storage;
use App\Models\WhatsappTemplate;
use App\Models\ShortUrl;

class WhatsappNotification
{
    protected $whapiApiEndpoint, $whapiApiToken, $whapiWorkerApiToken, $whapiClientApiToken, $whapiWorkerJobApiToken;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->whapiApiEndpoint = config('services.whapi.url');
        $this->whapiApiToken = config('services.whapi.token');
        $this->whapiWorkerApiToken = config('services.whapi.worker_token');
        $this->whapiClientApiToken = config('services.whapi.client_token');
        $this->whapiWorkerJobApiToken = config('services.whapi.worker_job_token');
    }

    private function replaceClientFields($text, $clientData, $eventData)
    {
        $placeholders = [];
        if (isset($clientData) && !empty($clientData)) {
            $addresses = [];

            // Add all property addresses if they exist
            if (!empty($clientData['property_addresses']) && is_array($clientData['property_addresses'])) {
                foreach ($clientData['property_addresses'] as $propertyAddress) {
                    if (!empty($propertyAddress['geo_address'])) {
                        $addresses[] = $propertyAddress['geo_address'];
                    }
                }
            }


            if(isset($clientData['id']) && !empty($clientData['id'])) {
                $leadDetailLink = generateShortUrl(url("admin/leads/view/" . $clientData['id']), 'admin');
                $clientJobsLink = generateShortUrl(url("client/jobs"), 'client');
                $clientDetailsLink = generateShortUrl(url("admin/clients/view/" . $clientData['id']), 'admin');
                $clientCardLink = generateShortUrl(url("client/settings"), 'client');
                $adminClientCardLink = generateShortUrl(url("admin/clients/view/" .$clientData['id'] ."?=card"), 'admin');
                $testimonialsLink = generateShortUrl(url('https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl'));
                $brromBrochureLink = generateShortUrl($clientData['lng'] == "en" ? url("pdfs/BroomServiceEnglish.pdf") : url("pdfs/BroomServiceHebrew.pdf"));
                $requestToChangeLink = generateShortUrl(url("/request-to-change/" .  base64_encode($clientData['id']). "?type=client" ?? ''), 'client');
            }


            // Concatenate all addresses into a single string, separated by a comma
            $fullAddress = implode(', ', $addresses);

            // Replaceable values
            $placeholders = [
                ':client_name' => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')),
                ':client_contact' => '+' . ($clientData['phone'] ?? ''),
                ':service_requested' => '',
                ':client_email' => $clientData['email'] ?? '',
                ':client_address' => $fullAddress ?? "NA",
                ':lead_detail_link' => $leadDetailLink ?? '',
                ':client_phone_number' => '+' . ($clientData['phone'] ?? ''),
                ':reason' => $clientData['reason'] ?? __('mail.wa-message.lead_declined_contract.no_reason_provided'),
                ':inquiry_date' => Carbon::now()->format('M d Y'),
                ':client_create_date' => isset($clientData['created_at']) ? Carbon::parse($clientData['created_at'])->format('M d Y H:i') : '',
                ':lead_detail_url' => $leadDetailLink ?? '',
                ':client_jobs' => $clientJobsLink ?? '',
                ':client_detail_url' => $clientDetailsLink ?? '',
                ':request_change_schedule' => $requestToChangeLink ?? '',
                ':request_details' => isset($eventData['request_details']) ? $eventData['request_details'] : '',
                ':new_status' => $eventData['new_status'] ?? '',
                ':testimonials_link' => $testimonialsLink ?? '',
                ':broom_brochure' => $brromBrochureLink ?? '',
                ':admin_add_client_card' => $adminClientCardLink ?? '',
                ':client_card' => $clientCardLink ?? '',
            ];

        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceWorkerFields($text, $workerData, $eventData)
    {
        $placeholders = [];
        if(isset($workerData) && !empty($workerData)) {

            if(isset($workerData['id']) && !empty($workerData['id'])) {
                $workerFormsLink = generateShortUrl(url("worker-forms/" . base64_encode($workerData['id'])), 'worker');
                $form101Link = generateShortUrl(
                    isset($workerData['id'], $workerData['formId'])
                    ? url("form101/" . base64_encode($workerData['id']) . "/" . base64_encode($workerData['formId']))
                    : '',
                    'worker'
                );
                $workerViewLink = generateShortUrl(url("admin/workers/view/" . $workerData['id']), 'worker');
                $requestToChangeLink = generateShortUrl(url("/request-to-change/" .  base64_encode($workerData['id']). "?type=worker" ?? ''), 'worker');
                $workerLeadFormsLink = generateShortUrl(url("worker-forms/" . base64_encode($workerData['id']) . "?type=lead" ?? ''), 'worker');
            }
            $placeholders = [
                ':worker_name' => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                ':worker_lead_name' => trim($workerData['name'] ?? ''),
                ':worker_lead_phone' => isset($workerData['phone']) ? $workerData['phone'] : $workerData['phone'],
                ':worker_phone_number' => '+' . ($workerData['phone'] ?? ''),
                ':request_change_schedule' => $requestToChangeLink ?? '',
                ':request_details' => isset($eventData['request_details']) ? $eventData['request_details'] : '',
                ':last_work_date' => $workerData['last_work_date'] ?? '',
                ':date' => isset($eventData['date']) ? Carbon::parse($eventData['date'])->format('M d Y') : '',
                ':check_form' => $workerFormsLink ?? '',
                ':worker_lead_check_form' => $workerLeadFormsLink ?? '',
                ':form_101_link' => $form101Link ?? '',
                ':refund_rejection_comment' => $eventData['refundclaim']['rejection_comment'] ?? "",
                ':refund_status' => $eventData['refundclaim']['status'] ?? "",
                ':visa_renewal_date' => $workerData['renewal_visa'] ?? "",
                ':worker_detail_url' => $workerViewLink ?? '',
            ];
        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceJobFields($text, $jobData, $workerData = null, $commentData = null)
    {
        $placeholders = [];
        if(isset($jobData) && !empty($jobData)) {
            $commentsText = null;
            if (!empty($jobData['comments'])) {
                foreach ($jobData['comments'] as $comment) {
                    $commentsText .= "- " . $comment['comment'] . " (by " . $comment['name'] . ") \n";
                }
            }

            if(isset($jobData['id']) && !empty($jobData['id'])) {
                $adminJobViewLink = generateShortUrl(url("admin/job/view/" . $jobData['id']), 'admin');
                $clientJobsReviewLink = generateShortUrl(url("client/jobs/" . base64_encode($jobData['id']) . "/review"), 'client');
                $teamJobActionLink = generateShortUrl(url("admin/jobs/" . $jobData['id'] . "/change-worker"), 'admin');
                $clientJobViewLink = generateShortUrl(url("client/jobs/view/" . base64_encode($jobData['id'])), 'client');
                $workerJobViewLink = generateShortUrl(url("worker/jobs/view/" . $jobData['id']), 'worker');
                $teamBtns = generateShortUrl(url("team-btn/" . base64_encode($jobData['id'])), 'admin');
                $contactManager = generateShortUrl(url("worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")), 'worker');
                $leaveForWork = generateShortUrl(url("worker/jobs/on-my-way/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")), 'worker');
                $finishJobByWorker = generateShortUrl(url("worker/jobs/finish/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")), 'worker');
                
                $workerApproveJob = generateShortUrl(
                    isset($workerData['id']) ? url("worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve") : null,
                    'worker'
                );
                $teamSkipComment = generateShortUrl(url("action-comment/" . ($commentData['id'] ?? '')), 'admin');
                $googleAddress = generateShortUrl(url("https://maps.google.com?q=" . ($jobData['property_address']['geo_address'] ?? '')), 'worker');
            }

            $currentTime = Carbon::parse($jobData['start_time'] ?? '00:00:00');
            $endTime = Carbon::parse($jobData['end_time'] ?? '00:00:00');
            $diffInHours = $currentTime->diffInHours($endTime, false);
            $diffInMinutes = $currentTime->diffInMinutes($endTime, false) % 60;

            $specialInstruction = "";
            $commentLinkText = "";

            $instructions = [
                "en" => "- *Special Instructions:*",
                "heb" => "- *הוראות מיוחדות:*",
                "spa" => "- *Instrucciones especiales:*",
                "rus" => "- *Особые инструкции:*",
            ];

            $commentInstructions = [
                "en" => "- *Click Here to Confirm Comments are Done*",
                "heb" => "- *לחץ כאן לאישור שהמשימות בוצעו*",
                "spa" => "- *Haga clic aquí para confirmar que las tareas están completadas*",
                "rus" => "- *Нажмите здесь, чтобы подтвердить выполнение задач*",
            ];

            $specialInstruction = $instructions[isset($workerData['lng']) ? $workerData['lng'] : 'en'] ?? "";
            $commentLinkText = $commentInstructions[isset($workerData['lng']) ? $workerData['lng'] : 'en'] ?? "";

            $placeholders = [
                ':job_full_address' => $jobData['property_address']['geo_address'] ?? '',
                ':google_address' => $googleAddress ?? '',
                ':job_start_date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                ':job_start_date' => Carbon::parse($jobData['start_date'] ?? "00-00-0000")->format('M d Y'),
                ':job_start_time' => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                ':job_end_time' => Carbon::today()->setTimeFromTimeString($jobData['end_time'] ?? '00:00:00')->format('H:i'),
                ':job_remaining_hours' => $diffInHours . ':' . $diffInMinutes,
                ':job_comments' => $commentsText ? $specialInstruction . " " . $commentsText : '',
                ':team_skip_comment_link' => $teamSkipComment ?? '',
                ':job_service_name' => (($workerData['lng'] ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? ''),
                ':team_job_link' => $adminJobViewLink ?? '',
                ':team_action_btns_link' => $teamBtns ?? '',
                ':worker_job_link' => $workerJobViewLink ?? '',
                ':leave_for_work' => $leaveForWork ?? '',
                ':finish_job_by_worker' => $finishJobByWorker ?? '',
                ':comment_worker_job_link' => $commentsText ? "\n".$commentLinkText . " " . $workerJobViewLink : '',
                ':client_view_job_link' => $clientJobViewLink ?? '',
                ':team_job_action_link' => $teamJobActionLink ?? '',
                ':job_status' => ucfirst($jobData['status']) ?? '',
                ':client_job_review' => $clientJobsReviewLink ?? '',
                ':content_txt' => isset($eventData['content_data']) ? $eventData['content_data'] : ' ',
                ':rating' => $jobData['rating'] ?? "",
                ':review' => $jobData['review'] ?? "",
                ':job_accept_url' => $workerApproveJob ?? '',
                ':job_contact_manager_link' => $contactManager ?? '',
                ':job_hours' => $jobData['jobservice']['job_hour'] ?? '',
            ];

        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceMeetingFields($text, $eventData, $lng)
    {
        $propertyAddress = $eventData['property_address'] ?? null;
        $purpose = '';
        if (isset($eventData['purpose'])) {
            if ($eventData['purpose'] == "Price offer") {
                $purpose = trans('mail.meeting.price_offer');
            } else if ($eventData['purpose'] == "Quality check") {
                $purpose = trans('mail.meeting.quality_check');
            } else {
                $purpose = $eventData['purpose'];
            }
        }

        if(isset($eventData)) {
            $meetingRescheduleLink = generateShortUrl(isset($eventData['id']) ? url("meeting-schedule/" . base64_encode($eventData['id'])) : '');
            $meetingFileUploadLink = generateShortUrl(isset($eventData['id']) ? url("meeting-files/" . base64_encode($eventData['id'])) : '');
            $uploadedFilesLink = generateShortUrl(isset($eventData["file_name"]) ? url("storage/uploads/ClientFiles/" . $eventData["file_name"]) : '', 'admin');
            $meetingAcceptLink = generateShortUrl(isset($eventData['id']) ? url("thankyou/".base64_encode($eventData['id'])."/accept") : "");
            $meetingRejectLink = generateShortUrl(isset($eventData['id']) ? url("thankyou/".base64_encode($eventData['id'])."/reject") : "");
        }

        $address = isset($propertyAddress) && isset($propertyAddress['address_name']) && !empty($propertyAddress['address_name']) ? $propertyAddress['address_name'] : "NA";

           // Calculate 'today_tommarow_or_date' field
        $startDate = isset($eventData['start_date']) ? Carbon::parse($eventData['start_date']) : null;
        $todayTomorrowOrDate = '';
        if ($startDate) {
            if ($startDate->isToday()) {
                $todayTomorrowOrDate = $lng === "heb" ? "'היום'" : 'Today';
            } elseif ($startDate->isTomorrow()) {
                $todayTomorrowOrDate = $lng === "heb" ? "'מחר'" : 'Tomorrow';
            } else {
                $todayTomorrowOrDate = $startDate->format('d-m-Y');
            }
        }

        \Log::info(Carbon::parse($eventData['start_date'] ?? "00-00-0000")->format('M d Y') . " " . ($eventData['start_time'] ?? ''));

        $placeholders = [
            ':meeting_team_member_name' => isset($eventData['team']) && !empty($eventData['team']['name'])
                ? $eventData['team']['name']
                : ' ',
            ':meeting_date_time' => Carbon::parse($eventData['start_date'] ?? "00-00-0000")->format('M d Y') . " " .  ($eventData['start_time'] ?? ''),
            ':meeting_start_time' => isset($eventData['start_time']) ? date("H:i", strtotime($eventData['start_time'])) : '',
            ':meeting_end_time' => isset($eventData['end_time']) ? date("H:i", strtotime($eventData['end_time'])) : '',
            ':meeting_address' => $address ?? '',
            ':meeting_purpose' => $purpose ? $purpose : "",
            ':meeting_reschedule_link' => $meetingRescheduleLink ?? "",
            ':meeting_date' => isset($eventData['start_date']) ? Carbon::parse($eventData['start_date'])->format('d-m-Y') : '',
            ':meeting_file_upload_link' => $meetingFileUploadLink ?? "",
            ':meeting_uploaded_file_url' => $uploadedFilesLink ?? "",
            ':file_upload_date' => $eventData["file_upload_date"] ?? '',
            ':meet_link' => $eventData["meet_link"] ?? "",
            ':today_tommarow_or_date' => $todayTomorrowOrDate,
            ':meeting_accept' => $meetingAcceptLink ?? "",
            ':meeting_reject' => $meetingRejectLink ?? "",
            ':all_team_meetings' => $eventData['all_meetings'] ?? "",
        ];

        // Replace placeholders with actual values
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceOfferFields($text, $offerData, $clientData, $propertyData)
    {
        $serviceNames = [];

        // Check if 'services' exists and is an array or object
        if (isset($offerData['services']) && (is_array($offerData['services']) || is_object($offerData['services']))) {
            foreach ($offerData['services'] as $service) {
                if (isset($service->name)) {
                    $serviceNames[] = $service->name;
                }
            }
        }


        if(isset($offerData["services"])) {
            $offerDetailLink = generateShortUrl(isset($offerData['id']) ? url("admin/offered-price/edit/" . ($offerData['id'] ?? '')) : '', 'admin');
            $priceOfferLink = generateShortUrl(isset($offerData['id']) ? url("price-offer/" . base64_encode($offerData['id'])) : '', 'client');
        }

        $serviceNamesString = implode(", ", $serviceNames);

        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
        } elseif (isset($propertyData['contact_person_name']) && !empty($propertyData['contact_person_name'])) {
            $property_person_name = $propertyData['contact_person_name'] ?? null;
        } else {
            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
        }

        $placeholders = [];
        if ($offerData) {
            $placeholders = [
                ':property_person_name' => $property_person_name  ?? '',
                ':offer_service_names' => $offerData['service_names'] ?? '',
                ':offer_pending_since' => $offerData['offer_pending_since'] ?? '',
                ':offer_detail_url' => $offerDetailLink ?? '',
                ':client_price_offer_link' => $priceOfferLink ?? '',
                ':price_offer_services' => $serviceNamesString,
                ':offer_sent_date' => isset($offerData['created_at']) ? Carbon::parse($offerData['created_at'])->format('M d Y H:i') : '',
            ];
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }


    private function replaceContractFields($text, $contractData, $eventData)
    {
        $placeholders = [];
        if($contractData) {

            if(isset($contractData["contract_id"]) || $contractData["id"]) {
                $teamViewContract = generateShortUrl(isset($contractData['id']) ? url("admin/view-contract/" . $contractData['id'] ?? '') : '', 'admin');
                $createJobLink = generateShortUrl(isset($contractData['id']) ? url("admin/create-job/" . ($contractData['id'] ?? "")) : "", 'admin');
                $clientContractLink = generateShortUrl((isset($contractData['contract_id']) || isset($contractData['unique_hash'])) ? url("work-contract/" . ($contractData['contract_id'] ?? $contractData['unique_hash'])) : '', 'client');
            }

            $placeholders = [
                // ':property_person_name' => $property_person_name  ?? '',
                ':client_contract_link' => $clientContractLink ?? '',
                ':team_contract_link' => $teamViewContract ?? '',
                ':contract_sent_date' => isset($contractData['created_at']) ? Carbon::parse($contractData['created_at']?? '')->format('M d Y H:i') : '',
                ':create_job' => $createJobLink ?? '',

            ];
        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceOrderFields($text, $eventData)
    {
        $placeholders = [];
        if($eventData) {
            $placeholders = [
                ':order_id' => $eventData['order_id'] ?? '',
                ':discount' => $eventData['discount'] ?? '',
                ':total' => $eventData['total_amount'] ?? '',
                ':extra' => $eventData['extra'] ?? '',
                ':invoice_id' => $eventData['invoice']['invoice_id'] ?? '',
                ':card_number' => $eventData['card']['card_number'] ?? ''
            ];
        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceOtherFields($text, $eventData)
    {
        $placeholders = [];
        if($eventData || $eventData['activity'] || ($eventData['old_worker'] && $eventData['old_job'])) {
            $by = isset($eventData['by']) ? $eventData['by'] : 'client';

            if(isset($eventData)) {
                $workerHearingLink = generateShortUrl(isset($eventData['id']) ? url("hearing-schedule/" . base64_encode($eventData['id'])) : '', 'worker');
            }

            $commentBy = "";
            $cancellationFee = null;
            if ($by === 'client') {
                $status = isset($eventData['job']) && ucfirst($eventData['job']['status'] ?? "");
                $cancellationFee = isset($eventData['job']['cancellation_fee_amount'])
                    ? ($eventData['job']['cancellation_fee_amount'] . " ILS")
                    : null;

                if (isset($eventData['client']) && $eventData['client']['lng'] === 'en') {
                    $commentBy = "Client changed the Job status to $status.";
                    if ($cancellationFee) {
                        $commentBy .= " With Cancellation fees $cancellationFee.";
                    }
                } else {
                    $commentBy = "הלקוח שינה את סטטוס העבודה ל $status.";
                    if ($cancellationFee) {
                        $commentBy .= " עם דמי ביטול $cancellationFee.";
                    }
                }
            } else {
                $status = isset($eventData['job']) && ucfirst($eventData['job']['status'] ?? "");
                $commentBy = "עבודה מסומנת בתור $status";
            }

            $placeholders = [
               ':team_name' => isset($eventData['team']) && !empty($eventData['team']['name'])
                                ? $eventData['team']['name']
                                : ' ',
                ':date'          => Carbon::parse($eventData['start_date']?? "00-00-0000")->format('d-m-Y'),
                ':start_time'    => date("H:i", strtotime($eventData['start_time'] ?? "00-00")),
                ':end_time'      => date("H:i", strtotime($eventData['end_time'] ?? "00-00")),
                ':purpose'       => $eventData['purpose'] ?? "No purpose provided",
                ':worker_hearing' => $workerHearingLink ?? '',
                ':old_job_start_date' => Carbon::parse($eventData['old_job']['start_date'] ?? "00-00-0000")->format('M d Y'),
                ':client_name' => ($eventData['job']['client']['firstname'] ?? '') . ' ' . ($eventData['job']['client']['lastname'] ?? ''),
                ':old_worker_service_name' => ($eventData['old_worker']['lng'] ?? 'en') == 'heb'
                    ? (($eventData['job']['jobservice']['heb_name'] ?? '') . ', ')
                    : (($eventData['job']['jobservice']['name'] ?? '') . ', '),
                ':old_job_start_time' => Carbon::parse($eventData['old_job']['start_time'] ?? "00:00")->format('H:i'),
                ':comment' => $commentBy ?? '',
                ':admin_name' => $eventData['admin']['name'] ?? '',
                ':came_from' => $eventData['type'] ?? '',
                ':reschedule_call_date' => $eventData['activity']['reschedule_date'] ?? '',
                ':reschedule_call_time' => $eventData['activity']['reschedule_time'] ?? '',
                ':activity_reason' => $eventData['activity']['reason'] ?? '',
                ':cancellation_fee' => $cancellationFee ?? '',
                // ':content_txt' => $eventData['content_data'] ? $eventData['content_data'] : ' ',

            ];
        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
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
            $template = WhatsappTemplate::where('key', $eventType)->first();
            $lng = 'heb';
            if ($template) {
                $jobData = $eventData['job'] ?? null;
                $workerData = $eventData['worker'] ?? null;
                $commentData = $eventData['comment'] ?? null;
                $clientData = $eventData['client'] ?? null;
                $offerData = $eventData['offer'] ?? null;
                $contractData = $eventData['contract'] ?? null;
                $propertyData = $eventData['property'] ?? null;

                switch ($eventType) {
                    case WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_6_PM:
                    case WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_5_PM:
                    case WhatsappMessageTemplateEnum::REMINDER_TO_WORKER_1_HOUR_BEFORE_JOB_START:
                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_CONFIRMING_ON_MY_WAY:
                    case WhatsappMessageTemplateEnum::WORKER_START_THE_JOB:
                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_ALL_COMMENTS_COMPLETED:
                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_FOR_NEXT_JOB_ON_COMPLETE_JOB:
                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_FINAL_NOTIFICATION_OF_DAY:
                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_ON_JOB_TIME_OVER:
                    case WhatsappMessageTemplateEnum::NOTIFY_MONDAY_WORKER_FOR_SCHEDULE:
                    case WhatsappMessageTemplateEnum::WORKER_FORMS:
                    case WhatsappMessageTemplateEnum::FORM101:
                    case WhatsappMessageTemplateEnum::NEW_JOB:
                    case WhatsappMessageTemplateEnum::WORKER_UNASSIGNED:
                    case WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_APPROVED:
                    case WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_REJECTED:
                    case WhatsappMessageTemplateEnum::NOTIFY_WORKER_ONE_WEEK_BEFORE_HIS_VISA_RENEWAL:
                    case WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED:
                    case WhatsappMessageTemplateEnum::DAILY_REMINDER_TO_LEAD:
                    case WhatsappMessageTemplateEnum::FINAL_MESSAGE_IF_NO_TO_LEAD:
                    case WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT:
                    case WhatsappMessageTemplateEnum::SEND_WORKER_JOB_CANCEL_BY_TEAM:
                    case WhatsappMessageTemplateEnum::SEND_WORKER_JOB_CANCEL_BY_CLIENT:
                    case WhatsappMessageTemplateEnum::SEND_WORKER_TO_STOP_TIMER:
                    case WhatsappMessageTemplateEnum::SEND_TO_WORKER_PENDING_FORMS:
                    case WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD:
                    case WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM:
                    case WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING:
                    case WhatsappMessageTemplateEnum::WORKER_HEARING_SCHEDULE:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';
                        break;

                    case WhatsappMessageTemplateEnum::TEAM_JOB_NOT_APPROVE_REMINDER_AT_6_PM:
                    case WhatsappMessageTemplateEnum::TEAM_JOB_NOT_CONFIRM_BEFORE_30_MINS:
                    case WhatsappMessageTemplateEnum::TEAM_JOB_NOT_CONFIRM_AFTER_30_MINS:
                    case WhatsappMessageTemplateEnum::WORKER_CONTACT_TO_MANAGER:
                    case WhatsappMessageTemplateEnum::NOTIFY_TEAM_ONE_WEEK_BEFORE_WORKER_VISA_RENEWAL:
                    case WhatsappMessageTemplateEnum::WORKER_NOT_FINISHED_JOB_ON_TIME:
                        $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
                        $lng = 'heb';
                        break;

                    case WhatsappMessageTemplateEnum::NEW_LEAD_FOR_HIRING_TO_TEAM:
                    case WhatsappMessageTemplateEnum::NEW_LEAD_FOR_HIRING_24HOUR_TO_TEAM:
                    case WhatsappMessageTemplateEnum::NEW_LEAD_HIRIED_TO_TEAM:
                    case WhatsappMessageTemplateEnum::NEW_LEAD_IN_HIRING_DAILY_REMINDER_TO_TEAM:
                        $receiverNumber = config('services.whatsapp_groups.relevant_with_workers');
                        $lng = 'en';
                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_TEAM_FOR_SKIPPED_COMMENTS:
                    case WhatsappMessageTemplateEnum::WORKER_LEAVES_JOB:
                    case WhatsappMessageTemplateEnum::WORKER_CHANGED_AVAILABILITY_AFFECT_JOB:
                    case WhatsappMessageTemplateEnum::WORKER_JOB_STATUS_NOTIFICATION:
                    case WhatsappMessageTemplateEnum::ADMIN_JOB_STATUS_NOTIFICATION:
                        $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                        $lng = 'heb';
                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_REVIEWED:
                    case WhatsappMessageTemplateEnum::CLIENT_CHANGED_JOB_SCHEDULE:
                    case WhatsappMessageTemplateEnum::CLIENT_COMMENTED:
                    case WhatsappMessageTemplateEnum::ADMIN_COMMENTED:
                        $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                        App::setLocale('heb');

                    case WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_DISCOUNT:
                    case WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_EXTRA:
                    case WhatsappMessageTemplateEnum::CLIENT_INVOICE_PAID_CREATED_RECEIPT:
                    case WhatsappMessageTemplateEnum::CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY:
                    case WhatsappMessageTemplateEnum::PAYMENT_PAID:
                    case WhatsappMessageTemplateEnum::PAYMENT_PARTIAL_PAID:
                    case WhatsappMessageTemplateEnum::ORDER_CANCELLED:
                    case WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED:
                        $receiverNumber = config('services.whatsapp_groups.payment_status');
                        App::setLocale('heb');
                        break;

                    // case WhatsappMessageTemplateEnum::UPDATE_ON_COMMENT_RESOLUTION:
                    // case WhatsappMessageTemplateEnum::OFFER_PRICE:
                    // case WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER_SENT_CLIENT:
                    // case WhatsappMessageTemplateEnum::NOTIFY_TO_CLIENT_CONTRACT_NOT_SIGNED:
                    // case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT:
                    // case WhatsappMessageTemplateEnum::CONTRACT:
                    // case WhatsappMessageTemplateEnum::CREATE_JOB:
                    // case WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED:
                    // case WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION:
                    // case WhatsappMessageTemplateEnum::WEEKLY_CLIENT_SCHEDULED_NOTIFICATION:
                    // case WhatsappMessageTemplateEnum::CLIENT_DECLINED_PRICE_OFFER:
                    // case WhatsappMessageTemplateEnum::CLIENT_DECLINED_CONTRACT:
                    // case WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED_TO_CLIENT:
                    case WhatsappMessageTemplateEnum::INQUIRY_RESPONSE:
                    case WhatsappMessageTemplateEnum::AFTER_STOP_TO_CLIENT:
                    case WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE:
                    case WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST:
                    case WhatsappMessageTemplateEnum::DELETE_MEETING:
                    case WhatsappMessageTemplateEnum::OFF_SITE_MEETING_REMINDER_TO_CLIENT:
                    case WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE:
                    case WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS:
                    case WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER:
                    case WhatsappMessageTemplateEnum::UNANSWERED_LEAD:
                    case WhatsappMessageTemplateEnum::PAST:
                    case WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION:
                    case WhatsappMessageTemplateEnum::NOTIFY_CLIENT_FOR_TOMMOROW_MEETINGS:
                    case WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING:
                    case WhatsappMessageTemplateEnum::CLIENT_NOT_IN_SYSTEM_OR_NO_OFFER:
                    case WhatsappMessageTemplateEnum::CLIENT_HAS_OFFER_BUT_NO_SIGNED_OR_NO_CONTRACT:
                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_3_DAYS:
                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_7_DAYS:
                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_8_DAYS:
                    case WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_CLIENT:
                    case WhatsappMessageTemplateEnum::CONTACT_ME_TO_RESCHEDULE_THE_MEETING_CLIENT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';
                        break;


                    case WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED_TO_CLIENT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = isset($clientData['contact_person_phone']) ? $clientData['contact_person_phone'] : $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';
                        break;

                    case WhatsappMessageTemplateEnum::WEEKLY_CLIENT_SCHEDULED_NOTIFICATION://done
                    case WhatsappMessageTemplateEnum::UPDATE_ON_COMMENT_RESOLUTION: //done
                    case WhatsappMessageTemplateEnum::OFFER_PRICE: //done
                    case WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER_SENT_CLIENT: //done
                    case WhatsappMessageTemplateEnum::NOTIFY_TO_CLIENT_CONTRACT_NOT_SIGNED: //done
                    case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT: //done
                    case WhatsappMessageTemplateEnum::CONTRACT: //done
                    case WhatsappMessageTemplateEnum::CREATE_JOB: //done
                    case WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED: //done
                    case WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION: //done
                    case WhatsappMessageTemplateEnum::CLIENT_DECLINED_PRICE_OFFER:
                    case WhatsappMessageTemplateEnum::CLIENT_DECLINED_CONTRACT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                            \Log::info("airbnb");
                            $receiverNumber = $clientData['phone'] ?? null;
                        } elseif (isset($propertyData['contact_person_phone'])) {
                            \Log::info("property");
                            $receiverNumber = $propertyData['contact_person_phone'];
                        } else {
                            \Log::info("client");
                            $receiverNumber = $clientData['phone'] ?? null;
                        }

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';
                        break;

                    case WhatsappMessageTemplateEnum::ADMIN_LEAD_FILES:
                    case WhatsappMessageTemplateEnum::FOLLOW_UP_REQUIRED:
                    case WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED:
                    case WhatsappMessageTemplateEnum::BOOK_CLIENT_AFTER_SIGNED_CONTRACT:
                    case WhatsappMessageTemplateEnum::NOTIFY_TEAM_REQUEST_TO_CHANGE_SCHEDULE_CLIENT:
                    case WhatsappMessageTemplateEnum::NOTIFY_TEAM_REQUEST_TO_CHANGE_SCHEDULE_WORKER:
                    case WhatsappMessageTemplateEnum::LEAD_ACCEPTED_PRICE_OFFER:
                    case WhatsappMessageTemplateEnum::LEAD_DECLINED_PRICE_OFFER:
                    case WhatsappMessageTemplateEnum::LEAD_DECLINED_CONTRACT:
                    case WhatsappMessageTemplateEnum::CLIENT_LEAD_STATUS_CHANGED:
                    case WhatsappMessageTemplateEnum::PENDING:
                    case WhatsappMessageTemplateEnum::POTENTIAL:
                    case WhatsappMessageTemplateEnum::IRRELEVANT:
                    case WhatsappMessageTemplateEnum::UNINTERESTED:
                    case WhatsappMessageTemplateEnum::UNANSWERED:
                    case WhatsappMessageTemplateEnum::POTENTIAL_CLIENT:
                    case WhatsappMessageTemplateEnum::PENDING_CLIENT:
                    case WhatsappMessageTemplateEnum::WAITING:
                    case WhatsappMessageTemplateEnum::ACTIVE_CLIENT:
                    case WhatsappMessageTemplateEnum::FREEZE_CLIENT:
                    case WhatsappMessageTemplateEnum::UNHAPPY:
                    case WhatsappMessageTemplateEnum::PRICE_ISSUE:
                    case WhatsappMessageTemplateEnum::MOVED:
                    case WhatsappMessageTemplateEnum::ONETIME:
                    case WhatsappMessageTemplateEnum::NO_SLOT_AVAIL_CALLBACK:
                    case WhatsappMessageTemplateEnum::LEAD_NEED_HUMAN_REPRESENTATIVE:
                    case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_TEAM:
                    case WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED:
                    case WhatsappMessageTemplateEnum::CLIENT_RESCHEDULE_MEETING:
                    case WhatsappMessageTemplateEnum::NOTIFY_TEAM_FOR_TOMMOROW_MEETINGS:
                    case WhatsappMessageTemplateEnum::STOP:
                    case WhatsappMessageTemplateEnum::CLIENT_MEETING_CANCELLED:
                    case WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_TEAM:
                    case WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_TEAM_ON_DATE:
                    case WhatsappMessageTemplateEnum::NOTIFY_TO_TEAM_CONTRACT_NOT_SIGNED:
                    case WhatsappMessageTemplateEnum::CONTACT_ME_TO_RESCHEDULE_THE_MEETING_TEAM:
                    // case WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST_TEAM:
                        $receiverNumber = config('services.whatsapp_groups.lead_client');
                        $lng = 'heb';
                        break;
                }

                $text = $template->{'message_' . $lng};
                $text = $this->replaceClientFields($text, $clientData, $eventData);
                $text = $this->replaceWorkerFields($text, $workerData, $eventData);
                $text = $this->replaceJobFields($text, $jobData, $workerData, $commentData);
                $text = $this->replaceMeetingFields($text, $eventData, $lng);
                $text = $this->replaceOfferFields($text, $offerData, $clientData, $propertyData);
                $text = $this->replaceContractFields($text, $contractData, $eventData);
                $text = $this->replaceOrderFields($text, $eventData);
                $text = $this->replaceOtherFields($text, $eventData);

            } else {
                switch ($eventType) {


                    case WhatsappMessageTemplateEnum::SICK_LEAVE_NOTIFICATION:
                        $userData = $eventData['user'];
                        $clientData = $eventData['client'];
                        $leaveData = $eventData['sickleave'];

                        $receiverNumber = $userData['phone'];
                        App::setLocale($userData['phone'] ?? 'en');

                        // Message Content
                        $text = __('mail.wa-message.follow_up.subject');
                        $text .= "\n\n";
                        $text .= __('mail.wa-message.follow_up.salutation', [
                            'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname']
                        ]);


                        break;
                }
            }

            // $receiverNumber = '918469138538';
            // $receiverNumber = config('services.whatsapp_groups.notification_test');
            if ($receiverNumber && $text) {
                Log::info('SENDING WA to ' . $receiverNumber);
                // Log::info($text);
                // Log::info($eventType);
                // Log::info($lng);

                $token = $this->whapiApiToken;

                if($receiverNumber == config('services.whatsapp_groups.relevant_with_workers')){
                    $token = $this->whapiWorkerApiToken;
                }else if($eventType == WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE || $eventType == WhatsappMessageTemplateEnum::NOTIFY_MONDAY_WORKER_FOR_SCHEDULE){
                    $token = $this->whapiClientApiToken;
                }else if(
                    $eventType == WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_5_PM ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_6_PM ||
                    $eventType == WhatsappMessageTemplateEnum::REMINDER_TO_WORKER_1_HOUR_BEFORE_JOB_START ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_NOTIFY_ON_JOB_TIME_OVER
                ){
                    $token = $this->whapiWorkerJobApiToken;
                }else if(
                    $eventType == WhatsappMessageTemplateEnum::FORM101 ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_FORMS ||
                    $eventType == WhatsappMessageTemplateEnum::SEND_TO_WORKER_PENDING_FORMS ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT ||
                    $eventType == WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD ||
                    $eventType == WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM ||
                    $eventType == WhatsappMessageTemplateEnum::NEW_LEAD_HIRIED_TO_TEAM
                ){
                    $token = $this->whapiWorkerApiToken;
                }else {
                    $token = $this->whapiApiToken;
                }

                $receiverNumber = "918000318833";

                $response = Http::withToken($token)
                    ->post($this->whapiApiEndpoint . 'messages/text', [
                        'to' => $receiverNumber,
                        'body' => $text
                    ]);

                // Log::info($response->json());
            }
        } catch (\Throwable $th) {
            // dd($th);
            // throw $th;
            // Log::error('WA NOTIFICATION ERROR', ['error' => $th]);
        }
    }
}
