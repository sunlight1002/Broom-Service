<?php

declare(strict_types=1);

namespace App\Enums;

final class WhatsappMessageTemplateEnum extends AbstractEnum
{
    public const CLIENT_MEETING_SCHEDULE = 'client_meeting_schedule';
    public const OFFER_PRICE = 'offer_price';
    public const CONTRACT = 'contract';
    public const CLIENT_JOB_UPDATED = 'client_job_updated';
    public const CREATE_JOB = 'create_job';
    public const DELETE_MEETING = 'delete_meeting';
    public const FORM101 = 'form101';
    public const NEW_JOB = 'new_job';
    public const WORKER_CONTRACT = 'worker_contract';
    public const WORKER_JOB_APPROVAL = 'worker_job_approval';
    public const WORKER_NOT_APPROVED_JOB = 'worker_not_approved_job';
    public const WORKER_NOT_LEFT_FOR_JOB = 'worker_not_left_for_job';
    public const WORKER_NOT_STARTED_JOB = 'worker_not_started_job';
    public const WORKER_NOT_FINISHED_JOB_ON_TIME = 'worker_not_finished_job_on_time';
    public const WORKER_EXCEED_JOB_TIME = 'worker_exceed_job_time';
    public const WORKER_REMIND_JOB = 'worker_remind_job';
    public const WORKER_UNASSIGNED = 'worker_unassigned';

    public const CLIENT_JOB_STATUS_NOTIFICATION = 'client_job_status_notification';
    public const ADMIN_JOB_STATUS_NOTIFICATION = 'admin_job_status_notification';
    public const WORKER_JOB_OPENING_NOTIFICATION = 'worker_job_opening_notification';
    public const WORKER_JOB_STATUS_NOTIFICATION = 'worker_job_status_notification';

