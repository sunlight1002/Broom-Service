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
use App\Models\WebhookResponse;
use Twilio\Rest\Client as TwilioClient;

class WhatsappNotification
{
    protected $twilioAccountSid ,$twilioAuthToken, $twilioPhoneNumber, $twilio;
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

        $this->twilioAccountSid = config('services.twilio.twilio_id');
        $this->twilioAuthToken = config('services.twilio.twilio_token');
        $this->twilioWhatsappNumber = config('services.twilio.twilio_whatsapp_number');

        // Initialize the Twilio client
        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
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
                $adminJobViewLink = generateShortUrl(url("admin/jobs/view/" . $jobData['id']), 'admin');
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

        // \Log::info(Carbon::parse($eventData['start_date'] ?? "00-00-0000")->format('M d Y') . " " . ($eventData['start_time'] ?? ''));

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
        $serviceNamesString = implode(", ", $serviceNames);


        if(isset($offerData["services"])) {
            $offerDetailLink = generateShortUrl(isset($offerData['id']) ? url("admin/offered-price/edit/" . ($offerData['id'] ?? '')) : '', 'admin');
            $priceOfferLink = generateShortUrl(isset($offerData['id']) ? url("price-offer/" . base64_encode($offerData['id'])) : '', 'client');
        }


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
                ':order_id' => isset($eventData['order_id']) ? $eventData['order_id'] : $eventData['order']['order_id'] ?? '',
                ':discount' => $eventData['discount'] ?? '',
                ':total' => $eventData['total_amount'] ?? '',
                ':extra' => $eventData['extra'] ?? '',
                ':invoice_id' => isset($eventData['invoice']['invoice_id']) ? $eventData['invoice']['invoice_id'] : $eventData['invoice_id'] ?? '',
                ':card_number' => isset($eventData['card']['card_number']) ? $eventData['card']['card_number'] : $eventData['card_number'] ?? '',
                ':icount_doc_url' => isset($eventData['order']) ? "https://app.icount.co.il/hash/show_doc.php?doctype=order&docnum=" . $eventData['order']['order_id'] : '',
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
            $isTwilio = false;
            $data = null;

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
                    case WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_5_PM:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX284d159218aad8b3b04de5ff06238f18";
                        }elseif($lng == "spa"){
                            $sid = "HX38c7043fb5eea7a16534866075c64c90";
                        }elseif($lng == "ru"){
                            $sid = "HXf2d268574176b3a56a0b78c5b63ba706";
                        }else{
                            $sid = "HX517f18e3ae6de354515fcdc52becfb28";
                        }

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                            "3" => $address,
                            "4" => "https://maps.google.com?q=" . ($jobData['property_address']['geo_address'] ?? ''),
                            "5" => $jobData['jobservice']['job_hour'] ?? '',
                            "6" => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                            "7" => isset($workerData['id']) ? "worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve" : '',
                            "8" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;
                               
                    case WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_6_PM:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX2dcd349e3a809d7a1b556eefdfd453a1";
                        }elseif($lng == "spa"){
                            $sid = "HX51a3e067adb77ea32fb998c384334a8f";
                        }elseif($lng == "ru"){
                            $sid = "HX83c19b8510fbafd6a5b030a92f881f3e";
                        }else{
                            $sid = "HXcf6dfdff92e307c16933672410aa8a7a";
                        }

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                            "3" => $address,
                            "4" => "https://maps.google.com?q=" . ($jobData['property_address']['geo_address'] ?? ''),
                            "5" => $jobData['jobservice']['job_hour'] ?? '',
                            "6" => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                            "7" => isset($workerData['id']) ? "worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve" : '',
                            "8" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;
                               
                    case WhatsappMessageTemplateEnum::REMINDER_TO_WORKER_1_HOUR_BEFORE_JOB_START:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX59e8a79f8d128b7b01b33d0acc76ec27";
                        }elseif($lng == "spa"){
                            $sid = "HX6c77c17eaa74ae63b7be720dc92a1437";
                        }elseif($lng == "ru"){
                            $sid = "HX8332e8e7fd6a952ba37c8836a062acfb";
                        }else{
                            $sid = "HX1a1e4ed508b85630608f09f957f3c78e";
                        }

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                            "3" => $address,
                            "4" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                            "5" => "worker/jobs/on-my-way/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                            "6" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;
                               
                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_CONFIRMING_ON_MY_WAY:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX1d046d7026af1a3503e014a8b6a666dd";
                        }elseif($lng == "spa"){
                            $sid = "HX09160733422044b146ec7b7e983a2c20";
                        }elseif($lng == "ru"){
                            $sid = "HX41c4236e972c57966a62678a5ba97145";
                        }else{
                            $sid = "HX7c3b0902a21d7fdc076a2fedf2342bc4";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => "worker/jobs/view/" . $jobData['id'],
                            "3" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;
                                                
                    case WhatsappMessageTemplateEnum::WORKER_START_THE_JOB:
                        \Log::info("WORKER_START_THE_JOB");
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        $commentsText = "";
                        if (!empty($jobData['comments'])) {
                            foreach ($jobData['comments'] as $comment) {
                                if (!empty($comment['comment']) && !empty($comment['name'])) {
                                    $commentsText .= "- " . $comment['comment'] . " (by " . ($comment['name'] ?? "") . ") \n";
                                }
                            }
                        }


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

                        $currentTime = Carbon::parse($jobData['start_time'] ?? '00:00:00');
                        $endTime = Carbon::parse($jobData['end_time'] ?? '00:00:00');
                        $diffInHours = $currentTime->diffInHours($endTime, false);
                        $diffInMinutes = $currentTime->diffInMinutes($endTime, false) % 60;

                        if($lng == "heb"){
                            $sid = "HX47d6750f537aead0e99773e6a6b428f1";
                        }elseif($lng == "spa"){
                            $sid = "HXf889705a6e3dad76e0d521fcf41660b3";
                        }elseif($lng == "ru"){
                            $sid = "HXa50fa914144e29e9a38b8aa3fb840d44";
                        }else{
                            $sid = "HX46a0a18e70945ce1aeb7a6cf842c4e68";
                        }

                        $specialInstruction = $instructions[isset($workerData['lng']) ? $workerData['lng'] : 'en'] ?? "";
                        $commentLinkText = $commentInstructions[isset($workerData['lng']) ? $workerData['lng'] : 'en'] ?? "";

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => trim($address),
                            "3" => $diffInHours . ':' . str_pad($diffInMinutes, 2, '0', STR_PAD_LEFT),
                            "4" => Carbon::today()->setTimeFromTimeString($jobData['end_time'] ?? '00:00:00')->format('H:i'),
                            "5" => trim((($workerData['lng'] ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? '')),
                            "6" => !empty($commentsText) ? $specialInstruction . " " . trim($commentsText) : '',
                            "7" => "worker/jobs/view/" . $jobData['id'],
                            "8" => "worker/jobs/" . ($jobData['uuid'] ?? "")
                        ];
                        
                        
                        try {
                            \Log::info("Sending message via Twilio...");
                            
                            $twi = $this->twilio->messages->create(
                                "whatsapp:+". $receiverNumber,
                                [
                                    "from" => $this->twilioWhatsappNumber, 
                                    "contentSid" => $sid,
                                    "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                    "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                                ]
                            );

                            $data = $twi->toArray();
                            $isTwilio = true;
                            \Log::info("Twilio message sent successfully!");
                        } catch (\Exception $e) {
                            \Log::error("Twilio API Error: " . $e->getMessage());

                        }

                        break;
                             
                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_ALL_COMMENTS_COMPLETED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HXa3c50cf13602bc7716cdb7563d37b634";
                        }elseif($lng == "spa"){
                            $sid = "HX478a2737303b0dff4db377a8a1fe09a3";
                        }elseif($lng == "ru"){
                            $sid = "HXa6641688c7c8c33ec0966033d41254c9";
                        }else{
                            $sid = "HX64e5545c5c73a29913a99b1a17659063";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => "worker/jobs/view/". $jobData['id'],
                            "3" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;

                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_FOR_NEXT_JOB_ON_COMPLETE_JOB:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HXb1b580f3c631bcf941a70e49579028d7";
                        }elseif($lng == "spa"){
                            $sid = "HX101caae38bf3ba26a0c58aa70c001b6d";
                        }elseif($lng == "ru"){
                            $sid = "HXae7060a8729e5afdea06a4268f1af91a";
                        }else{
                            $sid = "HX8aa74ae6e0bf5194b6c91df987ac5c70";
                        }

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                            "3" => $address,
                            "4" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                            "5" => "worker/jobs/view/". $jobData['id'],
                            "6" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;
                                      
                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_FINAL_NOTIFICATION_OF_DAY:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX6090f1738d31f9ae6c0d64f5037dccf4";
                        }elseif($lng == "spa"){
                            $sid = "HX05257c9dd4ca558d421f112a77d90134";
                        }elseif($lng == "ru"){
                            $sid = "HX2304b0453defc7f0aba9f513a3a23beb";
                        }else{
                            $sid = "HXdab8de810d8e981955d6a1026f74e996";
                        }
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                                ]),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_ON_JOB_TIME_OVER:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX55b9c36581faa344d780902936739c95";
                        }elseif($lng == "spa"){
                            $sid = "HXa7cd78cdc0f3d14afb6910c87292f425";
                        }elseif($lng == "ru"){
                            $sid = "HX34dfc36515ab7dacaed9a32d1a858eb1";
                        }else{
                            $sid = "HXf05482816e080a968b31ea6a287a4252";
                        }

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => $address,
                            "3" => Carbon::today()->setTimeFromTimeString($jobData['end_time'] ?? '00:00:00')->format('H:i'),
                            "4" => "worker/jobs/finish/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                            "5" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_MONDAY_WORKER_FOR_SCHEDULE:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HXf552adbb4fca22bdb5051818002819fb";
                        }elseif($lng == "spa"){
                            $sid = "HX85a1b1675653d7f8332032f41a530c12";
                        }elseif($lng == "ru"){
                            $sid = "HX9d0e0f6406f428be69c7f31af9bca144";
                        }else{
                            $sid = "HX8801a0be04a85e04578f454abba7de8e";
                        }

                        $variables = [
                           "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;
                               
                    case WhatsappMessageTemplateEnum::WORKER_FORMS:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX833a10f4f0e6101d10182ffb93a8307e";
                        }elseif($lng == "spa"){
                            $sid = "HX4dff3cef86c76ce228a95e84b495036e";
                        }elseif($lng == "ru"){
                            $sid = "HX54fb9be645633a669c8a71390db936ad";
                        }else{
                            $sid = "HX017483cf367bef17c5c0f42be9ab2214";
                        }

                        $variables = [
                           "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                           "2" => "worker-forms/" . base64_encode($workerData['id'])
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;
                               
                    case WhatsappMessageTemplateEnum::FORM101:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HXe6ed46927c37c23f8c534b8f5a690ecb";
                        }elseif($lng == "spa"){
                            $sid = "HX5096dcd0811c4b50a3130656d2ca9580";
                        }elseif($lng == "ru"){
                            $sid = "HX052be35abfed395d402e8c668396a607";
                        }else{
                            $sid = "HX174f6f4e015c00be9803d908471196c5";
                        }

                        $variables = [
                           "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                           "2" => isset($workerData['id'], $workerData['formId'])
                           ? "form101/" . base64_encode($workerData['id']) . "/" . base64_encode($workerData['formId'])
                           : '',
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;
                              
                    case WhatsappMessageTemplateEnum::NEW_JOB:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HXd48d809175ac7f3faade0105d1337c4c";
                        }elseif($lng == "spa"){
                            $sid = "HX8354d3ed3e7912ca8d832623759f61cf";
                        }elseif($lng == "ru"){
                            $sid = "HX10a8c45cf2302b028323375e0d8cab69";
                        }else{
                            $sid = "HX4d7ae0796e24eb46a5ae2572e0075c76";
                        }

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                           "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                            "3" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                            "4" => (($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? ''),
                            "5" => $address,
                            "6" => ucfirst($jobData['status']) ?? '',
                            "7" => "worker/jobs/view/". $jobData['id'],
                            "8" => "",
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;
                                                
                    case WhatsappMessageTemplateEnum::WORKER_UNASSIGNED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX850ed2ea4e4b37effb23ff24d9f0afe6";
                        }elseif($lng == "spa"){
                            $sid = "HX43a738ad7a192420c93589df04d298d4";
                        }elseif($lng == "ru"){
                            $sid = "HXccb27cb1e1382fdd7349e67ca67cf3b2";
                        }else{
                            $sid = "HX5e643484aa08151853a654e83faba3eb";
                        }

                        $variables = [
                           "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                           "2" => Carbon::parse($eventData['old_job']['start_date'] ?? "00-00-0000")->format('M d Y'),
                           "3" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                            "4" => ($eventData['old_worker']['lng'] ?? 'en') == 'heb'
                                ? (($eventData['job']['jobservice']['heb_name'] ?? '') . ', ')
                                : (($eventData['job']['jobservice']['name'] ?? '') . ', '),
                            "5" => Carbon::parse($eventData['old_job']['start_time'] ?? "00:00")->format('H:i')
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;
                                   
                    case WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_APPROVED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX24fe788eb0d9689e454597cd5f75855a";
                        }else{
                            $sid = "HX0d47df5629c69747c7ffc6f023333e2f";
                        }

                        $variables = [
                           "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                           "2" => $eventData['refundclaim']['status'] ?? "",
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;
                              
                    case WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_REJECTED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX75c211007206b80c6dabed250804a1d7";
                        }else{
                            $sid = "HX3a54285010d608e08f81801b26220d05";
                        }

                        $variables = [
                           "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                           "2" => $eventData['refundclaim']['status'] ?? "",
                           "3" => $eventData['refundclaim']['rejection_comment'] ?? ""
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;
                              
                    case WhatsappMessageTemplateEnum::NOTIFY_WORKER_ONE_WEEK_BEFORE_HIS_VISA_RENEWAL:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HXe9d2a2e986dd67996d53bfe0f6cc140b";
                        }elseif($lng == "spa"){
                            $sid = "HX6be650cc10b1fc882306a6b9d2e09ba4";
                        }elseif($lng == "ru"){
                            $sid = "HX704959e6c268569d7d606a05974882c4";
                        }else{
                            $sid = "HXea9186248377b32ff27a1cce7d697feb";
                        }

                        $variables = [
                           "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                           "2" => $workerData['renewal_visa'] ?? "",
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;
                              
                    case WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX0ea9239bbdf99de574c8626b61609590";
                        }elseif($lng == "spa"){
                            $sid = "HX1997a89a36d6f61c20f7fb9348f998a5";
                        }elseif($lng == "ru"){
                            $sid = "HX7f492288767416911749151dee74a45e";
                        }else{
                            $sid = "HX8be8446eb9a6a946945a728e77ac49a7";
                        }

                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::DAILY_REMINDER_TO_LEAD:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX13e65cfd94e4e0aa7473471742699555";
                        }elseif($lng == "ru"){
                            $sid = "HX2f99b43d02ab5675c12b01ac983b2573";
                        }else{
                            $sid = "HX85eb9417469bb2ab555225740c720200";
                        }

                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;
                        
                    case WhatsappMessageTemplateEnum::FINAL_MESSAGE_IF_NO_TO_LEAD:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX492d26962fe009a4b25157f5fd8bc226";
                        }elseif($lng == "ru"){
                            $sid = "HX8de41c8b676432f67d3aefd96f7b8648";
                        }else{
                            $sid = "HXa2369d2bfc34c47637bb42c319197ea4";
                        }

                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;
                        
                    case WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX7b45e614c0ad4dbe513a37a14b305d04";
                        }elseif($lng == "spa"){
                            $sid = "HX2010e79fde4a4800f28e90b4d9b5da7b";
                        }elseif($lng == "ru"){
                            $sid = "HX4fdf1391b672bca5d7647d2187c8cbf4";
                        }else{
                            $sid = "HXc0e17ef921dd28fed0da75051ab54f06";
                        }

                        $variables = [
                           "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        ];
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;
                              
                    case WhatsappMessageTemplateEnum::SEND_WORKER_JOB_CANCEL_BY_TEAM:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        $currentTime = Carbon::parse($jobData['start_time'] ?? '00:00:00');
                        $endTime = Carbon::parse($jobData['end_time'] ?? '00:00:00');
                        $diffInHours = $currentTime->diffInHours($endTime, false);
                        $diffInMinutes = $currentTime->diffInMinutes($endTime, false) % 60;

                        if($lng == "heb"){
                            $sid = "HX17b075ef1b8e2b5468496fe61ab0d380";
                        }elseif($lng == "spa"){
                            $sid = "HX6e287b595f61c979046d245a53cdf883";
                        }elseif($lng == "ru"){
                            $sid = "HXabd4c46aada73e7d1b82471bbfe73ab2";
                        }else{
                            $sid = "HXa467d131b65a6a361a59c551d71f6cf6";
                        }

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => Carbon::parse($jobData['start_date'] ?? "00-00-0000")->format('M d Y'),
                            "3" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                            "4" => trim((($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? '')),
                            "5" => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                            "6" => $address,
                            "7" => "worker/jobs/view/" . $jobData['id'],
                        ];
                        
                        
                            
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;
                             
                    case WhatsappMessageTemplateEnum::SEND_WORKER_JOB_CANCEL_BY_CLIENT:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';
                        $by = isset($eventData['by']) ? $eventData['by'] : 'client';

                        $currentTime = Carbon::parse($jobData['start_time'] ?? '00:00:00');
                        $endTime = Carbon::parse($jobData['end_time'] ?? '00:00:00');
                        $diffInHours = $currentTime->diffInHours($endTime, false);
                        $diffInMinutes = $currentTime->diffInMinutes($endTime, false) % 60;

                        if($lng == "heb"){
                            $sid = "HXf8c6499b644fe3fe94448eb036e51650";
                        }elseif($lng == "spa"){
                            $sid = "HX22763afe782277cfb599759e56a2f0a3";
                        }elseif($lng == "ru"){
                            $sid = "HX75061df5d63a93579f6b02ca63b719f8";
                        }else{
                            $sid = "HX649912dff0ed81e7243a46e43ff78323";
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

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => Carbon::parse($jobData['start_date'] ?? "00-00-0000")->format('M d Y'),
                            "3" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                            "4" => trim((($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? '')),
                            "5" => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                            "6" => $address,
                            "7" => "worker/jobs/view/" . $jobData['id'],
                            "8" => $cancellationFee
                        ];
                        
                        
                            
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );


                        $data = $twi->toArray();
                        $isTwilio = true;

                        
                        break;

                    case WhatsappMessageTemplateEnum::SEND_WORKER_TO_STOP_TIMER:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HXa95cc010d40732b58054e5755693ffd7";
                        }elseif($lng == "spa"){
                            $sid = "HX46ebc598f6b41d94aacd1cef2af93c59";
                        }elseif($lng == "ru"){
                            $sid = "HX6b93fe644d388f45d099a44a0ee9d658";
                        }else{
                            $sid = "HX44eed8d310442e71666f3ff8eb9f3a9c";
                        }

                        $address = trim($jobData['property_address']['geo_address'] ?? '');
                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => Carbon::parse($jobData['start_date'] ?? "00-00-0000")->format('M d Y'),
                            "3" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                            "4" => trim((($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? '')),
                            "5" => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                            "6" => $address,
                            "7" => "worker/jobs/view/" . $jobData['id'],
                        ];
                        
                        
                        try {
                            \Log::info("Sending message via Twilio...");
                            
                            $twi = $this->twilio->messages->create(
                                "whatsapp:+". $receiverNumber,
                                [
                                    "from" => $this->twilioWhatsappNumber, 
                                    "contentSid" => $sid,
                                    "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                    "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                                ]
                            );

                            $data = $twi->toArray();
                            $isTwilio = true;
                        
                            \Log::info("Twilio message sent successfully!");
                        } catch (\Exception $e) {
                            \Log::error("Twilio API Error: " . $e->getMessage());
                        }

                        break;
                             
                    case WhatsappMessageTemplateEnum::SEND_TO_WORKER_PENDING_FORMS:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX3e4c5c7160088e38eb064cbd6752ec47";
                        }elseif($lng == "spa"){
                            $sid = "HX4efa1d9982eb2f54a0bb3422b0c9a36e";
                        }elseif($lng == "ru"){
                            $sid = "HX960ec603e30b1bc3cd4314b90b637713";
                        }else{
                            $sid = "HX9a3675e53007de5cd1c70bca2bdcdbfc";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => "worker-forms/" . base64_encode($workerData['id'])
                        ];
                        
                        
                            
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX2d37cc3607abb8be6c89758e0a658f04";
                        }elseif($lng == "spa"){
                            $sid = "HX37c26e0fbf3e4c6e7db0cb26d4f0141f";
                        }elseif($lng == "ru"){
                            $sid = "HX717978587980a29e7ec4443030da6749";
                        }else{
                            $sid = "HX36cd452fdedaf0f571ed3ca141656cef";
                        }
                        
                        
                            
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );
                        $data = $twi->toArray();

                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HXad8dfbb6ad7d3863ec4cb0f72b9385d8";
                        }elseif($lng == "spa"){
                            $sid = "HXa436ba9e918f4e4912c8a5baf7080259";
                        }elseif($lng == "ru"){
                            $sid = "HXf2e3b6d77aa1ede1203d55f6fefc3790";
                        }else{
                            $sid = "HX4f35506ce44f595ba7b88323eae293b3";
                        }
                        
                        
                            
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING:
                    case WhatsappMessageTemplateEnum::WORKER_HEARING_SCHEDULE:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if($lng == "heb"){
                            $sid = "HX161d40f9cd389eef365117c0d19f0fb6";
                        }elseif($lng == "spa"){
                            $sid = "HXe8f508aa80d12d1b03fdcfa209b63ae5";
                        }elseif($lng == "ru"){
                            $sid = "HXb1591545679bd7fcea367db3848d00e3";
                        }else{
                            $sid = "HX81bf0ac1c8218b1d0b6b339523142c14";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => isset($eventData['team']) && !empty($eventData['team']['name'])
                                    ? $eventData['team']['name']
                                    : '',
                            "3" => isset($eventData['date']) ? Carbon::parse($eventData['date'])->format('M d Y') : '',
                            "4" => date("H:i", strtotime($eventData['start_time'] ?? "00-00")),
                            "5" => date("H:i", strtotime($eventData['end_time'] ?? "00-00")),
                            "6" => isset($eventData['id']) ? "hearing-schedule/" . base64_encode($eventData['id']) : ''
                        ];
                        
                        
                            
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"
                            ]
                        );
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::TEAM_JOB_NOT_APPROVE_REMINDER_AT_6_PM:
                    case WhatsappMessageTemplateEnum::TEAM_JOB_NOT_CONFIRM_BEFORE_30_MINS:
                    case WhatsappMessageTemplateEnum::TEAM_JOB_NOT_CONFIRM_AFTER_30_MINS:
                    case WhatsappMessageTemplateEnum::WORKER_CONTACT_TO_MANAGER:
                        // $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
                        // $lng = 'heb';
                        
                        // $address = trim($jobData['property_address']['geo_address'] ?? '');
                        // $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        // $address = str_replace(['"', "'"], ' ', $address);

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "3" => $address,
                        //     "4" => $clientData['phone'] ?? '',
                        //     "5" => $workerData['phone'] ?? "",
                        //     "6" => "team-btn/" . base64_encode($jobData['id']),
                        //     "7" => "admin/jobs/view/" . $jobData['id']
                        // ];
                        // 

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX2f62453a08104be3bbffa783c7c96b4a",
                        //             "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;
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
                        // $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                        // $lng = 'heb';
                        
                        // $address = trim($jobData['property_address']['geo_address'] ?? '');
                        // $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        // $address = str_replace(['"', "'"], ' ', $address);

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "3" => $address,
                        //     "4" => $clientData['phone'] ?? '',
                        //     "5" => $workerData['phone'] ?? "",
                        //     "6" => "action-comment/" . ($commentData['id'] ?? ''),
                        //     "7" => "admin/jobs/view/" . $jobData['id']
                        // ];
                        // 

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX5dbcfff00a790a14f06eb7d8b62f75da",
                        //             "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

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
                        // $receiverNumber = config('services.whatsapp_groups.payment_status');
                        // App::setLocale('heb');

                        // $variables = [
                        //     "1" => $eventData['order_id'] ?? '',
                        //     "2" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "3" => $eventData['discount'] ?? '',
                        //     "4" => $eventData['total_amount'] ?? '',
                        // ];
                        // 

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HXe178190e2f9ea539bf3587da7b8be4ed",
                        //             "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;
                        
                    case WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_EXTRA:
                        // $receiverNumber = config('services.whatsapp_groups.payment_status');
                        // App::setLocale('heb');

                        // $variables = [
                        //     "1" => $eventData['order_id'] ?? '',
                        //     "2" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "3" => $eventData['discount'] ?? '',
                        //     "4" => $eventData['total_amount'] ?? '',
                        //     "5" => $eventData['extra'] ?? '',
                        // ];
                        // 

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HXbb85ad956b47940a238748ed22e05896",
                        //             "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;
                        
                    case WhatsappMessageTemplateEnum::CLIENT_INVOICE_PAID_CREATED_RECEIPT:
                    case WhatsappMessageTemplateEnum::CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY:
                    case WhatsappMessageTemplateEnum::PAYMENT_PAID:
                    case WhatsappMessageTemplateEnum::PAYMENT_PARTIAL_PAID:
                        // $receiverNumber = config('services.whatsapp_groups.payment_status');
                        // App::setLocale('heb');

                        // $variables = [
                        //     "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        // ];
                        // 

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX07e08a98812eee36069a041264db8acd",
                        //             "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         ]);

                        //     \Log::info($twi->sid);

                        break;

                    case WhatsappMessageTemplateEnum::ORDER_CANCELLED:
                        // $receiverNumber = config('services.whatsapp_groups.payment_status');
                        // App::setLocale('heb');

                        // $variables = [
                        //     "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "2" => $eventData['order_id'] ?? '',
                        // ];
                        // 

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX1d3d5b18776f2ec9ced0fb94c4371a57",
                        //             "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED:
                        $receiverNumber = config('services.whatsapp_groups.payment_status');
                        App::setLocale('heb');

                        // $variables = [
                        //     "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "2" => $eventData['card']['card_number'] ?? '',
                        //     "3" => "admin/clients/view/" .$clientData['id'] ."?=card"
                        // ];
                        // 

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HXd74549d2abd1ee55ebe5220f8cb8b4d4",
                        //             "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         ]);

                        //     \Log::info($twi->sid);

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
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXf6bb2621f02900daeb0b63cc4b31e374" :"HX5596d68e908a51847aca1e3ac60108a2";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::AFTER_STOP_TO_CLIENT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXe9dc0be6dc99ff02a5e2adc7bf30a735" : "HX64bbd2bf02700b53a8a688e08eba0375";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$receiverNumber",
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid, 
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
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

                        $receiverNumber = $clientData['phone'] ?? null;
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXf5b5a1c437fdd8ba04498df919a95c57" :"HX2844b528b4f5ececf2c2a633fb3ab5df";

                        $address = isset($propertyAddress) && isset($propertyAddress['address_name']) && !empty($propertyAddress['address_name']) ? $propertyAddress['address_name'] : "NA";

                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                            "2" => isset($eventData['start_date']) ? Carbon::parse($eventData['start_date'])->format('d-m-Y') : '',
                            "3" => isset($eventData['start_time']) ? date("H:i", strtotime($eventData['start_time'])) : '',
                            "4" => isset($eventData['end_time']) ? date("H:i", strtotime($eventData['end_time'])) : '',
                            "5" => $address,
                            "6" => $purpose ? $purpose : '',
                            "7" => isset($eventData['id']) ? "meeting-schedule/" . base64_encode($eventData['id']) : '',
                            "8" => isset($eventData['id']) ? "meeting-files/" . base64_encode($eventData['id']) : ''
                        ];
                        Log::info($receiverNumber);

                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXe75478323d1897dfe660aa0efd68afa8" :"HX7a0c41761ad3a45cad1d68c64132afb3";
                        

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                    "2" => isset($eventData['id']) ? "meeting-files/" . base64_encode($eventData['id']) : ''
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::DELETE_MEETING:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX7c7ebf4918b9d70c9d8d54b605d6869c" :"HX1e69e897935c69355e757a0c9ab6fc92";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                    "2" => isset($eventData['team']) && !empty($eventData['team']['name'])
                                    ? $eventData['team']['name']
                                    : '',
                                    "3" => isset($eventData['start_date']) ? Carbon::parse($eventData['start_date'])->format('d-m-Y') : '',
                                    "4" => isset($eventData['start_time']) ? date("H:i", strtotime($eventData['start_time'])) : '',
                                    "5" => isset($eventData['end_time']) ? date("H:i", strtotime($eventData['end_time'])) : '',
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::OFF_SITE_MEETING_REMINDER_TO_CLIENT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX094040aca987f6ac573e08d3c4ea5e64" :"HXc483bf4d5368b797cb1348e771453460";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                    "2" => isset($eventData['id']) ? "meeting-files/" . base64_encode($eventData['id']) : ''
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE: // pending
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX297a4b25919c797e03570892afa5dfcb" :"HX71f57870dff6893b46991ad4c6e8035a";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXd03dbe74963b36f616d28afe42ceb26f" :"HX7135c5e1bc6da6773f99b3e2efe224db";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
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

                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXd5487f7a7ec1c3a316bf8a5bdccce3fd" :"HXbfeccd34bbb9f37bba5690de0e0e9b8f";

                        $address = isset($propertyAddress) && isset($propertyAddress['address_name']) && !empty($propertyAddress['address_name']) ? $propertyAddress['address_name'] : "NA";

                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                            "2" => isset($eventData['start_date']) ? Carbon::parse($eventData['start_date'])->format('d-m-Y') : '',
                            "3" => isset($eventData['start_time']) ? date("H:i", strtotime($eventData['start_time'])) : '',
                            "4" => isset($eventData['end_time']) ? date("H:i", strtotime($eventData['end_time'])) : '',
                            "5" => $address,
                            "6" => $purpose ? $purpose : '',
                            "7" => isset($eventData['id']) ? "meeting-schedule/" . base64_encode($eventData['id']) : '',
                            "8" => isset($eventData['id']) ? "meeting-files/" . base64_encode($eventData['id']) : ''
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::UNANSWERED_LEAD:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXd2938634aead6ea78c061e646fe842ff" :"HX27fb48c040eb82000bee49889456372e";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ]),
                                "statusCallback" => "https://2e18-2405-201-2022-10c3-f8e0-b2f4-f0a9-cd01.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::PAST:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXbf7b57c3d82b5a448e378d75c48bd03e" :"HX92b3db078d6037ba945f0698c08daed5";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXbd480e4791ebd24e5c61537dbf1be153" :"HXc34a5efcb2594ccf527d7127fb7479c4";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ]),
                                "statusCallback" => "https://0c4c-2405-201-2022-10c3-f734-3028-2a3e-4203.ngrok-free.app/twilio/webhook"

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_CLIENT_FOR_TOMMOROW_MEETINGS:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXef823fe7472ae0011da2f429bc6239ae" :"HX8c016b14713907f2a8d24111b2fab63d";

                        $variables = [
                            "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                            "2" => Carbon::parse($eventData['start_date'] ?? "00-00-0000")->format('M d Y') . " " .  ($eventData['start_time'] ?? ''),
                            "3" => $eventData["meet_link"] ?? "",
                            "4" => isset($eventData['id']) ? "meeting-schedule/" . base64_encode($eventData['id']) : '',
                            "5" => isset($eventData['id']) ? "meeting-files/" . base64_encode($eventData['id']) : ''
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        $propertyAddress = $eventData['property_address'] ?? null;

                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX415bf1b37ef5073097aa2fb19674089e" :"HX03f556cd581d9ee30eafb1a434a23a9d";

                        $address = isset($propertyAddress) && isset($propertyAddress['address_name']) && !empty($propertyAddress['address_name']) ? $propertyAddress['address_name'] : "NA";

                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                            "2" => isset($eventData['start_date']) ? Carbon::parse($eventData['start_date'])->format('d-m-Y') : '',
                            "3" => isset($eventData['start_time']) ? date("H:i", strtotime($eventData['start_time'])) : '',
                            "4" => isset($eventData['end_time']) ? date("H:i", strtotime($eventData['end_time'])) : '',
                            "5" => $address,
                            "6" => isset($eventData['id']) ? "meeting-schedule/" . base64_encode($eventData['id']) : '',
                            "7" => isset($eventData['id']) ? "meeting-files/" . base64_encode($eventData['id']) : ''
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_NOT_IN_SYSTEM_OR_NO_OFFER:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => "HXc8ccae82c125e06ff7f030d653a42b58",
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_HAS_OFFER_BUT_NO_SIGNED_OR_NO_CONTRACT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => "HX37b9ce6142bcd1f316ee043460a5342f",
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_3_DAYS:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX277201b6c5489e79309dd1882e69c647" :"HX8bbbcd4cf3f8e96f44ca8477c2302398";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_7_DAYS:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX8619a2955dba05875e6d0469309e727e" :"HX0d1766ed98ef0d04de8652d78f9f8495";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_8_DAYS:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX44807e05c04d5e9028a576b006c4dfb2" :"HX5f7121ebc2e96d3b97095c9c18b1b37a";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );
                        $isTwilio = true;
                        $data = $twi->toArray();
                        \Log::info($twi->sid);

                        break;

                    case WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_CLIENT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX2b0109ddce6eb84778226b9ca7b06a5a" :"HX2f2a4d4e86d66202cf981395a18338d9";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                    "2" => $eventData['activity']['reschedule_date'] ?? '',
                                    "3" => $eventData['activity']['reschedule_time'] ?? '',
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CONTACT_ME_TO_RESCHEDULE_THE_MEETING_CLIENT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXb8968c5efd4a94ba9bc8c718993c9bcc" :"HXca5ecd51118c6222d0443dbf4d6038c3";

                        
                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;


                    case WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED_TO_CLIENT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = isset($clientData['contact_person_phone']) ? $clientData['contact_person_phone'] : $clientData['phone'] ?? null;
                        $property_person_name = isset($clientData['contact_person_name']) ? $clientData['contact_person_name'] : trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX3fe032cc7fa68e6e95b5fea5dfd0300d" :"HX0b2f5c1ddc53f455835a96e3ef7abe67";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                    "2" => $eventData['card']['card_number'] ?? '',
                                    "3" => "client/settings"
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::WEEKLY_CLIENT_SCHEDULED_NOTIFICATION://done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXec70a491f299dc4dc28fa0bedd7c173b" :"HXafe82297e085462e4c9f5357eb966a21";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                    "2" => "client/jobs"
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::UPDATE_ON_COMMENT_RESOLUTION: //done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXb82a7cca9e5af48cdc86d0ae2e408359" :"HX70833a91ef1060ca3d9e9c134957e834";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                    "2" => (($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? ''),
                                    "3" => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                                    "4" => "client/jobs/view/" . base64_encode($jobData['id'])
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::OFFER_PRICE: //done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                            \Log::info("airbnb");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        } elseif (isset($propertyData['contact_person_phone'])) {
                            \Log::info("property");
                            $receiverNumber = $propertyData['contact_person_phone'];
                            $property_person_name = $propertyData['contact_person_name'] ?? null;
                        } else {
                            \Log::info("client");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        }

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXddec37bb3da456c00acf52a069896971" :"HX9a919e0b306dfb615a13ceffeef5e5e5";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                    "2" => $offerData['service_names'] ?? '',
                                    "3" => "price-offer/" . base64_encode($offerData['id'])
                                ]),
                                // "statusCallback" => "https://5231-2405-201-2022-10c3-c0f5-9685-c6e2-519b.ngrok-free.app/twilio/webhook"
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER_SENT_CLIENT: //done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                            \Log::info("airbnb");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        } elseif (isset($propertyData['contact_person_phone'])) {
                            \Log::info("property");
                            $receiverNumber = $propertyData['contact_person_phone'];
                            $property_person_name = $propertyData['contact_person_name'] ?? null;
                        } else {
                            \Log::info("client");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        }

                        $serviceNames = [];

                        // Check if 'services' exists and is an array or object
                        if (isset($offerData['services']) && (is_array($offerData['services']) || is_object($offerData['services']))) {
                            foreach ($offerData['services'] as $service) {
                                if (isset($service->name)) {
                                    $serviceNames[] = $service->name;
                                }
                            }
                        }
                        $serviceNamesString = implode(", ", $serviceNames);

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX2c5d36c7e8690403dc745bc0ebb48caf" : "HX49ece44c705b4d597fe468e3e352a2af";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                    "2" => isset($offerData['created_at']) ? Carbon::parse($offerData['created_at'])->format('M d Y H:i') : '',
                                    "3" => $serviceNamesString,
                                    "4" => isset($offerData['id']) ? "price-offer/" . base64_encode($offerData['id']) : '',
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_TO_CLIENT_CONTRACT_NOT_SIGNED: //done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                            \Log::info("airbnb");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        } elseif (isset($propertyData['contact_person_phone'])) {
                            \Log::info("property");
                            $receiverNumber = $propertyData['contact_person_phone'];
                            $property_person_name = $propertyData['contact_person_name'] ?? null;
                        } else {
                            \Log::info("client");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        }

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX02e1b8b09cd63db888d8e1ac514cb17d" : "HX88a6433f9c8299f34ac87ab111b3bd24";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                    "2" => isset($contractData['created_at']) ? Carbon::parse($contractData['created_at']?? '')->format('M d Y H:i') : '',
                                    "3" => (isset($contractData['contract_id']) || isset($contractData['unique_hash'])) 
                                        ? "work-contract/" . ($contractData['contract_id'] ?? $contractData['unique_hash']) 
                                        : "",
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT: //done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                            \Log::info("airbnb");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        } elseif (isset($propertyData['contact_person_phone'])) {
                            \Log::info("property");
                            $receiverNumber = $propertyData['contact_person_phone'];
                            $property_person_name = $propertyData['contact_person_name'] ?? null;
                        } else {
                            \Log::info("client");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        }

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXf0d1d917ed012e3edbdcaec03e45fbaa" : "HX102f841955a48204ccb6de7ac9d57008";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::CONTRACT: //done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                            \Log::info("airbnb");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        } elseif (isset($propertyData['contact_person_phone'])) {
                            \Log::info("property");
                            $receiverNumber = $propertyData['contact_person_phone'];
                            $property_person_name = $propertyData['contact_person_name'] ?? null;
                        } else {
                            \Log::info("client");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        }

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXa2c5325320ad094ae219a925df42e9f7" :"HX85857ebadb6d65144e4b82a933ebb0fa";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                    "2" => "work-contract/" . $offerData['contract_id'] ?? $offerData['unique_hash'] ?? '',
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        
                        break;

                    case WhatsappMessageTemplateEnum::CREATE_JOB: //done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                            \Log::info("airbnb");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        } elseif (isset($propertyData['contact_person_phone'])) {
                            \Log::info("property");
                            $receiverNumber = $propertyData['contact_person_phone'];
                            $property_person_name = $propertyData['contact_person_name'] ?? null;
                        } else {
                            \Log::info("client");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        }

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        

                        $sid = $lng == "heb" ? "HXb1d2ed5e977018e2488d428531d48389" :"HX9391bb3e7f827f0b24813d17bccfc00b";

                        $variables = [
                            "1" => $property_person_name,
                            "2" => (($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? ''),
                            "3" => Carbon::parse($jobData['start_date'] ?? "00-00-0000")->format('M d Y'),
                            "4" => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                            "5" => "client/jobs/view/" . base64_encode($jobData['id'])
                        ];

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                "statusCallback" => "https://eb4d-2405-201-2022-10c3-80f3-1c63-af73-7d69.ngrok-free.app/twilio/webhook"

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED: //done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                            \Log::info("airbnb");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        } elseif (isset($propertyData['contact_person_phone'])) {
                            \Log::info("property");
                            $receiverNumber = $propertyData['contact_person_phone'];
                            $property_person_name = $propertyData['contact_person_name'] ?? null;
                        } else {
                            \Log::info("client");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        }

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXc632938113f6ceccf9244a970b0bc078" :"HXd8a8f402984ddec0473666b7a24a8ca6";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                    "2" => "client/jobs/" . base64_encode($jobData['id']) . "/review"
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;
                        
                    case WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION: //done
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }

                        if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                            \Log::info("airbnb");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        } elseif (isset($propertyData['contact_person_phone'])) {
                            \Log::info("property");
                            $receiverNumber = $propertyData['contact_person_phone'];
                            $property_person_name = $propertyData['contact_person_name'] ?? null;
                        } else {
                            \Log::info("client");
                            $receiverNumber = $clientData['phone'] ?? null;
                            $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
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

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX635f65014d14b31877ebdabf86b31464" :"HX17a4cbb43cd201be896f958d7486c018";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                    "2" => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                                    "3" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                    "4" => (($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? ''),
                                    "5" => $commentBy,
                                    "6" => "client/jobs/view/" . base64_encode($jobData['id'])
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;
                        
                    case WhatsappMessageTemplateEnum::CLIENT_DECLINED_PRICE_OFFER:
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

                        $sid = $lng == "heb" ? "HX0bc765375b88af4339ae184e6786dbb5" :"HX18f4f53c412e97618f6fb1df4648f5e3";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

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

                        $sid = $lng == "heb" ? "HX64602821f84177f06957a7c14791280a" :"HX9d6876b57d72631a4c2f220752224567";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+". $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber, 
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ])
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::ADMIN_LEAD_FILES:
                    case WhatsappMessageTemplateEnum::FOLLOW_UP_REQUIRED:
                    case WhatsappMessageTemplateEnum::STATUS_NOT_UPDATED:
                    case WhatsappMessageTemplateEnum::BOOK_CLIENT_AFTER_SIGNED_CONTRACT:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $teamtwi = $this->twilio->messages->create(
                        //     "whatsapp:+". $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber, 
                        //         "contentSid" => "HX5460411c8361a287d406816d6e6f40a7",
                        //         "contentVariables" => json_encode([
                        //             "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //             "2" => $clientData['phone'] ?? '',
                        //             "3" => "admin/view-contract/" . $contractData['id']
                        //         ])
                        //     ]
                        // );

                        // \Log::info($teamtwi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::NOTIFY_TEAM_REQUEST_TO_CHANGE_SCHEDULE_CLIENT:
                    case WhatsappMessageTemplateEnum::NOTIFY_TEAM_REQUEST_TO_CHANGE_SCHEDULE_WORKER:
                    case WhatsappMessageTemplateEnum::LEAD_ACCEPTED_PRICE_OFFER:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        //     $teamtwi = $this->twilio->messages->create(
                        //         "whatsapp:+". $receiverNumber,
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HXb163e54b192baae940fa5d92f2297ac3",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => "admin/leads/view/" . $clientData['id']
                        //             ])
                        //         ]
                        //     );

                        //     \Log::info($teamtwi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::LEAD_DECLINED_PRICE_OFFER:
                    case WhatsappMessageTemplateEnum::LEAD_DECLINED_CONTRACT:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+". $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber, 
                        //         "contentSid" => "HXde08101daa59eb083d4b0e7fcf8912a0",
                        //         "contentVariables" => json_encode([
                        //             "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //             "2" =>  $clientData['reason'] ?? __('mail.wa-message.lead_declined_contract.no_reason_provided'),
                        //             "3" => "admin/leads/view/" . $clientData['id']
                        //         ])
                        //     ]
                        // );

                        // \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::CLIENT_LEAD_STATUS_CHANGED:
                    case WhatsappMessageTemplateEnum::PENDING:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HXd6dcedcb86da5afd516c6f14ebdbc228",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::POTENTIAL:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX3eea58013c8e52c1372c54e642540c35",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::IRRELEVANT:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX54f9a67f82dd64597655c66c1453856a",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::UNINTERESTED:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX64e04d98cc547be1ea0f8837dbaf27bb",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::UNANSWERED:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX96c7875ae926091d20bd2d6ab5b9e351",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::POTENTIAL_CLIENT:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HXfe583369f0a6664b227d17b76a10aa47",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::PENDING_CLIENT:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX34c387255c30e724ee7ae9a12adc56ac",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::WAITING:
                    case WhatsappMessageTemplateEnum::ACTIVE_CLIENT:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HXc256e028829006e698c92011be484393",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::FREEZE_CLIENT:
                    case WhatsappMessageTemplateEnum::UNHAPPY:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX62022d943828cef76bbf9a42073eeec5",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::PRICE_ISSUE:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HXca1e3d0daf8a228322ea8013361a4237",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::MOVED:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX8fb343da35e9de3dce65940da134baa9",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::ONETIME:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX2dd4e659142a446430aa3a91400bf098",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => $clientData['phone'] ?? '',
                        //                 "3" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::NO_SLOT_AVAIL_CALLBACK:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //         [
                        //             "from" => $this->twilioWhatsappNumber, 
                        //             "contentSid" => "HX1787c34118768c54858246d8c21ff8de",
                        //             "contentVariables" => json_encode([
                        //                 "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //                 "2" => "admin/leads/view/" . $clientData['id']
                        //                 ])
                        //         ]);

                        //     \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::LEAD_NEED_HUMAN_REPRESENTATIVE:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $sid = "HX5c7480b0b4599caeef1b48fb43e5c29a";

                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //     [
                        //         "from" => $this->twilioWhatsappNumber, 
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode([
                        //             "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //             "2" => "admin/leads/view/" . $clientData['id']
                        //         ])
                        //     ]
                        // );

                        // \Log::info($twi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_TEAM:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';


                        // if ($offerData && isset($offerData['service_template_names']) && str_contains($offerData['service_template_names'], 'airbnb')) {
                        //     \Log::info("airbnb");
                        //     $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        // } elseif (isset($propertyData['contact_person_phone'])) {
                        //     \Log::info("property");
                        //     $property_person_name = $propertyData['contact_person_name'] ?? null;
                        // } else {
                        //     \Log::info("client");
                        //     $property_person_name = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;
                        // }

                        // $teamTwi = $this->twilio->messages->create(
                        //     "whatsapp:+". $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber, 
                        //         "contentSid" => "HX3a84d21ba2a34591fc32686897537535",
                        //         "contentVariables" => json_encode([
                        //             "1" => $property_person_name,
                        //             "2" => "admin/create-job/" . $contractData['id'],
                        //         ])
                        //     ]
                        // );

                        // \Log::info($teamTwi->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $addresses = [];

                        // // Add all property addresses if they exist
                        // if (!empty($clientData['property_addresses']) && is_array($clientData['property_addresses'])) {
                        //     foreach ($clientData['property_addresses'] as $propertyAddress) {
                        //         if (!empty($propertyAddress['geo_address'])) {
                        //             $addresses[] = $propertyAddress['geo_address'];
                        //         }
                        //     }
                        // }

                        // $fullAddress = implode(', ', $addresses);


                        // $variables = [
                        //     "1" => trim(($clientData['firstname'] ?? '') . ' ' . ($clientData['lastname'] ?? '')),
                        //     "2" => $clientData['phone'] ?? "N/A",
                        //     "3" => $clientData['email'] ?? "N/A",
                        //     "4" => $fullAddress ?? "N/A",
                        //     "5" => $eventData['type'] ?? "N/A",
                        //     "6" => "admin/leads/view/" . ($clientData['id'] ?? "N/A"),
                        // ];
                        
                        // $teamMsg = $this->twilio->messages->create(
                        //     "whatsapp:",
                        //     [
                        //         "from" => "$this->twilioWhatsappNumber",
                        //         "contentSid" => "HX6966c131706592080d5e1c00acd394c0",
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         // "statusCallback" => "https://612a-2405-201-2022-10c3-1484-7d36-5a49-eef1.ngrok-free.app/twilio/webhook"
                        //     ]
                        // );

                        //     \Log::info($teamMsg->sid);

                        // break;

                    case WhatsappMessageTemplateEnum::CLIENT_RESCHEDULE_MEETING:
                    case WhatsappMessageTemplateEnum::NOTIFY_TEAM_FOR_TOMMOROW_MEETINGS:
                    case WhatsappMessageTemplateEnum::STOP:
                        // $receiverNumber = config('services.whatsapp_groups.lead_client');
                        // $lng = 'heb';

                        // $teamSid = "HX551ad01347f1b11225044a4503bd0803";
                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+$receiverNumber",
                        //     [
                        //         "from" => $this->twilioWhatsappNumber, 
                        //         "contentSid" => $teamSid,
                        //         "contentVariables" => json_encode([
                        //             "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //             "2" => $clientData['phone'],
                        //             "3" => $clientData['email'],
                        //             "2" => "admin/clients/view/" . $clientData['id']
                        //         ])
                        //     ]
                        // );

                        // \Log::info($twi->sid);
                        // break;

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
            if ($receiverNumber && $text) {
                Log::info('SENDING WA to ' . $receiverNumber);
                Log::info($text);
                Log::info($eventType);

                StoreWebhookResponse($text, $receiverNumber, $data);

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
                
                if(!$isTwilio){
                    \Log::info("Sending message $isTwilio");

                    $response = Http::withToken($token)
                    ->post($this->whapiApiEndpoint . 'messages/text', [
                        'to' => $receiverNumber,
                        'body' => $text
                    ]);

                    Log::info($response->json());
                }else{
                    \Log::info("twilio message $isTwilio");
                }
            }
        } catch (\Throwable $th) {
            // dd($th);
            // throw $th;
            Log::error('WA NOTIFICATION ERROR', ['error' => $th]);
        }
    }
}
