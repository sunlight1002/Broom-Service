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
    public const SICK_LEAVE_NOTIFICATION = "sick_leave_notification";
}
