<?php

declare(strict_types=1);

namespace App\Enums;

final class WhatsappMessageTemplateEnum extends AbstractEnum
{
    public const CLIENT_MEETING_SCHEDULE = 'client_meeting_schedule';
    public const OFFER_PRICE = 'offer_price';
    public const CONTRACT = 'contract';
    public const CLIENT_JOB_UPDATED = 'client_job_updated';
    public const DELETE_MEETING = 'delete_meeting';
    public const FORM101 = 'form101';
    public const NEW_JOB = 'new_job';
    public const WORKER_CHANGE_REQUEST = 'worker_change_request';
    public const WORKER_CONTRACT = 'worker_contract';
    public const WORKER_JOB_APPROVAL = 'worker_job_approval';
    public const WORKER_JOB_NOT_APPROVAL = 'worker_job_not_approval';
    public const WORKER_REMIND_JOB = 'worker_remind_job';
    public const WORKER_UNASSIGNED = 'worker_unassigned';

    public const CLIENT_JOB_STATUS_NOTIFICATION = 'client_job_status_notification';
    public const ADMIN_JOB_STATUS_NOTIFICATION = 'admin_job_status_notification';
    public const WORKER_JOB_OPENING_NOTIFICATION = 'worker_job_opening_notification';
    public const WORKER_JOB_STATUS_NOTIFICATION = 'worker_job_status_notification';

    public const WORKER_SAFE_GEAR = 'worker_safe_gear';
    public const ADMIN_RESCHEDULE_MEETING = 'admin_reschedule_meeting';
    public const TEAM_RESCHEDULE_MEETING = 'team_reschedule_meeting';
    public const CLIENT_RESCHEDULE_MEETING = 'client_reschedule_meeting';
    public const ADMIN_LEAD_FILES = 'admin_lead_files';
    public const TEAM_LEAD_FILES = 'team_lead_files';
    public const CLIENT_MEETING_REMINDER = 'client_meeting_reminder';

    public const WORKER_FORMS = "worker_forms";
    public const WORKER_AVAILABILITY_CHANGED = "worker_availability_changed";
    public const WORKER_FORM101_SIGNED = "worker_form101_signed";
    public const WORKER_CONTRACT_SIGNED = "worker_contract_signed";
    public const WORKER_INSURANCE_SIGNED = "worker_insurance_signed";
    public const WORKER_SAFETY_GEAR_SIGNED = "worker_safety_gear_signed";
    public const CLIENT_PAYMENT_FAILED = "client_payment_failed";
    public const CLIENT_REVIEWED = "client_reviewed";
}