    public const WORKER_SAFE_GEAR = 'worker_safe_gear';
    public const ADMIN_RESCHEDULE_MEETING = 'admin_reschedule_meeting';
    public const CLIENT_RESCHEDULE_MEETING = 'client_reschedule_meeting';
    public const ADMIN_LEAD_FILES = 'admin_lead_files';
    public const CLIENT_MEETING_REMINDER = 'client_meeting_reminder';
    public const SICK_LEAVE_NOTIFICATION = 'sick_leave_notification';
    public const WORKER_FORMS = "worker_forms";
    public const WORKER_FORM101_SIGNED = "worker_form101_signed";
    public const WORKER_CONTRACT_SIGNED = "worker_contract_signed";
    public const WORKER_INSURANCE_SIGNED = "worker_insurance_signed";
    public const WORKER_SAFETY_GEAR_SIGNED = "worker_safety_gear_signed";
    public const CLIENT_PAYMENT_FAILED = "client_payment_failed";
    public const PAYMENT_PAID = 'payment_paid';
    public const PAYMENT_PARTIAL_PAID = 'payment_partial_paid';
    public const CLIENT_REVIEWED = "client_reviewed";
    public const CLIENT_COMMENTED = "client_commented";
    public const WORKER_COMMENTED = "worker_commented";
    public const ADMIN_COMMENTED = "admin_commented";
    public const NEW_LEAD_ARRIVED = "new_lead_arrived";
    public const CLIENT_LEAD_STATUS_CHANGED = "client_lead_status_changed";
    public const CLIENT_CHANGED_JOB_SCHEDULE = "client_changed_job_schedule";
    public const WORKER_CHANGED_AVAILABILITY_AFFECT_JOB = "worker_changed_availability_affect_job";
    public const WORKER_LEAVES_JOB = "worker_leaves_job";
    public const ORDER_CANCELLED = "order_cancelled";
    public const CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY = "client_invoice_created_and_sent_to_pay";
    public const CLIENT_INVOICE_PAID_CREATED_RECEIPT = "client_invoice_paid_created_receipt";
    public const ORDER_CREATED_WITH_EXTRA = "order_created_with_extra";
    public const ORDER_CREATED_WITH_DISCOUNT = "order_created_with_discount";
    public const LEAD_NEED_HUMAN_REPRESENTATIVE = "lead_need_human_representative";
    public const NO_SLOT_AVAIL_CALLBACK = "no_slot_avail_callback";
    public const USER_STATUS_CHANGED = "user_status_changed";
    public const UNANSWERED_LEAD = "unanswered-lead-notification";
    public const FOLLOW_UP_PRICE_OFFER = "follow-up-price-offer";
    public const FINAL_FOLLOW_UP_PRICE_OFFER = "final_follow-up-price-offer";
    public const BOOK_CLIENT_AFTER_SIGNED_CONTRACT = "book-client-after-signed-contract";
    public const LEAD_ACCEPTED_PRICE_OFFER = "lead-accepted-price-offer";
    public const LEAD_DECLINED_CONTRACT = "lead-declined-contract";
    public const LEAD_DECLINED_PRICE_OFFER = "lead-declined-price-offer";
    public const CLIENT_IN_FREEZE_STATUS = "client-in-freeze-status";
    public const STATUS_NOT_UPDATED = "status-not-updated";
    public const INQUIRY_RESPONSE = "inquiry-response";
    public const FOLLOW_UP_REQUIRED = "follow-up-required";
    public const FILE_SUBMISSION_REQUEST = "file-submission-request";
    public const FOLLOW_UP_ON_OUR_CONVERSATION = "follow-up-on-our-conversation";
    public const JOB_APPROVED_NOTIFICATION_TO_WORKER = "job-approved-notification-to-worker";
    public const NOTIFY_CONTRACT_VERIFY_TO_CLIENT = "notify-contract-verify-to-client";
    public const NOTIFY_CONTRACT_VERIFY_TO_TEAM = "notify-contract-verify-to-team";
    public const CONTRACT_REMINDER_TO_CLIENT_AFTER_3DAY = "contract-reminder-to-client-after-3day";
    public const CONTRACT_REMINDER_TO_CLIENT_AFTER_24HOUR = "contract-reminder-to-client-after-24hour";
    public const PRICE_OFFER_REMINDER_12_HOURS = "price-offer-reminder-12-hours";
    public const CONTRACT_NOT_SIGNED_12_HOURS = "contract-not-signed-12-hours";
    public const JOB_APPROVED_NOTIFICATION_TO_TEAM = "job-approved-notification-to-team";
    public const WORKER_AFTER_APPROVE_JOB = "worker-after-approve-job";
    public const WORKER_NOTIFY_AFTER_ON_MY_WAY = "worker-notify-after-on-my-way";
    public const TEAM_NOTIFY_WORKER_AFTER_ON_MY_WAY = "team_notify-worker-before-on-my-way";
    public const TEAM_NOTIFY_CONTACT_MANAGER = "team-notify-contact-manager";
    public const WORKER_ON_MY_WAY_NOTIFY = "worker-on-my-way-notify";
    public const WORKER_ARRIVE_NOTIFY = "worker-arrive-notify";
    public const NOTIFY_TEAM_FOR_SKIPPED_COMMENTS = "notify-team-for-skipped-comments";
    public const TEAM_ADJUST_WORKER_JOB_COMPLETED_TIME = "team-adjust-worker-job-completed-time";
    public const NOTIFY_CLIENT_FOR_REVIEWED = "notify-client-for-reviewed";
    public const NOTIFY_MONDAY_CLIENT_AND_WORKER_FOR_SCHEDULE = "notify-monday-client-and-worker-for-schedule";
    public const WEEKLY_CLIENT_SCHEDULED_NOTIFICATION = "weekly-client-scheduled-notification";
    public const TO_TEAM_WORKER_NOT_CONFIRM_JOB = "to-team-worker-not-confirm-job";
    public const REMIND_WORKER_TO_JOB_CONFIRM = "remind-worker-to-job-confirm";
    public const REFUND_CLAIM_MESSAGE = "refund-claim-message";
}
