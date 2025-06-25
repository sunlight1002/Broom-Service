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
use Google\Cloud\Translate\V2\TranslateClient;
use Illuminate\Support\Str;



class WhatsappNotification
{
    protected $twilioAccountSid, $twilioAuthToken, $twilioWhatsappNumber, $twilioWorkerLeadWhatsappNumber, $twilio;
    protected $whapiApiEndpoint, $whapiApiToken, $whapiWorkerApiToken, $whapiClientApiToken, $whapiWorkerJobApiToken, $translateClient;

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
        $this->twilioWorkerLeadWhatsappNumber = config('services.whapi.whapi_worker_lead_number_1');


        $this->translateClient = new TranslateClient([
            'key' => config('services.google.translate_key'),
        ]);

        // Initialize the Twilio client
        $this->twilio = new TwilioClient($this->twilioAccountSid, $this->twilioAuthToken);
    }

    private function replaceClientFields($text, $clientData, $eventData)
    {
        $placeholders = [];

        $lng = isset($eventData['worker']) && isset($eventData['worker']['lng']) ? $eventData['worker']['lng'] : 'en';

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


            if (isset($clientData['id']) && !empty($clientData['id'])) {
                $leadDetailLink = generateShortUrl(url("admin/leads/view/" . $clientData['id']), 'admin');
                $clientJobsLink = generateShortUrl(url("client/jobs"), 'client');
                $clientDetailsLink = generateShortUrl(url("admin/clients/view/" . $clientData['id']), 'admin');
                $clientCardLink = generateShortUrl(url("client/settings"), 'client');
                $adminClientCardLink = generateShortUrl(url("admin/clients/view/" . $clientData['id'] . "?=card"), 'admin');
                $testimonialsLink = generateShortUrl(url('https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl'));
                $brromBrochureLink = generateShortUrl($clientData['lng'] == "en" ? url("pdfs/BroomServiceEnglish.pdf") : url("pdfs/BroomServiceHebrew.pdf"));
                $requestToChangeLink = generateShortUrl(url("/request-to-change/" .  base64_encode($clientData['id']) . "?type=client" ?? ''), 'client');
            }


            // Concatenate all addresses into a single string, separated by a comma
            $fullAddress = implode(', ', $addresses);
            $clientName = trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''));


            $ClientNametranslation = $this->translateClient->translate($clientName, [
                'target' => $lng == "heb" ? 'he' : 'en',
            ]);

            // Replaceable values
            $placeholders = [
                'translated_client_name' => $ClientNametranslation['text'],
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
        if (isset($workerData) && !empty($workerData)) {

            if (isset($workerData['id']) && !empty($workerData['id'])) {
                $workerFormsLink = generateShortUrl(url("worker-forms/" . base64_encode($workerData['id'])), 'worker');
                $form101Link = generateShortUrl(
                    isset($workerData['id'], $workerData['formId'])
                        ? url("form101/" . base64_encode($workerData['id']) . "/" . base64_encode($workerData['formId']))
                        : '',
                    'worker'
                );
                $workerViewLink = generateShortUrl(url("admin/workers/view/" . $workerData['id']), 'worker');
                $requestToChangeLink = generateShortUrl(url("/request-to-change/" .  base64_encode($workerData['id']) . "?type=worker" ?? ''), 'worker');
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

    private function getStreetNameFromAddressComponents($addressComponents)
    {
        $street = null;
        $number = null;

        foreach ($addressComponents as $component) {
            if (in_array('route', $component['types'])) {
                $street = $component['long_name'];
            }

            if (in_array('street_number', $component['types'])) {
                $number = $component['long_name'];
            }
        }

        return trim(($number ? $number . ' ' : '') . $street);
    }

    private function replaceJobFields($text, $jobData, $workerData = null, $commentData = null)
    {
        $placeholders = [];
        if (isset($jobData) && !empty($jobData)) {
            $commentsText = null;
            if (!empty($jobData['comments'])) {
                foreach ($jobData['comments'] as $comment) {
                    $commentsText .= "- " . $comment['comment'] . " (by " . $comment['name'] . ") \n";
                }
            }

            $latitude = $jobData['property_address']['latitude'] ?? null;
            $longitude = $jobData['property_address']['longitude'] ?? null;

            if (isset($jobData['id']) && !empty($jobData['id'])) {
                $adminJobViewLink = generateShortUrl(url("admin/jobs/view/" . $jobData['id']), 'admin');
                $clientJobsReviewLink = generateShortUrl(url("client/jobs/" . base64_encode($jobData['id']) . "/review"), 'client');
                $teamJobActionLink = generateShortUrl(url("admin/jobs/" . $jobData['id'] . "/change-worker"), 'admin');
                $clientJobViewLink = generateShortUrl(url("client/jobs/view/" . base64_encode($jobData['id'])), 'client');
                $workerJobViewLink = generateShortUrl(url("worker/jobs/view/" . $jobData['id']), 'worker');
                $teamBtns = generateShortUrl(url("team-btn/" . base64_encode($jobData['uuid'])), 'admin');
                $contactManager = generateShortUrl(url("worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")), 'worker');
                $leaveForWork = generateShortUrl(url("worker/jobs/on-my-way/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")), 'worker');
                $finishJobByWorker = generateShortUrl(url("worker/jobs/finish/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")), 'worker');

                $workerApproveJob = generateShortUrl(
                    isset($workerData['id']) ? url("worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve") : null,
                    'worker'
                );
                $teamSkipComment = generateShortUrl(url("action-comment/" . ($commentData['id'] ?? '')), 'admin');

                $geoAddress = $jobData['property_address']['geo_address'] ?? '';
                $latitude = $jobData['property_address']['latitude'] ?? null;
                $longitude = $jobData['property_address']['longitude'] ?? null;

                if ($latitude && $longitude && $geoAddress) {
                    $encodedAddress = urlencode($geoAddress);
                    $zoom = 17;
                    $googleMapsUrl = "https://www.google.com/maps/place/{$encodedAddress}/@{$latitude},{$longitude},{$zoom}z";
                } else {
                    $googleMapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($geoAddress);
                }

                $googleAddress = generateShortUrl(url($googleMapsUrl), 'worker');
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
            $lng = ((isset($workerData['lng']) ? $workerData['lng'] : $jobData['worker']['lng']) == 'heb' ? 'he' : 'en');

            $addressParts = [];

            if (!empty($jobData['property_address']['geo_address'])) {
                $addressParts[] = $jobData['property_address']['geo_address'];
            }

            if (!empty($jobData['property_address']['apt_no'])) {
                $addressParts[] = 'Apt ' . $jobData['property_address']['apt_no'];
            }

            if (!empty($jobData['property_address']['floor'])) {
                $addressParts[] = 'Floor ' . $jobData['property_address']['floor'];
            }

            if (!empty($jobData['property_address']['city'])) {
                $addressParts[] = $jobData['property_address']['city'];
            }

            if (!empty($jobData['property_address']['zipcode'])) {
                $addressParts[] = $jobData['property_address']['zipcode'];
            }

            $fullAddress = implode(', ', $addressParts);

            $decodedAddress = html_entity_decode($fullAddress, ENT_QUOTES, 'UTF-8');

            // Remove duplicate words (optional)
            $decodedAddress = collect(explode(', ', $decodedAddress))
                ->unique()
                ->implode(', ');

            $translation = $this->translateClient->translate($decodedAddress, [
                'target' => $lng,
            ]);

            $placeholders = [
                ':job_full_address' => $translation['text'],
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
                ':comment_worker_job_link' => $commentsText ? "\n" . $commentLinkText . " " . $workerJobViewLink : '',
                ':client_view_job_link' => $clientJobViewLink ?? '',
                ':team_job_action_link' => $teamJobActionLink ?? '',
                ':job_status' => ucfirst($jobData['status']) ?? '',
                ':client_job_review' => $clientJobsReviewLink ?? '',
                ':content_txt' => isset($eventData['content_data']) ? $eventData['content_data'] : ' ',
                ':rating' => $jobData['rating'] ?? "",
                ':review' => $jobData['review'] ?? "",
                ':job_accept_url' => $workerApproveJob ?? '',
                ':job_contact_manager_link' => $contactManager ?? '',
                ':job_hours' => isset($jobData['jobservice']['duration_minutes'])
                    ? ($jobData['jobservice']['duration_minutes'] / 60)
                    : '',
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

        if (isset($eventData)) {
            $meetingRescheduleLink = generateShortUrl(isset($eventData['id']) ? url("meeting-schedule/" . base64_encode($eventData['id'])) : '');
            $meetingFileUploadLink = generateShortUrl(isset($eventData['id']) ? url("meeting-files/" . base64_encode($eventData['id'])) : '');
            $uploadedFilesLink = generateShortUrl(isset($eventData["file_name"]) ? url("storage/uploads/ClientFiles/" . $eventData["file_name"]) : '', 'admin');
            $meetingAcceptLink = generateShortUrl(isset($eventData['id']) ? url("thankyou/" . base64_encode($eventData['id']) . "/accept") : "");
            $meetingRejectLink = generateShortUrl(isset($eventData['id']) ? url("thankyou/" . base64_encode($eventData['id']) . "/reject") : "");
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


        if (isset($offerData["services"])) {
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
        if ($contractData) {

            if (isset($contractData["contract_id"]) || $contractData["id"]) {
                $teamViewContract = generateShortUrl(isset($contractData['id']) ? url("admin/view-contract/" . $contractData['id'] ?? '') : '', 'admin');
                $createJobLink = generateShortUrl(isset($contractData['id']) ? url("admin/create-job/" . ($contractData['id'] ?? "")) : "", 'admin');
                $clientContractLink = generateShortUrl((isset($contractData['contract_id']) || isset($contractData['unique_hash'])) ? url("work-contract/" . ($contractData['contract_id'] ?? $contractData['unique_hash'])) : '', 'client');
            }

            $placeholders = [
                // ':property_person_name' => $property_person_name  ?? '',
                ':client_contract_link' => $clientContractLink ?? '',
                ':team_contract_link' => $teamViewContract ?? '',
                ':contract_sent_date' => isset($contractData['created_at']) ? Carbon::parse($contractData['created_at'] ?? '')->format('M d Y H:i') : '',
                ':create_job' => $createJobLink ?? '',

            ];
        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceOrderFields($text, $eventData)
    {
        $placeholders = [];
        if ($eventData) {
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
        if ($eventData || $eventData['activity'] || ($eventData['old_worker'] && $eventData['old_job'])) {
            $by = isset($eventData['by']) ? $eventData['by'] : 'client';

            if (isset($eventData)) {
                $workerHearingLink = generateShortUrl(isset($eventData['id']) ? url("hearing-schedule/" . base64_encode($eventData['id'])) : '', 'worker');
                $leadsLink = generateShortUrl(url("admin/leads"), 'admin');
                $offersLink = generateShortUrl(url("admin/offered-price"), 'admin');
                $contractsLink = generateShortUrl(url("admin/contracts"), 'admin');
                $workerLeadsLink = generateShortUrl(url("admin/worker-leads"), 'admin');
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
                ':hearing_date' => !empty($eventData['start_date']) ? Carbon::parse($eventData['start_date'])->format('d-m-Y') : '',
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
                ':leads_link' => $leadsLink ?? '',
                ':offers_link' => $offersLink ?? '',
                ':contracts_link' => $contractsLink ?? '',
                ':worker_leads_link' => $workerLeadsLink ?? '',
                ':pending_lead_count' => $eventData['pending_lead_count'] ?? '',
                ':pending_offer_count' => $eventData['pending_offer_count'] ?? '',
                ':pending_contracts_count' => $eventData['pending_contracts_count'] ?? '',
                ':worker_lead_count' => $eventData['worker_lead_count'] ?? '',
                ':time_interval' => $eventData['time_interval'] ?? '',
                ':job_full_addresses' => $eventData['job_full_addresses'] ?? '',
                ':job_details' => $eventData['job_details'] ?? '',
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
            $attachFile = null;
            $buttons = [];


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

                        if ($lng == "heb") {
                            $title1 = "ראיתי";
                            $title2 = "דבר עם מנהל";
                            // $sid = "HX284d159218aad8b3b04de5ff06238f18";
                        } elseif ($lng == "spa") {
                            $title1 = "He visto el horario";
                            $title2 = "Administrador de contactos";
                            // $sid = "HX38c7043fb5eea7a16534866075c64c90";
                        } elseif ($lng == "ru") {
                            $title1 = "Я видел расписание";
                            $title2 = "Связаться с менеджером";
                            // $sid = "HXf2d268574176b3a56a0b78c5b63ba706";
                        } else {
                            $title1 = "I’ve seen the schedule";
                            $title2 = "Contact manager";
                            // $sid = "HX517f18e3ae6de354515fcdc52becfb28";
                        }

                        $buttons = [
                            [
                                'type' => 'quick_reply',
                                'title' => $title1,
                                'id' => 'seen_schedule',
                                // 'url' => isset($workerData['id']) ? url("worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve") : '',
                            ],
                            [
                                'type' => 'quick_reply',
                                'title' => $title2,
                                'id' => 'contact_manager',
                                // 'url' => url("worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")),
                            ]
                        ];

                        // if($lng == "heb"){
                        //     $sid = "HX284d159218aad8b3b04de5ff06238f18";
                        // }elseif($lng == "spa"){
                        //     $sid = "HX38c7043fb5eea7a16534866075c64c90";
                        // }elseif($lng == "ru"){
                        //     $sid = "HXf2d268574176b3a56a0b78c5b63ba706";
                        // }else{
                        //     $sid = "HX517f18e3ae6de354515fcdc52becfb28";
                        // }

                        // $address = trim($jobData['property_address']['geo_address'] ?? '');
                        // $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        // $address = str_replace(['"', "'"], ' ', $address);

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "3" => $address,
                        //     "4" => "https://maps.google.com?q=" . ($jobData['property_address']['geo_address'] ?? ''),
                        //     "5" => $jobData['jobservice']['job_hour'] ?? '',
                        //     "6" => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                        //     "7" => isset($workerData['id']) ? "worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve" : '',
                        //     "8" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")
                        // ];


                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+". $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         "statusCallback" => config("services.twilio.webhook") . "twilio/status-callback"
                        //     ]
                        // );

                        // \Log::info($twi->sid);
                        // $data = $twi->toArray();
                        // $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_6_PM:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        // if ($lng == "heb") {
                        //     $title1 = "אשר כתובת";
                        //     $title2 = "מנהל אנשי קשר";
                        //     // $sid = "HX2dcd349e3a809d7a1b556eefdfd453a1";
                        // } elseif ($lng == "spa") {
                        //     $title1 = "Aceptar Dirección";
                        //     $title2 = "Administrador de contactos";
                        //     // $sid = "HX51a3e067adb77ea32fb998c384334a8f";
                        // } elseif ($lng == "ru") {
                        //     $title1 = "Подтвердить адрес";
                        //     $title2 = "Менеджер контактов";
                        //     // $sid = "HX83c19b8510fbafd6a5b030a92f881f3e";
                        // } else {
                        //     $title1 = "Accept address";
                        //     $title2 = "Contact manager";
                        //     // $sid = "HXcf6dfdff92e307c16933672410aa8a7a";
                        // }

                        // $buttons = [
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title1,
                        //         'id' => '1',
                        //         'url' => isset($workerData['id']) ? url("worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve") : '',
                        //     ],
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title2,
                        //         'id' => '2',
                        //         'url' => url("worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")),
                        //     ]
                        // ];

                        // if($lng == "heb"){
                        //     $sid = "HX2dcd349e3a809d7a1b556eefdfd453a1";
                        // }elseif($lng == "spa"){
                        //     $sid = "HX51a3e067adb77ea32fb998c384334a8f";
                        // }elseif($lng == "ru"){
                        //     $sid = "HX83c19b8510fbafd6a5b030a92f881f3e";
                        // }else{
                        //     $sid = "HXcf6dfdff92e307c16933672410aa8a7a";
                        // }

                        // $address = trim($jobData['property_address']['geo_address'] ?? '');
                        // $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        // $address = str_replace(['"', "'"], ' ', $address);

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "3" => $address,
                        //     "4" => "https://maps.google.com?q=" . ($jobData['property_address']['geo_address'] ?? ''),
                        //     "5" => $jobData['jobservice']['job_hour'] ?? '',
                        //     "6" => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                        //     "7" => isset($workerData['id']) ? "worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve" : '',
                        //     "8" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")
                        // ];


                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+". $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         "statusCallback" => config("services.twilio.webhook") . "twilio/status-callback"
                        //     ]
                        // );

                        // \Log::info($twi->sid);
                        // $data = $twi->toArray();
                        // $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::REMINDER_TO_WORKER_1_HOUR_BEFORE_JOB_START:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        // if ($lng == "heb") {
                        //     $title1 = "אני בדרך";
                        //     $title2 = "מנהל אנשי קשר";
                        //     // $sid = "HX59e8a79f8d128b7b01b33d0acc76ec27";
                        // } elseif ($lng == "spa") {
                        //     $title1 = "Estoy en camino";
                        //     $title2 = "Gerente de contactos";
                        //     // $sid = "HX6c77c17eaa74ae63b7be720dc92a1437";
                        // } elseif ($lng == "ru") {
                        //     $title1 = "Я уже в пути";
                        //     $title2 = "Связаться с менеджером";
                        //     // $sid = "HX036c7b1ac952fc6cae74c0bffa00f959";
                        // } else {
                        //     $title1 = "I'm on my way";
                        //     $title2 = "Contact Manager";
                        //     // $sid = "HX936e87d929ebfeb60353023160e9a4be";
                        // }

                        // $buttons = [
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title1,
                        //         'id' => '1',
                        //         'url' => url("worker/jobs/on-my-way/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")),
                        //     ],
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title2,
                        //         'id' => '2',
                        //         'url' => url("worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")),
                        //     ]
                        // ];

                        // if($lng == "heb"){
                        //     $sid = "HX59e8a79f8d128b7b01b33d0acc76ec27";
                        // }elseif($lng == "spa"){
                        //     $sid = "HX6c77c17eaa74ae63b7be720dc92a1437";
                        // }elseif($lng == "ru"){
                        //     $sid = "HX036c7b1ac952fc6cae74c0bffa00f959";
                        // }else{
                        //     $sid = "HX936e87d929ebfeb60353023160e9a4be";
                        // }

                        // $address = trim($jobData['property_address']['geo_address'] ?? '');
                        // $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        // $address = str_replace(['"', "'"], ' ', $address);

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                        //     "3" => $address,
                        //     "4" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "5" => "worker/jobs/on-my-way/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        //     "6" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")
                        // ];


                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+". $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         "statusCallback" => config("services.twilio.webhook") . "twilio/status-callback"
                        //     ]
                        // );

                        // \Log::info($twi->sid);
                        // $data = $twi->toArray();
                        // $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_CONFIRMING_ON_MY_WAY:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HXea6defca05c0a344e5f7631750268faa";
                        } elseif ($lng == "spa") {
                            $sid = "HX09160733422044b146ec7b7e983a2c20";
                        } elseif ($lng == "ru") {
                            $sid = "HXa261df15b21b2d46adf68cf5c1440da3";
                        } else {
                            $sid = "HX6ecf04405ebc100c8221158b3d4db89d";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => "worker/jobs/view/" . $jobData['id'],
                            "3" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::WORKER_START_THE_JOB:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        // if ($lng == "heb") {
                        //     $title1 = "לחצו כאן לסיום העבודה";
                        //     $title2 = "צור קשר עם המנהל";
                        //     // $sid = "HX1cc4f8bfec22f2a71cb23071838b9799";
                        // } elseif ($lng == "spa") {
                        //     $title1 = "Haz clic para finalizar";
                        //     $title2 = "Contacta al gerente";
                        //     // $sid = "HXf889705a6e3dad76e0d521fcf41660b3";
                        // } elseif ($lng == "ru") {
                        //     $title1 = "Нажмите, чтобы завершить";
                        //     $title2 = "Связаться с менеджером";
                        //     // $sid = "HX4042d35f763308e61c04660b2f02861a";
                        // } else {
                        //     $title1 = "Click to finish job";
                        //     $title2 = "Contact Manager";
                        //     // $sid = "HXee912fda54b92b8a99523eda74b2ebb0";
                        // }


                        // $buttons = [
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title1,
                        //         'id' => '1',
                        //         'url' => url("worker/jobs/view/" . $jobData['id']),
                        //     ],
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title2,
                        //         'id' => '2',
                        //         'url' => url("worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")),
                        //     ]
                        // ];

                        // $commentsText = "";
                        // if (!empty($jobData['comments'])) {
                        //     foreach ($jobData['comments'] as $comment) {
                        //         if (!empty($comment['comment']) && !empty($comment['name'])) {
                        //             $commentsText .= "- " . $comment['comment'] . " (by " . ($comment['name'] ?? "") . ") \n";
                        //         }
                        //     }
                        // }


                        // $instructions = [
                        //     "en" => "- *Special Instructions:*",
                        //     "heb" => "- *הוראות מיוחדות:*",
                        //     "spa" => "- *Instrucciones especiales:*",
                        //     "rus" => "- *Особые инструкции:*",
                        // ];

                        // $commentInstructions = [
                        //     "en" => "- *Click Here to Confirm Comments are Done*",
                        //     "heb" => "- *לחץ כאן לאישור שהמשימות בוצעו*",
                        //     "spa" => "- *Haga clic aquí para confirmar que las tareas están completadas*",
                        //     "rus" => "- *Нажмите здесь, чтобы подтвердить выполнение задач*",
                        // ];

                        // $currentTime = Carbon::parse($jobData['start_time'] ?? '00:00:00');
                        // $endTime = Carbon::parse($jobData['end_time'] ?? '00:00:00');
                        // $diffInHours = $currentTime->diffInHours($endTime, false);
                        // $diffInMinutes = $currentTime->diffInMinutes($endTime, false) % 60;

                        // if ($lng == "heb") {
                        //     $sid = "HX1cc4f8bfec22f2a71cb23071838b9799";
                        // } elseif ($lng == "spa") {
                        //     $sid = "HXf889705a6e3dad76e0d521fcf41660b3";
                        // } elseif ($lng == "ru") {
                        //     $sid = "HX4042d35f763308e61c04660b2f02861a";
                        // } else {
                        //     $sid = "HXee912fda54b92b8a99523eda74b2ebb0";
                        // }

                        // $specialInstruction = $instructions[isset($workerData['lng']) ? $workerData['lng'] : 'en'] ?? "";
                        // $commentLinkText = $commentInstructions[isset($workerData['lng']) ? $workerData['lng'] : 'en'] ?? "";

                        // $address = trim($jobData['property_address']['geo_address'] ?? '');
                        // $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        // $address = str_replace(['"', "'"], ' ', $address);

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => trim($address),
                        //     "3" => $diffInHours . ':' . str_pad($diffInMinutes, 2, '0', STR_PAD_LEFT),
                        //     "4" => Carbon::today()->setTimeFromTimeString($jobData['end_time'] ?? '00:00:00')->format('H:i'),
                        //     "5" => trim((($workerData['lng'] ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? '')),
                        //     "6" => !empty($commentsText) ? $specialInstruction . " " . trim($commentsText) : '',
                        //     "7" => "worker/jobs/view/" . $jobData['id'],
                        //     "8" => "worker/jobs/" . ($jobData['uuid'] ?? "")
                        // ];


                        // try {
                        //     \Log::info("Sending message via Twilio...");

                        //     $twi = $this->twilio->messages->create(
                        //         "whatsapp:+" . $receiverNumber,
                        //         [
                        //             "from" => $this->twilioWhatsappNumber,
                        //             "contentSid" => $sid,
                        //             "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                        //         ]
                        //     );

                        //     $data = $twi->toArray();
                        //     $isTwilio = true;
                        //     \Log::info("Twilio message sent successfully!");
                        // } catch (\Exception $e) {
                        //     \Log::error("Twilio API Error: " . $e->getMessage());
                        // }

                        break;

                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_AFTER_ALL_COMMENTS_COMPLETED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HXd222d3357b0b612cc1ff525e6ff6629a";
                        } elseif ($lng == "spa") {
                            $sid = "HX478a2737303b0dff4db377a8a1fe09a3";
                        } elseif ($lng == "ru") {
                            $sid = "HXf1e97c07a1fb7a16fe04d82b8eefc991";
                        } else {
                            $sid = "HXd3d13c1e209af3ece911061db1d211f5";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => "worker/jobs/view/" . $jobData['id'],
                            "3" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;

                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_FOR_NEXT_JOB_ON_COMPLETE_JOB:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HXb1b580f3c631bcf941a70e49579028d7";
                        } elseif ($lng == "spa") {
                            $sid = "HX101caae38bf3ba26a0c58aa70c001b6d";
                        } elseif ($lng == "ru") {
                            $sid = "HXae7060a8729e5afdea06a4268f1af91a";
                        } else {
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
                            "5" => "worker/jobs/view/" . $jobData['id'],
                            "6" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_FINAL_NOTIFICATION_OF_DAY:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HX6090f1738d31f9ae6c0d64f5037dccf4";
                        } elseif ($lng == "spa") {
                            $sid = "HX05257c9dd4ca558d421f112a77d90134";
                        } elseif ($lng == "ru") {
                            $sid = "HX2304b0453defc7f0aba9f513a3a23beb";
                        } else {
                            $sid = "HXdab8de810d8e981955d6a1026f74e996";
                        }


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                                ]),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::WORKER_NOTIFY_ON_JOB_TIME_OVER:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        // if ($lng == "heb") {
                        //     $title1 = "סיום עבודה";
                        //     $title2 = "מנהל אנשי קשר";
                        //     // $sid = "HX55b9c36581faa344d780902936739c95";
                        // } elseif ($lng == "spa") {
                        //     $title1 = "Finalizar trabajo";
                        //     $title2 = "Gerente de contactos";
                        //     // $sid = "HXa7cd78cdc0f3d14afb6910c87292f425";
                        // } elseif ($lng == "ru") {
                        //     $title1 = "Завершить работу";
                        //     $title2 = "Связаться с менеджером";
                        //     // $sid = "HX34dfc36515ab7dacaed9a32d1a858eb1";
                        // } else {
                        //     $title1 = "Finish Job";
                        //     $title2 = "Contact Manager";
                        //     // $sid = "HXf05482816e080a968b31ea6a287a4252";
                        // }

                        // $buttons = [
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title1,
                        //         'id' => '1',
                        //         'url' => url("worker/jobs/finish/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")),
                        //     ],
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title2,
                        //         'id' => '2',
                        //         'url' => url("worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : "")),
                        //     ]
                        // ];

                        // if($lng == "heb"){
                        //     $sid = "HX55b9c36581faa344d780902936739c95";
                        // }elseif($lng == "spa"){
                        //     $sid = "HXa7cd78cdc0f3d14afb6910c87292f425";
                        // }elseif($lng == "ru"){
                        //     $sid = "HX34dfc36515ab7dacaed9a32d1a858eb1";
                        // }else{
                        //     $sid = "HXf05482816e080a968b31ea6a287a4252";
                        // }

                        // $address = trim($jobData['property_address']['geo_address'] ?? '');
                        // $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        // $address = str_replace(['"', "'"], ' ', $address);

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => $address,
                        //     "3" => Carbon::today()->setTimeFromTimeString($jobData['end_time'] ?? '00:00:00')->format('H:i'),
                        //     "4" => "worker/jobs/finish/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        //     "5" => "worker/jobs/" . (isset($jobData['uuid']) ? $jobData['uuid'] : ""),
                        // ];


                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+". $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         "statusCallback" => config("services.twilio.webhook") . "twilio/status-callback"
                        //     ]
                        // );

                        // \Log::info($twi->sid);
                        // $data = $twi->toArray();
                        // $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_MONDAY_WORKER_FOR_SCHEDULE:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HX659f2ce678448280c28968bcc7dc2702";
                        } elseif ($lng == "spa") {
                            $sid = "HX6ee778cc861e06acf54f8d9ae05160da";
                        } elseif ($lng == "ru") {
                            $sid = "HX44d7ebaae0914acd6c5cd19587ea959b";
                        } else {
                            $sid = "HX25c30905195cfd2b747818f710e4371f";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::WORKER_FORMS:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        // if ($lng == "heb") {
                        //     $title1 = "בדוק טפסים";
                        // } elseif ($lng == "spa") {
                        //     $title1 = "Consultar formularios";
                        // } elseif ($lng == "ru") {
                        //     $title1 = "Проверить формы";
                        // } else {
                        //     $title1 = "Check Forms";
                        // }

                        // $buttons = [
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title1,
                        //         'id' => '1',
                        //         'url' => "worker-forms/" . base64_encode($workerData['id']),
                        //     ]
                        // ];

                        // if ($lng == "heb") {
                        //     $sid = "HX833a10f4f0e6101d10182ffb93a8307e";
                        // } elseif ($lng == "spa") {
                        //     $sid = "HX4dff3cef86c76ce228a95e84b495036e";
                        // } elseif ($lng == "ru") {
                        //     $sid = "HX54fb9be645633a669c8a71390db936ad";
                        // } else {
                        //     $sid = "HX017483cf367bef17c5c0f42be9ab2214";
                        // }

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => "worker-forms/" . base64_encode($workerData['id'])
                        // ];


                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+" . $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                        //     ]
                        // );

                        // \Log::info($twi->sid);
                        // $data = $twi->toArray();
                        // $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::FORM101:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        // if ($lng == "heb") {
                        //     $sid = "HXe6ed46927c37c23f8c534b8f5a690ecb";
                        // } elseif ($lng == "spa") {
                        //     $sid = "HX5096dcd0811c4b50a3130656d2ca9580";
                        // } elseif ($lng == "ru") {
                        //     $sid = "HX052be35abfed395d402e8c668396a607";
                        // } else {
                        //     $sid = "HX174f6f4e015c00be9803d908471196c5";
                        // }

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => isset($workerData['id'], $workerData['formId'])
                        //         ? "form101/" . base64_encode($workerData['id']) . "/" . base64_encode($workerData['formId'])
                        //         : '',
                        // ];


                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+" . $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                        //     ]
                        // );

                        // \Log::info($twi->sid);
                        // $data = $twi->toArray();
                        // $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::NEW_JOB:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HXa21f75242dad793cda764d2b56e65f6a";
                        } elseif ($lng == "spa") {
                            $sid = "HX8354d3ed3e7912ca8d832623759f61cf";
                        } elseif ($lng == "ru") {
                            $sid = "HX10a8c45cf2302b028323375e0d8cab69";
                        } else {
                            $sid = "HX2dcae427f9cf5ae3377b0a8fa62b49f4";
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
                            "7" => "worker/jobs/view/" . $jobData['id'],
                            "8" => "",
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::WORKER_UNASSIGNED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HX850ed2ea4e4b37effb23ff24d9f0afe6";
                        } elseif ($lng == "spa") {
                            $sid = "HX43a738ad7a192420c93589df04d298d4";
                        } elseif ($lng == "ru") {
                            $sid = "HXccb27cb1e1382fdd7349e67ca67cf3b2";
                        } else {
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
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;

                    case WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_APPROVED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HX24fe788eb0d9689e454597cd5f75855a";
                        } else {
                            $sid = "HX0d47df5629c69747c7ffc6f023333e2f";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => $eventData['refundclaim']['status'] ?? "",
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;

                    case WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE_REJECTED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HX75c211007206b80c6dabed250804a1d7";
                        } else {
                            $sid = "HX3a54285010d608e08f81801b26220d05";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => $eventData['refundclaim']['status'] ?? "",
                            "3" => $eventData['refundclaim']['rejection_comment'] ?? ""
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_WORKER_ONE_WEEK_BEFORE_HIS_VISA_RENEWAL:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HXe9d2a2e986dd67996d53bfe0f6cc140b";
                        } elseif ($lng == "spa") {
                            $sid = "HX6be650cc10b1fc882306a6b9d2e09ba4";
                        } elseif ($lng == "ru") {
                            $sid = "HX704959e6c268569d7d606a05974882c4";
                        } else {
                            $sid = "HXea9186248377b32ff27a1cce7d697feb";
                        }

                        $variables = [
                            "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                            "2" => $workerData['renewal_visa'] ?? "",
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] == "ru" ? "ru" : 'en';

                        // if ($lng == "heb") {
                        //     $sid = "HX0ea9239bbdf99de574c8626b61609590";
                        // } elseif ($lng == "spa") {
                        //     $sid = "HX1997a89a36d6f61c20f7fb9348f998a5";
                        // } elseif ($lng == "ru") {
                        //     $sid = "HXb49025961705979ca2fd1b26f6886fd0";
                        // } else {
                        //     $sid = "HX0ecb9b0a4aa779fca29e227753d32297";
                        // }



                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+" . $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWorkerLeadWhatsappNumber,
                        //         "contentSid" => $sid
                        //     ]
                        // );

                        // \Log::info($twi->sid);
                        // $data = $twi->toArray();
                        // $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::DAILY_REMINDER_TO_LEAD:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        if ($lng == "heb") {
                            $sid = "HX1a3d85d04e28486f060019999b5152a6";
                        } elseif ($lng == "ru") {
                            $sid = "HX238d3c1465b25574acc462421fac5c92";
                        } else {
                            $sid = "HX03827c371075fe94b840c9adfc66b0fa";
                        }



                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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

                        if ($lng == "heb") {
                            $sid = "HX492d26962fe009a4b25157f5fd8bc226";
                        } elseif ($lng == "ru") {
                            $sid = "HX8de41c8b676432f67d3aefd96f7b8648";
                        } else {
                            $sid = "HXa2369d2bfc34c47637bb42c319197ea4";
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWorkerLeadWhatsappNumber,
                                "contentSid" => $sid
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;
                        break;

                    case WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] == "ru" ? "ru" : 'en';

                        // if ($lng == "heb") {
                        //     $sid = "HX7b45e614c0ad4dbe513a37a14b305d04";
                        // } elseif ($lng == "spa") {
                        //     $sid = "HX2010e79fde4a4800f28e90b4d9b5da7b";
                        // } elseif ($lng == "ru") {
                        //     $sid = "HXf866857bc100d0df044be54c9ca3fb31";
                        // } else {
                        //     $sid = "HX61716a714052e9c45181435bb35f8064";
                        // }

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        // ];


                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+" . $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWorkerLeadWhatsappNumber,
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                        //     ]
                        // );

                        // \Log::info($twi->sid);
                        // $data = $twi->toArray();
                        // $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::SEND_WORKER_JOB_CANCEL_BY_TEAM:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        $currentTime = Carbon::parse($jobData['start_time'] ?? '00:00:00');
                        $endTime = Carbon::parse($jobData['end_time'] ?? '00:00:00');
                        $diffInHours = $currentTime->diffInHours($endTime, false);
                        $diffInMinutes = $currentTime->diffInMinutes($endTime, false) % 60;

                        if ($lng == "heb") {
                            $sid = "HX17b075ef1b8e2b5468496fe61ab0d380";
                        } elseif ($lng == "spa") {
                            $sid = "HX6e287b595f61c979046d245a53cdf883";
                        } elseif ($lng == "ru") {
                            $sid = "HXabd4c46aada73e7d1b82471bbfe73ab2";
                        } else {
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
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

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

                        if ($lng == "heb") {
                            $sid = "HXf8c6499b644fe3fe94448eb036e51650";
                        } elseif ($lng == "spa") {
                            $sid = "HX22763afe782277cfb599759e56a2f0a3";
                        } elseif ($lng == "ru") {
                            $sid = "HX75061df5d63a93579f6b02ca63b719f8";
                        } else {
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
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                            ]
                        );


                        $data = $twi->toArray();
                        $isTwilio = true;


                        break;

                    case WhatsappMessageTemplateEnum::SEND_WORKER_TO_STOP_TIMER:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        // if ($lng == "heb") {
                        //     // $sid = "HXa95cc010d40732b58054e5755693ffd7";
                        //     $title = "בדיקת פרטי משרה";
                        // } elseif ($lng == "spa") {
                        //     // $sid = "HX46ebc598f6b41d94aacd1cef2af93c59";
                        //     $title = "Verificar detalles del trabajo";
                        // } elseif ($lng == "ru") {
                        //     // $sid = "HX6b93fe644d388f45d099a44a0ee9d658";
                        //     $title = "Проверить сведения о задании";
                        // } else {
                        //     // $sid = "HX44eed8d310442e71666f3ff8eb9f3a9c";
                        //     $title = "Check Job Details";
                        // }

                        // $buttons = [
                        //     [
                        //         'type' => 'url',
                        //         'title' => $title,
                        //         'id' => '1',
                        //         'url' => url("worker/jobs/view/" . $jobData['id']),
                        //     ]
                        // ];

                        // $address = trim($jobData['property_address']['geo_address'] ?? '');
                        // $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        // $address = str_replace(['"', "'"], ' ', $address);

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => Carbon::parse($jobData['start_date'] ?? "00-00-0000")->format('M d Y'),
                        //     "3" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                        //     "4" => trim((($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? '')),
                        //     "5" => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                        //     "6" => $address,
                        //     "7" => "worker/jobs/view/" . $jobData['id'],
                        // ];


                        // try {
                        //     \Log::info("Sending message via Twilio...");

                        //     $twi = $this->twilio->messages->create(
                        //         "whatsapp:+" . $receiverNumber,
                        //         [
                        //             "from" => $this->twilioWhatsappNumber,
                        //             "contentSid" => $sid,
                        //             "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                        //         ]
                        //     );

                        //     $data = $twi->toArray();
                        //     $isTwilio = true;

                        //     \Log::info("Twilio message sent successfully!");
                        // } catch (\Exception $e) {
                        //     \Log::error("Twilio API Error: " . $e->getMessage());
                        // }

                        break;

                    case WhatsappMessageTemplateEnum::SEND_TO_WORKER_PENDING_FORMS:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        // if ($lng == "heb") {
                        //     $sid = "HX3e4c5c7160088e38eb064cbd6752ec47";
                        // } elseif ($lng == "spa") {
                        //     $sid = "HX4efa1d9982eb2f54a0bb3422b0c9a36e";
                        // } elseif ($lng == "ru") {
                        //     $sid = "HX960ec603e30b1bc3cd4314b90b637713";
                        // } else {
                        //     $sid = "HX9a3675e53007de5cd1c70bca2bdcdbfc";
                        // }

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => "worker-forms/" . base64_encode($workerData['id'])
                        // ];



                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+" . $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                        //     ]
                        // );

                        // $data = $twi->toArray();
                        // $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] == "ru" ? "ru" : 'en';

                        // if ($lng == "heb") {
                        //     $sid = "HXd08482f0b51c466cf4620a07fd63c863";
                        // } elseif ($lng == "spa") {
                        //     $sid = "HX37c26e0fbf3e4c6e7db0cb26d4f0141f";
                        // } elseif ($lng == "ru") {
                        //     $sid = "HX47b7ff605aaf54bcb2081119e865258c";
                        // } else {
                        //     $sid = "HXa378ee35b317a4650073663895786900";
                        // }



                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+" . $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWorkerLeadWhatsappNumber,
                        //         "contentSid" => $sid,

                        //     ]
                        // );
                        // $data = $twi->toArray();

                        // $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] == "ru" ? "ru" : 'en';

                        // if ($lng == "heb") {
                        //     $sid = "HX97f8e43c66e05be83b1542de31e98b73";
                        // } elseif ($lng == "spa") {
                        //     $sid = "HX28f81c7e432890076bc0aa302d5afbb9";
                        // } elseif ($lng == "ru") {
                        //     $sid = "HX91920b97a285a7dc9de4c45d94b522e4";
                        // } else {
                        //     $sid = "HXe7eb20fbbe66441964829cdcab68a468";
                        // }



                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+" . $receiverNumber,
                        //     [
                        //         "from" => $this->twilioWorkerLeadWhatsappNumber,
                        //         "contentSid" => $sid,

                        //     ]
                        // );
                        // $data = $twi->toArray();
                        // $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING:
                    case WhatsappMessageTemplateEnum::WORKER_HEARING_SCHEDULE:
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';

                        $file = $eventData['attach_file_name'] ?? null;
                        if ($file) {
                            $attachFile = storage_path('app/public/' . $file);
                        }

                        // \Log::info($file);
                        // if ($file) {
                        //     $attachFile = asset("storage/" . $file);
                        //     \Log::info($attachFile);
                        // }

                        // if($lng == "heb"){
                        //     $sid = "HX161d40f9cd389eef365117c0d19f0fb6";
                        // }elseif($lng == "spa"){
                        //     $sid = "HXe8f508aa80d12d1b03fdcfa209b63ae5";
                        // }elseif($lng == "ru"){
                        //     $sid = "HXb1591545679bd7fcea367db3848d00e3";
                        // }else{
                        //     $sid = "HX81bf0ac1c8218b1d0b6b339523142c14";
                        // }

                        // $variables = [
                        //     "1" => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                        //     "2" => isset($eventData['team']) && !empty($eventData['team']['name'])
                        //             ? $eventData['team']['name']
                        //             : '',
                        //     "3" => isset($eventData['date']) ? Carbon::parse($eventData['date'])->format('M d Y') : '',
                        //     "4" => date("H:i", strtotime($eventData['start_time'] ?? "00-00")),
                        //     "5" => date("H:i", strtotime($eventData['end_time'] ?? "00-00")),
                        //     "6" => isset($eventData['id']) ? "hearing-schedule/" . base64_encode($eventData['id']) : ''
                        // ];



                        // $twi = $this->twilio->messages->create(
                        //     "whatsapp:+". "918000318833",
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "contentSid" => $sid,
                        //         "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        //         "statusCallback" => "https://efc4-2405-201-2022-10c3-ded9-9a3e-2ed7-5304.ngrok-free.app/twilio/status-callback"

                        //     ]
                        // );
                        // $data = $twi->toArray();
                        // $isTwilio = true;

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
                        break;

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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXf6bb2621f02900daeb0b63cc4b31e374" : "HX5596d68e908a51847aca1e3ac60108a2";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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

                    case WhatsappMessageTemplateEnum::INQUIRY_RESPONSE_LEAD:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        break;

                    case WhatsappMessageTemplateEnum::AFTER_STOP_TO_CLIENT:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX52833dee24b95ac2a6e6a6c408ec26bc" : "HXdb86c20c01c07f4572677175da75154c";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+$receiverNumber",
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

                    case WhatsappMessageTemplateEnum::AFTER_STOP_TO_CLIENT_WHAPI:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        $lng = $clientData['lng'] ?? 'heb';

                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HXf5b5a1c437fdd8ba04498df919a95c57" : "HX2844b528b4f5ececf2c2a633fb3ab5df";

                        $address = isset($propertyAddress) && isset($propertyAddress['address_name']) && !empty($propertyAddress['address_name']) ? $propertyAddress['address_name'] : "NA";

                        $address = json_encode($address, JSON_UNESCAPED_UNICODE);
                        $address = str_replace(['"', "'"], ' ', $address);

                        $variables = [
                            "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                            "2" => isset($eventData['start_date']) ? Carbon::parse($eventData['start_date'])->format('d-m-Y') : '',
                            "3" => isset($eventData['start_time']) ? date("H:i", strtotime($eventData['start_time'])) : '',
                            "4" => isset($eventData['end_time']) ? date("H:i", strtotime($eventData['end_time'])) : '',
                            "5" => trim($address),
                            "6" => $purpose ? $purpose : '',
                            "7" => isset($eventData['id']) ? "meeting-schedule/" . base64_encode($eventData['id']) : '',
                            "8" => isset($eventData['id']) ? "meeting-files/" . base64_encode($eventData['id']) : ''
                        ];

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXe75478323d1897dfe660aa0efd68afa8" : "HX7a0c41761ad3a45cad1d68c64132afb3";


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX7c7ebf4918b9d70c9d8d54b605d6869c" : "HX1e69e897935c69355e757a0c9ab6fc92";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXcd415330409802536916a5a0d7d7c64c" : "HXc483bf4d5368b797cb1348e771453460";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX7d9934dd573d787c256c4c6895db1be6" : "HX25228efdbe967b43697f7fa3f1aec959";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXfad16bdb4d3824122222f6ec0d554392" : "HX7bb23b6f6a7a05ada325608f0d61ef97";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HXc426082cce146b5a3fcabbfbbc71b7d6" : "HX0f435a73787206064bbd017964c0aff6";

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
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::UNANSWERED_LEAD:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX21394a2e7d50529efe397629f74c02cc" : "HX4426be78542a3b79ad4e47d77747423a";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::PAST:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXf7156d7ae827e519d663be86048c46fe" : "HX704c95d691f1fc99e269a35c0d81c05d";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        \Log::info("FOLLOW_UP_ON_OUR_CONVERSATION");
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX6479d8fbd0c980f54d5cc783a514de0f" : "HX01315524ec8acad1bbd75436bd0a9dd7";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? ''),
                                ]),
                                "statusCallback" => "https://2a3f-2405-201-2022-10c3-1bae-5bc7-9144-43e2.ngrok-free.app/status-callback"

                            ]
                        );

                        \Log::info($twi);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_CLIENT_FOR_TOMMOROW_MEETINGS:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX9ca3ce938fb8e84c6444208fed4e6cc1" : "HXe4b7a2b9fbe06fbd8cee946c5034e312";

                        $variables = [
                            "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                            "2" => Carbon::parse($eventData['start_date'] ?? "00-00-0000")->format('M d Y') . " " .  ($eventData['start_time'] ?? ''),
                            "3" => $eventData["meet_link"] ?? "",
                            "4" => isset($eventData['id']) ? "meeting-schedule/" . base64_encode($eventData['id']) : '',
                            "5" => isset($eventData['id']) ? "meeting-files/" . base64_encode($eventData['id']) : ''
                        ];


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }

                        $propertyAddress = $eventData['property_address'] ?? null;

                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX415bf1b37ef5073097aa2fb19674089e" : "HX03f556cd581d9ee30eafb1a434a23a9d";

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
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_NOT_IN_SYSTEM_OR_NO_OFFER:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => "HXc8ccae82c125e06ff7f030d653a42b58",
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_HAS_OFFER_BUT_NO_SIGNED_OR_NO_CONTRACT:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => "HX37b9ce6142bcd1f316ee043460a5342f",
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_1_DAY:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX8a685c980a510a7b278fc265f0288cbe" : "HXdc2ab379e4b93829fba65c4d2ed16fec";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_3_DAYS:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX32f076e8f30f12cea3821b4d1c76a6a8" : "HX8f1f514ac4f6223ebe16ef22ebd66ad9";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_4_DAYS:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXd63618ac0eabed00076b272556f14b2f" : "HX4856598b33204fc97b32520d2d0c54d7";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                            ]
                        );
                        $isTwilio = true;
                        $data = $twi->toArray();
                        \Log::info($twi->sid);

                        break;

                    case WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_CLIENT:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX06bf4c9fe0dfd0271e428d0fbe6cc674" : "HX08c50d92acabf7489cf41aa62a2f70f0";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                    "2" => $eventData['activity']['reschedule_date'] ?? '',
                                    "3" => $eventData['activity']['reschedule_time'] ?? '',
                                    "4" => "thankyou/" . base64_encode($clientData['id']) . "/change_call"
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CONTACT_ME_TO_RESCHEDULE_THE_MEETING_CLIENT:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXbc9e464f7aeb7fb3a0b7ddd0cb1f13c4" : "HXaa623e74462fd8c47fcd5e7217879d7e";


                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;


                    case WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED_TO_CLIENT:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = isset($clientData['contact_person_phone']) ? $clientData['contact_person_phone'] : $clientData['phone'] ?? null;
                        $property_person_name = isset($clientData['contact_person_name']) ? $clientData['contact_person_name'] : trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null;

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX0f881391f84de7e6f7e6ecefb7d39b79" : "HX6c29549b8ef911fa12e24b5e564c5e94";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                    "2" => $eventData['card']['card_number'] ?? '',
                                    "3" => "client/settings"
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::WEEKLY_CLIENT_SCHEDULED_NOTIFICATION: //done
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXa6e44032033c312788542917ae418814" : "HX0a72361a6fab0f2638e1f356185956e9";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                    "2" => "client/jobs"
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::UPDATE_ON_COMMENT_RESOLUTION: //done
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;

                        Log::info($receiverNumber);
                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXaa24f00d053af3783dfa0b909c011024" : "HXc8daa6f607d4272d67b29c541e32fe20";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')) ?? null,
                                    "2" => (($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? ''),
                                    "3" => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                                    "4" => "client/jobs/view/" . base64_encode($jobData['id'])
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::OFFER_PRICE: //done
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HXb6e5cc838db80bceb45fdd82cf40e554" : "HX145bf177046bc6a61737f70371de196d";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name ?? '',
                                    "2" => $offerData['service_names'] ?? '',
                                    "3" => "price-offer/" . base64_encode($offerData['id'])
                                ]),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER_SENT_CLIENT: //done
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HX1dbaaf43664f0afe089e01a656f521ae" : "HXb5cc24fa86df27e3e70ba3ebf9ccd8f6";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HX16c5aa3bc5897d5c90697b8e2c0cdb1b" : "HX2259d760f26db145e5c841b782ec6e5f";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => $property_person_name,
                                    "2" => isset($contractData['created_at']) ? Carbon::parse($contractData['created_at'] ?? '')->format('M d Y H:i') : '',
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HX173db9ece76db28630dd2d641d591d01" : "HX2f086178179402d5e6b0ca3c75785de6";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        if (in_array($clientData['id'], [88, 21, 1660])) {
                            $sid = $lng == "heb" ? "HX1065d9f2b2d6424a17367a1ff72c709e" : "HX03fd91b1d53e8e3833efbc7476c39f70";
                        } else {
                            $sid = $lng == "heb" ? "HX3885b7a64e902bfdd02c790bad694721" : "HXe4a6b7fdb4e5a82f55003f9956a9f169";
                        }

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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



                        $sid = $lng == "heb" ? "HXb1d2ed5e977018e2488d428531d48389" : "HX9391bb3e7f827f0b24813d17bccfc00b";

                        $variables = [
                            "1" => $property_person_name,
                            "2" => (($lng ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? ''),
                            "3" => Carbon::parse($jobData['start_date'] ?? "00-00-0000")->format('M d Y'),
                            "4" => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                            "5" => "client/jobs/view/" . base64_encode($jobData['id'])
                        ];

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode($variables, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            ]
                        );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED: //done
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HXc632938113f6ceccf9244a970b0bc078" : "HXd8a8f402984ddec0473666b7a24a8ca6";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HX635f65014d14b31877ebdabf86b31464" : "HX17a4cbb43cd201be896f958d7486c018";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HX0bc765375b88af4339ae184e6786dbb5" : "HX18f4f53c412e97618f6fb1df4648f5e3";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $sid = $lng == "heb" ? "HX64602821f84177f06957a7c14791280a" : "HX9d6876b57d72631a4c2f220752224567";

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
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

                    case WhatsappMessageTemplateEnum::MESSAGE_SEND_TO_CLIENT_AFTER_SIGNED_CONTRACT:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HX55a553d9f94cf69f2b44ef965cfd307b" : "HX3e7a4c59e65d4c8b3f183dba4968235c";
                        $mediaUrl = 'pdfs/BroomServiceEnglish.pdf';

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')),
                                    "2" => "?lng=" . ($clientData['lng'] == "heb" ? "he" : "en") . "&fname=" . urlencode($clientData['firstname']) .
                                        "&lname=" . urlencode($clientData['lastname']) .
                                        "&phone=" . urlencode($clientData['phone']) .
                                        "&email=" . urlencode($clientData['email']) .
                                        "&name_on_invoice=" . urlencode($clientData['invoicename'] ?? ($clientData['firstname'] . " " . $clientData['lastname'])),
                                    "3" => $mediaUrl
                                ]),
                                "statusCallback" => "https://db30-2405-201-2022-10c3-4427-3383-232a-3697.ngrok-free.app/twilio/status-callback"
                            ]
                        );

                        // $twiImage = $this->twilio->messages->create(
                        //     "whatsapp:+" . "918000318833",
                        //     [
                        //         "from" => $this->twilioWhatsappNumber,
                        //         "contentSid" => $lng == "heb" ? "HX7fb7a0d35ad44fd5efe7562fc2433e96" : "HX7fb7a0d35ad44fd5efe7562fc2433e96",
                        //         "statusCallback" => "https://db30-2405-201-2022-10c3-4427-3383-232a-3697.ngrok-free.app/twilio/status-callback"
                        //     ]
                        // );

                        \Log::info($twi->sid);
                        $data = $twi->toArray();
                        $isTwilio = true;

                        break;

                    case WhatsappMessageTemplateEnum::MESSAGE_SEND_TO_CLIENT_AFTER_VERIFYED_CONTRACT:
                        if (isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1) {
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

                        $lng = $clientData['lng'] ?? 'heb';

                        $sid = $lng == "heb" ? "HXf7367faa67d6b59479b35a5720d9f08b" : "HX70a33ef64b717cde74a757f5c1716b83";

                        $pdfLink = generateShortUrl("https://crm.broomservice.co.il/" . $lng == "heb" ? "pdfs/BroomServiceHebrew.pdf" : "pdfs/BroomServiceEnglish.pdf", 'client');

                        $twi = $this->twilio->messages->create(
                            "whatsapp:+" . $receiverNumber,
                            [
                                "from" => $this->twilioWhatsappNumber,
                                "contentSid" => $sid,
                                "contentVariables" => json_encode([
                                    "1" => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')),
                                    "2" => $pdfLink
                                ]),
                                "statusCallback" => "https://db30-2405-201-2022-10c3-4427-3383-232a-3697.ngrok-free.app/twilio/status-callback"
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

            // $receiverNumber = "918000318833";

            if ($receiverNumber && $text) {
                Log::info('SENDING WA to ' . $receiverNumber);
                Log::info($text);
                Log::info($eventType);

                $token = $this->whapiApiToken;

                if ($receiverNumber == config('services.whatsapp_groups.relevant_with_workers')) {
                    $token = $this->whapiWorkerApiToken;
                } else if ($eventType == WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE || $eventType == WhatsappMessageTemplateEnum::NOTIFY_MONDAY_WORKER_FOR_SCHEDULE) {
                    $token = $this->whapiClientApiToken;
                } else if (
                    $eventType == WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_5_PM ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_NEXT_DAY_JOB_REMINDER_AT_6_PM ||
                    $eventType == WhatsappMessageTemplateEnum::REMINDER_TO_WORKER_1_HOUR_BEFORE_JOB_START ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_NOTIFY_ON_JOB_TIME_OVER ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_START_THE_JOB ||
                    $eventType == WhatsappMessageTemplateEnum::SEND_WORKER_TO_STOP_TIMER
                ) {
                    $token = $this->whapiWorkerJobApiToken;
                } else if (
                    $eventType == WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT ||
                    $eventType == WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD ||
                    $eventType == WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED ||
                    $eventType == WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM ||
                    $eventType == WhatsappMessageTemplateEnum::NEW_LEAD_HIRIED_TO_TEAM
                ) {
                    $token = $this->whapiWorkerApiToken;
                } else {
                    $token = $this->whapiApiToken;
                }

                if (!$isTwilio) {
                    \Log::info("Sending message $isTwilio");

                    $type = !empty($buttons) ? 'messages/interactive' : 'messages/text';

                    $payload = [
                        'to' => $receiverNumber,
                    ];

                    // Add 'body' and 'action' conditionally
                    if (!empty($buttons)) {
                        $payload['type'] = 'button'; // Required for interactive
                        $payload['body'] = ['text' => $text];
                        $payload['action'] = ['buttons' => $buttons];
                    } else {
                        $payload['body'] = $text;
                    }

                    $response = Http::withToken($token)
                        ->withHeaders([
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                        ])
                        ->post($this->whapiApiEndpoint . $type, $payload);


                    if (in_array($eventType, [
                        WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT,
                        WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD,
                        WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED,
                        WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM,
                        WhatsappMessageTemplateEnum::WORKER_FORMS,
                        WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING
                    ])) {
                        StoreWorkerWebhookResponse($text, $receiverNumber, $data);
                    } else {
                        StoreWebhookResponse($text, $receiverNumber, $data);
                    }

                    if ($attachFile) {
                        sendWhatsappFileMessage(
                            $receiverNumber,
                            $attachFile,
                            ''
                        );
                    }

                    \Log::info($response->json());
                } else {
                    if (in_array($eventType, [
                        WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT,
                        WhatsappMessageTemplateEnum::TEAM_WILL_THINK_SEND_TO_WORKER_LEAD,
                        WhatsappMessageTemplateEnum::NEW_LEAD_HIRING_ALEX_REPLY_UNANSWERED,
                        WhatsappMessageTemplateEnum::WORKER_LEAD_NOT_RELEVANT_BY_TEAM,
                        WhatsappMessageTemplateEnum::WORKER_FORMS,
                        WhatsappMessageTemplateEnum::WORKER_LEAD_FORMS_AFTER_HIRING
                    ])) {
                        StoreWorkerWebhookResponse($data['body'] ?? $text, $receiverNumber, $data);
                    } else {
                        StoreWebhookResponse($data['body'] ?? $text, $receiverNumber, $data);
                    }

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
