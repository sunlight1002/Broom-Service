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


            // Concatenate all addresses into a single string, separated by a comma
            $fullAddress = implode(', ', $addresses);

            // Replaceable values
            $placeholders = [
                ':client_name' => trim(trim($clientData['firstname'] ?? '') . ' ' . trim($clientData['lastname'] ?? '')),
                ':client_contact' => '+' . ($clientData['phone'] ?? ''),
                ':service_requested' => '',
                ':client_email' => $clientData['email'] ?? '',
                ':client_address' => $fullAddress ?? "NA",
                ':lead_detail_link' => isset($clientData['id']) ? url("admin/leads/view/" . $clientData['id'] ?? '') : '',
                ':client_phone_number' => '+' . ($clientData['phone'] ?? ''),
                ':reason' => $clientData['reason'] ?? __('mail.wa-message.lead_declined_contract.no_reason_provided'),
                ':inquiry_date' => Carbon::now()->format('M d Y'),
                ':client_create_date' => isset($clientData['created_at']) ? Carbon::parse($clientData['created_at'])->format('M d Y H:i') : '',
                ':lead_detail_url' => url("admin/leads/view/" . $clientData['id'] ?? ''),
                ':client_jobs' => url("client/jobs"),
                ':client_detail_url' => url("admin/clients/view/" . $clientData['id'] ?? ''),
                ':request_change_schedule' => url("/request-to-change/" .  base64_encode($clientData['id']). "?type=client" ?? ''),
                ':request_details' => isset($eventData['request_details']) ? $eventData['request_details'] : '',
                ':new_status' => $eventData['new_status'] ?? '',
                ':testimonials_link' => url('https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl'),
                ':broom_brochure' => $clientData['lng'] == "en" ? url("pdfs/BroomServiceEnglish.pdf") : url("pdfs/BroomServiceHebrew.pdf"),
                ':admin_add_client_card' => url("admin/clients/view/" .$clientData['id'] ."?=card"),
                ':client_card' => url("client/settings"),
            ];

        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceWorkerFields($text, $workerData, $eventData)
    {
        $placeholders = [];
        if(isset($workerData) && !empty($workerData)) {
            $placeholders = [
                ':worker_name' => trim(trim($workerData['firstname'] ?? '') . ' ' . trim($workerData['lastname'] ?? '')),
                ':worker_phone_number' => '+' . ($workerData['phone'] ?? ''),
                ':request_change_schedule' => url("/request-to-change/" .  (base64_encode($workerData['id']) . "?type=worker") ?? ''),
                ':request_details' => isset($eventData['request_details']) ? $eventData['request_details'] : '',
                ':last_work_date' => $workerData['last_work_date'] ?? '',
                ':date' => isset($eventData['date']) ? Carbon::parse($eventData['date'])->format('M d Y') : '',
                ':check_form' => url("worker-forms/" . base64_encode($workerData['id'])) ?? '',
                ':form_101_link' => isset($workerData['id'], $workerData['formId'])
                    ? url("form101/" . base64_encode($workerData['id']) . "/" . base64_encode($workerData['formId']))
                    : '',
                ':refund_rejection_comment' => $eventData['refundclaim']['rejection_comment'] ?? "",
                ':refund_status' => $eventData['refundclaim']['status'] ?? "",
                ':visa_renewal_date' => $workerData['renewal_visa'] ?? "",
                ':worker_detail_url' => url("workers/view/" .($workerData['id'] ?? '')),
            ];
        }
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceJobFields($text, $jobData,$eventData, $workerData = null, $commentData = null)
    {
        $placeholders = [];
        if(isset($jobData) && !empty($jobData)) {
            $commentsText = "";
            if (!empty($jobData['comments'])) {
                foreach ($jobData['comments'] as $comment) {
                    $commentsText .= "- " . $comment['comment'] . " (by " . $comment['name'] . ") \n";
                }
            }

            $currentTime = Carbon::parse($jobData['start_time'] ?? '00:00:00');
            $endTime = Carbon::parse($jobData['end_time'] ?? '00:00:00');
            $diffInHours = $currentTime->diffInHours($endTime, false);
            $diffInMinutes = $currentTime->diffInMinutes($endTime, false) % 60;

            $placeholders = [
                ':job_full_address' => $jobData['property_address']['geo_address'] ?? '',
                ':job_start_date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00')->format('H:i'),
                ':job_start_date' => Carbon::parse($jobData['start_date'] ?? "00-00-0000")->format('M d Y'),
                ':job_start_time' => Carbon::today()->setTimeFromTimeString($jobData['start_time'] ?? '00:00:00')->format('H:i'),
                ':job_end_time' => Carbon::today()->setTimeFromTimeString($jobData['end_time'] ?? '00:00:00')->format('H:i'),
                ':job_remaining_hours' => $diffInHours . ':' . $diffInMinutes,
                ':job_comments' => $commentsText,
                ':team_skip_comment_link' => url("action-comment/" . ($commentData['id'] ?? '')),
                ':job_service_name' => (($workerData['lng'] ?? 'heb') == 'heb' && isset($jobData['jobservice'])) ? $jobData['jobservice']['heb_name'] : ($jobData['jobservice']['name'] ?? ''),
                ':team_job_link' => url("admin/jobs/view/" . $jobData['id']),
                ':team_action_btns_link' => url("team-btn/" . base64_encode($jobData['id'])),
                ':worker_job_link' => url("worker/jobs/view/" . $jobData['id']),
                ':client_view_job_link' => url("client/jobs/view/" . base64_encode($jobData['id'])),
                ':team_job_action_link' => url("admin/jobs/" . $jobData['id'] . "/change-worker"),
                ':job_status' => ucfirst($jobData['status']) ?? '',
                ':client_job_review' => url("client/jobs/" . base64_encode($jobData['id']) . "/review") ?? '',
                ':content_txt' => isset($eventData['content_data']) ? $eventData['content_data'] : ' ',
                ':rating' => $jobData['rating'] ?? "",
                ':review' => $jobData['review'] ?? "",
            ];

        }
        if(isset($jobData) && !empty($jobData) ) {
            $placeholders = array_merge($placeholders, [
                ':job_accept_url' => isset($workerData['id']) ?? url("worker/" . base64_encode($workerData['id']) . "/jobs" . "/" . base64_encode($jobData['id']) . "/approve"),
                ':job_contact_manager_link' => url("worker/jobs/view/" . $jobData['id']."?q=contact_manager"),
            ]) ;
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

        $placeholders = [
            ':meeting_team_member_name' => isset($eventData['team']) && !empty($eventData['team']['name'])
                ? $eventData['team']['name']
                : ' ',
            ':meeting_date_time' => Carbon::parse($eventData['start_date'] ?? "00-00-0000")->format('M d Y') . " " .  ($eventData['start_time'] ?? ''),
            ':meeting_start_time' => isset($eventData['start_time']) ? date("H:i", strtotime($eventData['start_time'])) : '',
            ':meeting_end_time' => isset($eventData['end_time']) ? date("H:i", strtotime($eventData['end_time'])) : '',
            ':meeting_address' => $address ?? '',
            ':meeting_purpose' => $purpose ? $purpose : "",
            ':meeting_reschedule_link' => isset($eventData['id']) ? url("meeting-schedule/" . base64_encode($eventData['id'])) : '',
            ':meeting_date' => isset($eventData['start_date']) ? Carbon::parse($eventData['start_date'])->format('d-m-Y') : '',
            ':meeting_file_upload_link' => isset($eventData['id']) ? url("meeting-files/" . base64_encode($eventData['id'])) : '',
            ':meeting_uploaded_file_url' => isset($eventData["file_name"]) ? url("storage/uploads/ClientFiles/" . $eventData["file_name"]) : '',
            ':file_upload_date' => $eventData["file_upload_date"] ?? '',
            ':meet_link' => $eventData["meet_link"] ?? "",
            ':today_tommarow_or_date' => $todayTomorrowOrDate,
            ':meeting_accept' => isset($eventData['id']) ? url("thankyou/".base64_encode($eventData['id'])."/accept") : "",
            ':meeting_reject' => isset($eventData['id']) ? url("thankyou/".base64_encode($eventData['id'])."/reject") : "",
            ':all_team_meetings' => $eventData['all_meetings'] ?? "",
        ];

        // Replace placeholders with actual values
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function replaceOfferFields($text, $offerData)
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

        $placeholders = [];
        if ($offerData) {
            $placeholders = [
                ':offer_service_names' => $offerData['service_names'] ?? '',
                ':offer_pending_since' => $offerData['offer_pending_since'] ?? '',
                ':offer_detail_url' => isset($offerData['id']) ? url("admin/offered-price/edit/" . ($offerData['id'] ?? '')) : '',
                ':client_price_offer_link' => isset($offerData['id']) ? url("price-offer/" . base64_encode($offerData['id'])) : '',
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
            $placeholders = [
                ':client_contract_link' => isset($contractData['id']) ? url("work-contract/" . $contractData['id'] ?? '') : '',
                ':team_contract_link' => isset($contractData['id']) ? url("admin/view-contract/" . $contractData['id'] ?? '') : '',
                ':contract_sent_date' => isset($contractData['created_at']) ? Carbon::parse($contractData['created_at']?? '')->format('M d Y H:i') : '',
                ':create_job' => isset($contractData['id']) ? url("admin/create-job/" . ($contractData['id'] ?? "")) : " ",

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
            $placeholders = [
               ':team_name' => isset($eventData['team']) && !empty($eventData['team']['name'])
                                ? $eventData['team']['name']
                                : ' ',
                ':date'          => Carbon::parse($eventData['start_date']?? "00-00-0000")->format('d-m-Y'),
                ':start_time'    => date("H:i", strtotime($eventData['start_time'] ?? "00-00")),
                ':end_time'      => date("H:i", strtotime($eventData['end_time'] ?? "00-00")),
                ':purpose'       => $eventData['purpose'] ?? "No purpose provided",
                ':worker_hearing' => isset($eventData['id']) ? url("hearing-schedule/" . base64_encode($eventData['id'])) : '',
                ':old_job_start_date' => Carbon::parse($eventData['old_job']['start_date'] ?? "00-00-0000")->format('M d Y'),
                ':client_name' => ($eventData['job']['client']['firstname'] ?? '') . ' ' . ($eventData['job']['client']['lastname'] ?? ''),
                ':old_worker_service_name' => ($eventData['old_worker']['lng'] ?? 'en') == 'heb'
                    ? (($eventData['job']['jobservice']['heb_name'] ?? '') . ', ')
                    : (($eventData['job']['jobservice']['name'] ?? '') . ', '),
                ':old_job_start_time' => Carbon::parse($eventData['old_job']['start_time'] ?? "00:00")->format('H:i'),
                ':comment' => $by == 'client'
                    ? ("Client changed the Job status to " . ucfirst($jobData['status'] ?? "") . "."
                    . (isset($jobData['cancellation_fee_amount']) ?
                        (" With Cancellation fees " . $jobData['cancellation_fee_amount'] . " ILS.") : " "))
                    : ("Job is marked as " . ucfirst($jobData['status'] ?? "")),
                ':admin_name' => $eventData['admin']['name'] ?? '',
                ':came_from' => $eventData['type'] ?? '',
                ':reschedule_call_date' => $eventData['activity']['reschedule_date'] ?? '',
                ':reschedule_call_time' => $eventData['activity']['reschedule_time'] ?? '',

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
                        $receiverNumber = $workerData['phone'] ?? null;
                        $lng = $workerData['lng'] ?? 'heb';
                        break;

                    case WhatsappMessageTemplateEnum::TEAM_JOB_NOT_APPROVE_REMINDER_AT_6_PM:
                    case WhatsappMessageTemplateEnum::TEAM_JOB_NOT_CONFIRM_BEFORE_30_MINS:
                    case WhatsappMessageTemplateEnum::TEAM_JOB_NOT_CONFIRM_AFTER_30_MINS:
                    case WhatsappMessageTemplateEnum::WORKER_CONTACT_TO_MANAGER:
                    case WhatsappMessageTemplateEnum::WORKER_NOT_FINISHED_JOB_ON_TIME:
                        $receiverNumber = config('services.whatsapp_groups.problem_with_workers');
                        $lng = 'heb';
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

                    case WhatsappMessageTemplateEnum::UPDATE_ON_COMMENT_RESOLUTION:
                    case WhatsappMessageTemplateEnum::CLIENT_MEETING_SCHEDULE:
                    case WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST:
                    case WhatsappMessageTemplateEnum::DELETE_MEETING:
                    case WhatsappMessageTemplateEnum::OFFER_PRICE:
                    case WhatsappMessageTemplateEnum::FOLLOW_UP_PRICE_OFFER_SENT_CLIENT:
                    case WhatsappMessageTemplateEnum::NOTIFY_TO_CLIENT_CONTRACT_NOT_SIGNED:
                    case WhatsappMessageTemplateEnum::OFF_SITE_MEETING_REMINDER_TO_CLIENT:
                    case WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE:
                    case WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT:
                    case WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS:
                    case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT:
                    case WhatsappMessageTemplateEnum::CONTRACT:
                    case WhatsappMessageTemplateEnum::CREATE_JOB:
                    case WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED:
                    case WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER:
                    case WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION:
                    case WhatsappMessageTemplateEnum::UNANSWERED_LEAD:
                    case WhatsappMessageTemplateEnum::INQUIRY_RESPONSE:
                    case WhatsappMessageTemplateEnum::PAST:
                    case WhatsappMessageTemplateEnum::WEEKLY_CLIENT_SCHEDULED_NOTIFICATION:
                    case WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION:
                    case WhatsappMessageTemplateEnum::NOTIFY_CLIENT_FOR_TOMMOROW_MEETINGS:
                    case WhatsappMessageTemplateEnum::ADMIN_RESCHEDULE_MEETING:
                    case WhatsappMessageTemplateEnum::AFTER_STOP_TO_CLIENT:
                    case WhatsappMessageTemplateEnum::CLIENT_NOT_IN_SYSTEM_OR_NO_OFFER:
                    case WhatsappMessageTemplateEnum::CLIENT_HAS_OFFER_BUT_NO_SIGNED_OR_NO_CONTRACT:
                    case WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED_TO_CLIENT:
                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_3_DAYS:
                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_7_DAYS:
                    case WhatsappMessageTemplateEnum::NOTIFY_UNANSWERED_AFTER_8_DAYS:
                    case WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_CLIENT:
                        if(isset($clientData['disable_notification']) && $clientData['disable_notification'] == 1){
                            \Log::info("client disable notification");
                            return;
                        }
                        $receiverNumber = $clientData['phone'] ?? null;
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
                    case WhatsappMessageTemplateEnum::NOTIFY_TEAM_ONE_WEEK_BEFORE_WORKER_VISA_RENEWAL:
                    case WhatsappMessageTemplateEnum::CLIENT_MEETING_CANCELLED:
                    case WhatsappMessageTemplateEnum::RESCHEDULE_CALL_FOR_TEAM:
                    case WhatsappMessageTemplateEnum::NOTIFY_TO_TEAM_CONTRACT_NOT_SIGNED:
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
                $text = $this->replaceOfferFields($text, $offerData);
                $text = $this->replaceContractFields($text, $contractData, $eventData);
                $text = $this->replaceOrderFields($text, $eventData);
                $text = $this->replaceOtherFields($text, $eventData);

            } else {
                switch ($eventType) {

                    // case WhatsappMessageTemplateEnum::CLIENT_MEETING_REMINDER:
                    //     $receiverNumber = $eventData['phone'];
                    //     App::setLocale($eventData['lng']);

                    //     $propertyAddress = $eventData['property_address'];
                    //     if ($eventData['purpose'] == "Price offer") {
                    //         $eventData['purpose'] =  trans('mail.meeting.price_offer');
                    //     } else if ($eventData['purpose'] == "Quality check") {
                    //         $eventData['purpose'] =  trans('mail.meeting.quality_check');
                    //     } else {
                    //         $eventData['purpose'] = $eventData['purpose'];
                    //     }

                    //     $address = isset($propertyAddress) && isset($propertyAddress['address_name']) && !empty($propertyAddress['address_name']) ? $propertyAddress['address_name'] : "NA";

                    //     $text = __('mail.wa-message.client_meeting_reminder.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => $eventData['firstname'] . ' ' . $eventData['lastname']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_meeting_schedule.content', [
                    //         'date'          => Carbon::parse($eventData['start_date'])->format('d-m-Y'),
                    //         'start_time'    => date("H:i", strtotime($eventData['start_time'])),
                    //         'end_time'      => date("H:i", strtotime($eventData['end_time'])),
                    //         'address'       => $address,
                    //         'purpose'       => $eventData['purpose'] ? $eventData['purpose'] : " "
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.accept_reject') . ": " . url("meeting-schedule/" . base64_encode($eventData['id']));

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.upload_file') . ": " . url("meeting-files/" . base64_encode($eventData['id']));

                    //     break;


                    // case WhatsappMessageTemplateEnum::CONTRACT:
                    //     \Log::info($eventData);
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = $clientData['phone'];
                    //     App::setLocale($clientData['lng']);

                    //     $text = __('mail.wa-message.contract.header', [
                    //         'id' => $eventData['id']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname']))
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.contract.content');

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.check_contract') . ": " . url("work-contract/" . $eventData['contract_id']);

                    //     break;

                    // case WhatsappMessageTemplateEnum::CLIENT_JOB_UPDATED:
                    //     $jobData = $eventData['job'];
                    //     $clientData = $jobData['client'];

                    //     $receiverNumber = $clientData['phone'];

                    //     App::setLocale($clientData['lng']);

                    //     $text = __('mail.wa-message.client_job_updated.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => $clientData['firstname']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_job_updated.content', [
                    //         'date' => Carbon::parse($jobData['start_date'])->format('M d Y'),
                    //         'service_name' => $clientData['lng'] == 'heb'
                    //             ? $jobData['jobservice']['heb_name']
                    //             : $jobData['jobservice']['name'],
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.review') . ": " . url("client/jobs/" . base64_encode($jobData['id']) . "/review");

                    //     break;

                    // case WhatsappMessageTemplateEnum::CREATE_JOB:

                    //     $jobData = $eventData['job'];
                    //     $clientData = $jobData['client'];

                    //     $receiverNumber = $clientData['phone'];
                    //     App::setLocale($clientData['lng']);

                    //     $text = __('mail.wa-message.create_job.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => $clientData['firstname']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.create_job.content', [
                    //         'date' => Carbon::parse($jobData['start_date'])->format('M d Y'),
                    //         'service_name' => $clientData['lng'] == 'heb'
                    //             ? $jobData['jobservice']['heb_name']
                    //             : $jobData['jobservice']['name'],
                    //         'time' => $jobData['start_time']??''
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.review') . ": " . url("client/jobs/" . base64_encode($jobData['id']) . "/review");

                    //     $text .= __('mail.wa-message.create_job.signature');

                    //     break;

                    // case WhatsappMessageTemplateEnum::FORM101:
                    //     $workerData = $eventData['worker'];

                    //     $receiverNumber = $workerData['phone'];
                    //     App::setLocale($workerData['lng']);

                    //     $text = __('mail.wa-message.form101.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => $workerData['firstname'] . ' ' . $workerData['lastname']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.form101.content');

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.form101') . ": " . url("form101/" . base64_encode($workerData['id']) . "/" . base64_encode($workerData['formId']));

                    //     break;

                    // case WhatsappMessageTemplateEnum::NEW_JOB:
                    //     $jobData = $eventData['job'];
                    //     $workerData = $jobData['worker'];
                    //     $clientData = $jobData['client'];

                    //     $receiverNumber = $workerData['phone'];
                    //     App::setLocale($workerData['lng']);

                    //     $text = __('mail.wa-message.new_job.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => $workerData['firstname'] . ' ' . $workerData['lastname']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.new_job.content', [
                    //         'content_txt' => $eventData['content_data'] ? $eventData['content_data'] : ' ',
                    //         'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'service_name' => $workerData['lng'] == 'heb'
                    //             ? ($jobData['jobservice']['heb_name'] . ', ')
                    //             : ($jobData['jobservice']['name'] . ', '),
                    //         'address' => $jobData['property_address']['address_name'] . " " . ($jobData['property_address']['parking']
                    //             ? ("[" . $jobData['property_address']['parking'] . "]")
                    //             :  " "),
                    //         'status' => ucfirst($jobData['status'])
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/login");

                    //     break;



                    // case WhatsappMessageTemplateEnum::WORKER_HEARING_SCHEDULE:
                    //     $workerData = $eventData;
                    //     // $teamData = $eventData['team'];

                    //     $receiverNumber = $workerData['phone'];
                    //     App::setLocale($workerData['lng']);

                    //     $text = __('mail.wa-message.worker_hearing_schedule.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => trim(trim($workerData['firstname']) . ' ' . trim($workerData['lastname']))
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.worker_hearing_schedule.content', [
                    //         'team_name' => isset($eventData['team']) && !empty($eventData['team']['name'])
                    //             ? $eventData['team']['name']
                    //             : ' ',
                    //         'date'          => Carbon::parse($eventData['start_date'])->format('d-m-Y'),
                    //         'start_time'    => date("H:i", strtotime($eventData['start_time'])),
                    //         'end_time'      => date("H:i", strtotime($eventData['end_time'])),
                    //         'purpose'       => $eventData['purpose'] ? $eventData['purpose'] : "No purpose provided"
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.accept_reject') . ": " . url("hearing-schedule/" . base64_encode($eventData['id']));

                    //     break;

                    // case WhatsappMessageTemplateEnum::WORKER_NEED_EXTRA_TIME:
                    //     $jobData = $eventData['job'];

                    //     $receiverNumber = $jobData['worker']['phone'];
                    //     App::setLocale($jobData['worker']['lng']);

                    //     $text = __('mail.wa-message.need_extra_time_team.header');

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => "צוות"
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.need_extra_time_team.content', [
                    //         'worker_name' => $jobData['worker']['firstname'] . " " . $jobData['worker']['lastname'],
                    //         'change_shift' => url("admin/jobs/" . $jobData['id'] . "/change-worker"),
                    //         'address' => $jobData['property_address']
                    //             ? $jobData['property_address']['address_name']
                    //             : 'NA',
                    //         'extend_time' => url("time-manage/" . base64_encode($jobData["id"]) . "?action=adjust"),
                    //         'adjust_time' => url("time-manage/" . base64_encode($jobData["id"]) . "?action=keep"),
                    //         'worker_phone' => $jobData['worker']['phone'],
                    //         'client_phone' => $jobData['client']['phone'],
                    //     ]);

                    //     $text .= __('mail.wa-message.need_extra_time_team.signature');

                    //     break;



                    case WhatsappMessageTemplateEnum::JOB_APPROVED_NOTIFICATION_TO_WORKER:
                        // Extract job data
                        $jobData = $eventData['job'];
                        $emailData = $eventData['emailData'] ?? null;  // Check if emailData is present
                        $worker = $eventData['worker'] ?? null;  // Check if emailData is present

                        App::setLocale($worker['lng']);

                        $receiverNumber = $jobData['client']['phone'];

                        // Build the message
                        $text =  $emailData['emailSubject'] . "\n\n";

                        $text =  $emailData['emailTitle'] . "\n\n";


                        $text .= __('mail.wa-message.common.salutation', ['name' => $jobData['worker']['firstname']]) . "\n\n";
                        // if (isset($emailData['emailContentWa'])) {
                        //     $text .= $emailData['emailContentWa'] . "\n\n";
                        // } else {
                        //     $text .= $emailData['emailContent'] . "\n\n";
                        // }

                        if (isset($emailData['emailContentWa'])) {
                            $text .= $emailData['emailContentWa'] . "\n\n";
                        }else{
                            $text .= $emailData['emailContent'] . "\n\n";
                        }


                        $text .= __('mail.wa-message.worker_job_approval.content', [
                            'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                            'client_name' => $jobData['client']['firstname'] . " " . $jobData['client']['lastname'],
                            'worker_name' => $jobData['worker']['firstname'] . " " . $jobData['worker']['lastname'],
                            'service_name' => $jobData['worker']['lng'] == 'heb' ? $jobData['jobservice']['heb_name'] : $jobData['jobservice']['name'],
                            'start_time' => Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                            'address' => $jobData['property_address']['address_name'] ?? 'NA',
                        ]);

                        $text .= "\n\n" . __('mail.job_common.check_job_details') . ": " . url("worker/jobs/view/" . $jobData['id']) . "\n\n";
                        $text .= __('mail.job_common.reply_txt') . "\n\n";
                        $text .= __('mail.wa-message.common.signature');

                        break;

                    // case WhatsappMessageTemplateEnum::WORKER_UNASSIGNED:
                    //     $jobData = $eventData['job'];
                    //     $oldWorkerData = $eventData['old_worker'];
                    //     $oldJobData = $eventData['old_job'];

                    //     $receiverNumber = $oldWorkerData['phone'];
                    //     App::setLocale($oldWorkerData['lng']);

                    //     $text = __('mail.wa-message.worker_unassigned_job.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => $oldWorkerData['firstname'] . ' ' . $oldWorkerData['lastname']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.worker_unassigned_job.content', [
                    //         'old_job_start_date' => Carbon::parse($oldJobData['start_date'])->format('M d Y'),
                    //         'client_name' => $jobData['client']['firstname'] . ' ' . $jobData['client']['lastname'],
                    //         'service_name' => $oldWorkerData['lng'] == 'heb' ? ($jobData['jobservice']['heb_name'] . ', ') : ($jobData['jobservice']['name'] . ', '),
                    //         'old_job_start_time' => Carbon::today()->setTimeFromTimeString($oldJobData['start_time'])->format('H:i')
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::CLIENT_JOB_STATUS_NOTIFICATION:
                    //     $by = isset($eventData['by']) ? $eventData['by'] : 'client';
                    //     $adminData = $eventData['admin'];
                    //     $jobData = $eventData['job'];

                    //     $receiverNumber = $jobData['client']['phone'];
                    //     App::setLocale($jobData['client']['lng']);

                    //     $text = __('mail.wa-message.client_job_status_notification.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => $jobData['client']['firstname'] . " " . $jobData['client']['lastname']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_job_status_notification.content', [
                    //         'date' => Carbon::parse($jobData['start_date'])->format('M d Y')  . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                    //         'client_name' => ($jobData['client'] ? ($jobData['client']['firstname'] . " " . $jobData['client']['lastname']) : "NA"),
                    //         'service_name' => $jobData['jobservice']['name'],
                    //         'comment' => ($by == 'client' ?
                    //                     ("Client changed the Job status to " . ucfirst($jobData['status']) . "."
                    //                     . ($jobData['cancellation_fee_amount']) ?
                    //                     ("With Cancellation fees " . $jobData['cancellation_fee_amount'] . " ILS.") : " ") :
                    //                     ("Job is marked as " . ucfirst($jobData['status'])))
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("client/login");

                    //     break;

                    // case WhatsappMessageTemplateEnum::NOTIFY_MONDAY_CLIENT_FOR_SCHEDULE:
                    //     $clientData = $eventData['client'];
                    //     $holidayMessage = $eventData['holidayMessage'];

                    //     $receiverNumber = $clientData['phone'];
                    //     App::setLocale($clientData['lng'] ?? 'heb');

                    //     $text = __('mail.wa-message.notify_monday_client.subject');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.notify_monday_client.salutation');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.notify_monday_client.content');

                    //     $text .= "\n\n";

                    //     if ($holidayMessage) {
                    //         $text .= __('mail.wa-message.notify_monday_client.holiday',[
                    //             'holidays' => $holidayMessage
                    //         ]);
                    //     $text .= "\n";
                    //     }

                    //     $text .= __('mail.wa-message.notify_monday_client.request');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.notify_monday_client.link', [
                    //         'client_jobs' => url('client/jobs'),
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.notify_monday_client.signature');

                    //     break;

                    // case WhatsappMessageTemplateEnum::NOTIFY_MONDAY_WORKER_FOR_SCHEDULE:
                    //     $workerData = $eventData['worker'];
                    //     $holidayMessage = $eventData['holidayMessage'];

                    //     $receiverNumber = $workerData['phone'];
                    //     App::setLocale($workerData['lng'] ?? 'heb');

                    //     $text = __('mail.wa-message.notify_monday_worker.subject');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.notify_monday_worker.salutation',[
                    //         'worker_name' => $workerData['firstname'] . ' ' . $workerData['lastname'],
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.notify_monday_worker.content');

                    //     $text .= "\n\n";

                    //     if ($holidayMessage) {
                    //         $text .= __('mail.wa-message.notify_monday_worker.holiday',[
                    //             'holidays' => $holidayMessage
                    //         ]);
                    //     $text .= "\n";
                    //     }

                    //     $text .= __('mail.wa-message.notify_monday_worker.link', [
                    //         'worker_jobs' => url('worker/jobs'),
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.notify_monday_worker.signature');

                    //     break;

                    // case WhatsappMessageTemplateEnum::WORKER_JOB_STATUS_NOTIFICATION:
                    //     $comment = $eventData['comment'];
                    //     $jobData = $eventData['job'];

                    //     $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.worker_job_status_notification.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.worker_job_status_notification.content', [
                    //         'status' => ucfirst($jobData['status']),
                    //         'date' => Carbon::parse($jobData['start_date'])->format('M d Y') . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                    //         'worker_name' => ($jobData['worker'] ? ($jobData['worker']['firstname'] . " " . $jobData['worker']['lastname']) : "NA"),
                    //         'client_name' => ($jobData['client'] ? ($jobData['client']['firstname'] . " " . $jobData['client']['lastname']) : "NA"),
                    //         'service_name' => $jobData['jobservice']['name'],
                    //         'status' => ucfirst($jobData['status'])
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("worker/jobs/view/" . $jobData["id"]);

                    //     break;


                    // case WhatsappMessageTemplateEnum::LEAD_NEED_HUMAN_REPRESENTATIVE:
                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.lead_need_human_representative.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.lead_need_human_representative.content', [
                    //         'client_name' => $eventData['client']['firstname'] . ' ' . $eventData['client']['lastname'],
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_client') . ": " . url("admin/clients/view/" . $eventData['client']['id']);

                    //     break;

                    // case WhatsappMessageTemplateEnum::NO_SLOT_AVAIL_CALLBACK:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.no_slot_avail_callback.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.no_slot_avail_callback.content', [
                    //         'client_name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_client') . ": " . url("admin/clients/view/" . $eventData['client']['id']);

                    //     break;

                    // case WhatsappMessageTemplateEnum::WORKER_FORMS:
                    //     $workerData = $eventData['worker'];

                    //     $receiverNumber = $workerData['phone'];
                    //     App::setLocale($workerData['lng']);

                    //     $text = __('mail.wa-message.worker_forms.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => $workerData['firstname'] . ' ' . $workerData['lastname']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.worker_forms.content');

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.check_form') . ": " . url("worker-forms/" . base64_encode($workerData['id']));

                    //     break;

                    // case WhatsappMessageTemplateEnum::ADMIN_JOB_STATUS_NOTIFICATION:
                    //     $by = $eventData['by'];
                    //     $jobData = $eventData['job'];

                    //     $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.admin_job_status_notification.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.admin_job_status_notification.content', [
                    //         'date' => Carbon::parse($jobData['start_date'])->format('M d Y')  . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                    //         'worker_name' => ($jobData['worker'] ? ($jobData['worker']['firstname'] . " " . $jobData['worker']['lastname']) : "NA"),
                    //         'client_name' => ($jobData['client'] ? ($jobData['client']['firstname'] . " " . $jobData['client']['lastname']) : "NA"),
                    //         'service_name' => $jobData['jobservice']['name'],
                    //         'status' => ucfirst($jobData['status']),
                    //         'comment' => ($by == 'client' ?
                    //         ("Client changed the Job status to " . ucfirst($jobData['status']) . "." .
                    //         ($jobData['cancellation_fee_amount']) ?
                    //         ("With Cancellation fees " . $jobData['cancellation_fee_amount'] . " ILS.") : " ") :
                    //         ("Job is marked as " . ucfirst($jobData['status'])))
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_job') . ": " . url("admin/jobs/view/" . $jobData["id"]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::WORKER_CHANGED_AVAILABILITY_AFFECT_JOB:
                    //     $workerData = $eventData['worker'];

                    //     $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.worker_changed_availability_affect_job.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.worker_changed_availability_affect_job.content', [
                    //         'name' => $workerData['firstname'] . ' ' . $workerData['lastname'],
                    //         'date' => Carbon::parse($eventData['date'])->format('M d Y'),
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::WORKER_LEAVES_JOB:
                    //     $workerData = $eventData['worker'];

                    //     $receiverNumber = config('services.whatsapp_groups.changes_cancellation');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.worker_leaves_job.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.worker_leaves_job.content', [
                    //         'name' => $workerData['firstname'] . ' ' . $workerData['lastname'],
                    //         'date' => $workerData['last_work_date'],
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::CLIENT_PAYMENT_FAILED:
                    //     $clientData = $eventData['client'];
                    //     $cardData = $eventData['card'];

                    //     $receiverNumber = config('services.whatsapp_groups.payment_status');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.client_payment_failed.header');
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.common.salutation', ['name' => 'צוות']);
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.client_payment_failed.content', [
                    //         'name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'card_number' => $cardData['card_number']
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::ORDER_CANCELLED:
                    //     $clientData = $eventData['client'];
                    //     $orderData = $eventData['order'];

                    //     $receiverNumber = config('services.whatsapp_groups.payment_status');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.order_cancelled.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.order_cancelled.content', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'order_id' => $orderData['order_id']
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::PAYMENT_PAID:
                    // case WhatsappMessageTemplateEnum::PAYMENT_PARTIAL_PAID:
                    //     $clientData = $eventData['client'];
                    //     // $amountData = $eventData['amount'];

                    //     $receiverNumber = config('services.whatsapp_groups.payment_status');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.payment_paid.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.payment_paid.content', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY:
                    //     $clientData = $eventData['client'];
                    //     $invoiceData = $eventData['invoice'];

                    //     $receiverNumber = config('services.whatsapp_groups.payment_status');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.client_invoice_created_and_sent_to_pay.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_invoice_created_and_sent_to_pay.content', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'invoice_id' => $invoiceData['invoice_id']
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::CLIENT_INVOICE_PAID_CREATED_RECEIPT:
                    //     $clientData = $eventData['client'];
                    //     $invoiceData = $eventData['invoice'];

                    //     $receiverNumber = config('services.whatsapp_groups.payment_status');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.client_invoice_paid_created_receipt.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_invoice_paid_created_receipt.content', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'invoice_id' => $invoiceData['invoice_id']
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_EXTRA:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.payment_status');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.order_created_with_extra.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.order_created_with_extra.content', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'order_id' => $eventData['order_id'],
                    //         'extra' => $eventData['extra'],
                    //         'total' => $eventData['total_amount'],
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::ORDER_CREATED_WITH_DISCOUNT:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.payment_status');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.order_created_with_discount.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.order_created_with_discount.content', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'order_id' => $eventData['order_id'],
                    //         'discount' => $eventData['discount'],
                    //         'total' => $eventData['total_amount'],
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::CLIENT_REVIEWED:
                    //     $clientData = $eventData['client'];
                    //     $jobData = $eventData['job'];

                    //     $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.client_reviewed.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_reviewed.content', [
                    //         'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                    //         'client_name' => $clientData['firstname'] . " " . $clientData['lastname'],
                    //         'client_phone' => $clientData['phone'],
                    //         'rating' => $jobData['rating'],
                    //         'review' => $jobData['review'],
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::CLIENT_CHANGED_JOB_SCHEDULE:
                    //     $clientData = $eventData['client'];
                    //     $jobData = $eventData['job'];

                    //     $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.client_changed_job_schedule.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_changed_job_schedule.content', [
                    //         'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                    //         'client_name' => $clientData['firstname'] . " " . $clientData['lastname'],
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::CLIENT_COMMENTED:
                        // $clientData = $eventData['client'];
                        // $jobData = $eventData['job'];

                        // $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                        // App::setLocale('heb');

                        // $text = __('mail.wa-message.client_commented.header');

                        // $text .= "\n\n";

                        // $text .= __('mail.wa-message.common.salutation', [
                        //     'name' => 'צוות'
                        // ]);

                        // $text .= "\n\n";

                        // $text .= __('mail.wa-message.client_commented.content', [
                        //     'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                        //     'client_name' => $clientData['firstname'] . " " . $clientData['lastname'],
                        // ]);

                        // break;

                    // case WhatsappMessageTemplateEnum::ADMIN_COMMENTED:
                    //     $adminData = $eventData['admin'];
                    //     $jobData = $eventData['job'];

                    //     $receiverNumber = config('services.whatsapp_groups.reviews_of_clients');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.admin_commented.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.admin_commented.content', [
                    //         'date_time' => Carbon::parse($jobData['start_date'])->format('M d Y') . " " . Carbon::today()->setTimeFromTimeString($jobData['start_time'])->format('H:i'),
                    //         'admin_name' => $adminData['name'],
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::NEW_LEAD_ARRIVED:
                    //     $clientData = $eventData['client'];
                    //     $type = $eventData['type'] ?? "";

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');


                    //     $addresses = [];

                    //     // Add all property addresses if they exist
                    //     if (!empty($clientData['property_addresses']) && is_array($clientData['property_addresses'])) {
                    //         foreach ($clientData['property_addresses'] as $propertyAddress) {
                    //             if (!empty($propertyAddress['address_name'])) {
                    //                 $addresses[] = $propertyAddress['address_name'];
                    //             }
                    //         }
                    //     }

                    //     // Concatenate all addresses into a single string, separated by a comma
                    //     $fullAddress = implode(', ', $addresses);

                    //     $text = __('mail.wa-message.new_lead_arrived.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.new_lead_arrived.content', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'contact' => $clientData['phone'],
                    //         'Service_Requested' => "",
                    //         'email' => $clientData['email'],
                    //         'address' => $fullAddress ?? "NA",
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.new_lead_arrived.follow_up');

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_lead') . ": " . url("admin/leads/view/" . $clientData['id']);
                    //     $text .= "\n\n" . __('mail.wa-message.button-label.call_lead') . ": " . $clientData['phone'];

                    //     break;



                    // case WhatsappMessageTemplateEnum::UNANSWERED_LEAD:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = $clientData["phone"];

                    //     App::setLocale($clientData["lng"]?? "heb");

                    //     $text .= __('mail.wa-message.common.salutation', ['name' => $clientData['firstname']]);
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.tried_to_contact_you.content', [
                    //         'name' => $clientData['firstname'],
                    //     ]);
                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.tried_to_contact_you.contact_details');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.tried_to_contact_you.availability');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.signature');

                    //     break;

                    // case WhatsappMessageTemplateEnum::INQUIRY_RESPONSE:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = $clientData["phone"];
                    //     App::setLocale($clientData['lng']);

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname']))
                    //     ]);
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.inquiry_response.content');
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.inquiry_response.service_areas');
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.common.signature');

                    //     break;

                    // case WhatsappMessageTemplateEnum::PENDING:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.pending.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::POTENTIAL:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.potential.content',[
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);
                    //     break;

                    // case WhatsappMessageTemplateEnum::IRRELEVANT:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.irrelevant.content',[
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::UNINTERESTED:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.uninterested.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::UNANSWERED:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.unanswered.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::POTENTIAL_CLIENT:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.potential_client.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);
                    //     break;

                    // case WhatsappMessageTemplateEnum::PENDING_CLIENT:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.pending_client.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                        // break;

                    // case WhatsappMessageTemplateEnum::WAITING:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.waiting.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                        break;

                    // case WhatsappMessageTemplateEnum::ACTIVE_CLIENT:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.active_client.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     \Log::info("Text",["text" => $text]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::FREEZE_CLIENT:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.freeze_client_team.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::UNHAPPY:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.unhappy.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::PRICE_ISSUE:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.price_issue.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::MOVED:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.moved.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::ONETIME:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text .= __('mail.wa-message.onetime.content', [
                    //         'name' => $clientData['firstname'] ." ".$clientData['lastname'],
                    //         'phone' => $clientData['phone'],
                    //         'url' => url("admin/clients/view/" . $clientData['id'])
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::PAST:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = $clientData['phone'];
                    //     App::setLocale($clientData['lng']??'en');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname']))
                    //     ]);

                    //     $text .= __('mail.wa-message.past.thankyou');

                    //     $text .= "\n\n";

                    //     // $text .= __('mail.wa-message.client_in_freeze_status.content', [
                    //     //     'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //     // ]);
                    //     $text .= __('mail.wa-message.past.content');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.past.feelfree');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.past.signature');


                    //     break;


                    // case WhatsappMessageTemplateEnum::LEAD_ACCEPTED_PRICE_OFFER:
                    //     $clientData = $eventData['client'];
                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     // Create the message
                    //     $text = __('mail.wa-message.lead_accepted_price_offer.header', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.lead_accepted_price_offer.content', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_lead') . ": " . url("admin/leads/view/" . $clientData['id']);

                    //     break;

                    // case WhatsappMessageTemplateEnum::LEAD_DECLINED_PRICE_OFFER:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     // Create the message
                    //     $text = __('mail.wa-message.lead_declined_price_offer.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות',
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.lead_declined_price_offer.content');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.lead_declined_price_offer.details', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'reason' => $clientData['reason'] ?? __('mail.wa-message.lead_declined_price_offer.no_reason_provided'),
                    //     ]);
                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_lead') . ": " . url("admin/leads/view/" . $clientData['id']);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.lead_declined_price_offer.assistance');

                    //     $text .= __('mail.common.regards');

                    //     $text .= "\n";

                    //     $text .= __('mail.common.company');

                    //     break;


                    // case WhatsappMessageTemplateEnum::FILE_SUBMISSION_REQUEST_TEAM:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     // Create the message
                    //     $text = __('mail.wa-message.file_submission_request_team.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.file_submission_request_team.content',[
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.file_submission_request_team.details', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'client_contact' => $clientData['phone'],
                    //     ]);

                    //     $text .= __('mail.wa-message.file_submission_request_team.signature');

                    //     break;


                    // case WhatsappMessageTemplateEnum::LEAD_DECLINED_CONTRACT:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     // Create the message
                    //     $text = __('mail.wa-message.lead_declined_contract.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות',
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.lead_declined_contract.content');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.lead_declined_contract.details', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'reason' => $clientData['reason'] ?? __('mail.wa-message.lead_declined_contract.no_reason_provided'),
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.view_lead') . ": " . url("admin/leads/view/" . $clientData['id']);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.lead_declined_contract.assistance');

                    //     $text .= __('mail.common.regards');

                    //     $text .= "\n";

                    //     $text .= __('mail.common.company');

                    //     break;

                    // case WhatsappMessageTemplateEnum::CLIENT_IN_FREEZE_STATUS:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = $clientData['phone'];
                    //     App::setLocale($clientData['lng']??'en');


                    //     // Create the message
                    //     // $text = __('mail.wa-message.client_in_freeze_status.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname']))
                    //     ]);

                    //     $text .= __('mail.wa-message.client_in_freeze_status.thankyou');

                    //     $text .= "\n\n";

                    //     // $text .= __('mail.wa-message.client_in_freeze_status.content', [
                    //     //     'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //     // ]);
                    //     $text .= __('mail.wa-message.client_in_freeze_status.content');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_in_freeze_status.action_required');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_in_freeze_status.signature');


                    //     break;


                    // case WhatsappMessageTemplateEnum::CLIENT_LEAD_STATUS_CHANGED:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.client_lead_status_changed.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => 'צוות'
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.client_lead_status_changed.content', [
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //         'new_status' => $eventData['new_status']
                    //     ]);

                    //     break;

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
                    // case WhatsappMessageTemplateEnum::REFUND_CLAIM_MESSAGE:
                    //     $userData = $eventData['worker'];
                    //     $claimData = $eventData['refundclaim'];
                    //     \Log::info([$claimData]);

                    //     $receiverNumber = $userData['phone'];
                    //     App::setLocale($userData['lng']);

                    //     $text = __('mail.refund_claim.header');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => $userData['firstname'] . ' ' . $userData['lastname']
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.refund_claim.body', [
                    //         'status' => $claimData['status'],
                    //     ]);

                    //     if ($claimData['status'] !== 'approved' && !is_null($claimData['rejection_comment'])) {
                    //         $text .= "\n\n";
                    //         $text .= __('mail.refund_claim.reason', [
                    //             'reason' => $claimData['rejection_comment']
                    //         ]);
                    //     }


                    //     break;

                    // case WhatsappMessageTemplateEnum::FOLLOW_UP_ON_OUR_CONVERSATION:
                    //     $clientData = $eventData['client'];

                    //     $whapiApiEndpoint = config('services.whapi.url');
                    //     $whapiApiToken = config('services.whapi.token');

                    //     App::setLocale($clientData['lng'] ?? 'en');
                    //     $receiverNumber = $clientData['phone'];
                    //     $number = $clientData['phone'] . "@s.whatsapp.net";

                    //     $text .= __('mail.wa-message.follow_up.salutation',[
                    //         'client_name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname']))
                    //     ]);
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.follow_up.introduction');
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.follow_up.testimonials', [
                    //         'testimonials_link' => url('https://www.facebook.com/brmsrvc/posts/pfbid02wFoke74Yv9fK8FvwExmLducZdYufrHheqx84Dhmn14LikcUo3ZmGscLh1BrFBzrEl')
                    //     ]);
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.follow_up.brochure');
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.follow_up.commitment');
                    //     $text .= "\n\n";
                    //     $text .= __('mail.wa-message.follow_up.help');

                    //     $text .= __('mail.wa-message.follow_up.signature');

                    //     $fileName = $clientData['lng'] === 'heb' ? 'BroomServiceHebrew.pdf' : 'BroomServiceEnglish.pdf';

                    //     // Retrieve the file from storage
                    //     $pdfPath = Storage::path($fileName);

                    //     // Prepare the file for attachment
                    //     $file = fopen($pdfPath, 'r'); // Open the file in read mode

                    //     // Send message and PDF
                    //     $response = Http::withHeaders([
                    //         'Authorization' => 'Bearer ' . $whapiApiToken,
                    //     ])->attach(
                    //         'media',
                    //         $file,
                    //         $fileName // Use 'media' for the attachment field
                    //     )->post($whapiApiEndpoint . 'messages/document', [
                    //         'to' => $number,
                    //         'mime_type' => 'application/pdf',
                    //     ]);

                    //     fclose($file);

                    //     if ($response->successful()) {
                    //         \Log::info('PDF sent successfully');
                    //     } else {
                    //         \Log::error('Failed to send PDF: ' . $response->body());
                    //     }
                    //     break;

                        // case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT:
                        // $clientData = $eventData['client'];

                        // $receiverNumber = $clientData['phone'];
                        // App::setLocale($clientData['lng']??'en');

                        // $text = __('mail.wa-message.contract_verify.subject');

                        // $text .= "\n\n";

                        // $text .= __('mail.wa-message.contract_verify.info',[
                        //     'name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                        // ]);

                        // $text .= "\n\n";

                        // $text .= __('mail.wa-message.contract_verify.content');

                        // break;

                    // case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_TEAM:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.contract_verify_team.subject');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.contract_verify_team.info',[
                    //         'name' => "צוות",
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.contract_verify_team.content',[
                    //         'name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //     ]);

                    //     break;

                    // case WhatsappMessageTemplateEnum::WEEKLY_CLIENT_SCHEDULED_NOTIFICATION:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = $clientData['phone'];
                    //     App::setLocale($clientData['lng'] ?? 'en');

                    //     // Add the body content with dynamic client name and contract date
                    //     $text .= __('mail.wa-message.common.salutation', [
                    //         'name' => trim(trim($clientData['firstname']) . ' ' . trim($clientData['lastname'])),
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.weekly_notification.content');

                    //     $text .= "\n\n";
                    //     // $text .= __('mail.wa-message.weekly_notification.action_btn') . "\n";

                    //     $text .= __('mail.wa-message.button-label.change_service_date') . ": " . url("client/jobs");
                    //     // $text .= __('mail.wa-message.button-label.change_service_date') . ": " . url("client/jobs/view/" . base64_encode($jobData->id));
                    //     // $text .= "\n" . __('mail.wa-message.button-label.cancel_service') . ": " . url("client/jobs/view/" . base64_encode($jobData->id)) . "/cancel-service";
                    //     $text .= "\n\n";

                    //     // Add the footer with contact details
                    //     $text .= __('mail.wa-message.common.signature');

                    //     break;


                    // case WhatsappMessageTemplateEnum::WORKER_LEAD_WEBHOOK_IRRELEVANT:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = $clientData["phone"];
                    //     App::setLocale($clientData['lng']??'en');

                    //     $text = '';

                    //     $text .=  __('mail.wa-message.worker_webhook_irrelevant.message');

                    //     break;

                    // case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_CLIENT:
                    //     $clientData = $eventData['client'];

                    //     $receiverNumber = $clientData['phone'];
                    //     App::setLocale($clientData['lng']??'en');

                    //     $text = __('mail.wa-message.contract_verify.subject');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.contract_verify.info',[
                    //         'name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.contract_verify.content');

                    //     break;

                    // case WhatsappMessageTemplateEnum::NOTIFY_CONTRACT_VERIFY_TO_TEAM:
                    //     $clientData = $eventData['client'];
                    //     $jobData = $eventData['job'];

                    //     $receiverNumber = config('services.whatsapp_groups.lead_client');
                    //     App::setLocale('heb');

                    //     $text = __('mail.wa-message.contract_verify_team.subject');

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.contract_verify_team.info',[
                    //         'name' => "צוות",
                    //     ]);

                    //     $text .= "\n\n";

                    //     $text .= __('mail.wa-message.contract_verify_team.content',[
                    //         'name' => $clientData['firstname'] . ' ' . $clientData['lastname'],
                    //     ]);

                    //     $text .= "\n\n" . __('mail.wa-message.button-label.review') . ": " . url("client/jobs/" . base64_encode($jobData['id']) . "/review");

                    //     break;


                }
            }

            // $receiverNumber = '918469138538';
            // $receiverNumber = config('services.whatsapp_groups.notification_test');
            if ($receiverNumber && $text) {
                Log::info('SENDING WA to ' . $receiverNumber);
                Log::info($text);
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
            Log::error('WA NOTIFICATION ERROR', ['error' => $th->getMessage(), 's' => $th->getTraceAsString()]);
        }
    }
}
