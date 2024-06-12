<?php

declare(strict_types=1);

namespace App\Enums;

final class NotificationTypeEnum extends AbstractEnum
{
    public const SENT_MEETING = 'sent-meeting';
    public const ACCEPT_MEETING = 'accept-meeting';
    public const REJECT_MEETING = 'reject-meeting';
    public const ACCEPT_OFFER = 'accept-offer';
    public const REJECT_OFFER = 'reject-offer';
    public const CONTRACT_ACCEPT = 'contract-accept';
    public const CONTRACT_REJECT = 'contract-reject';
    public const CLIENT_CANCEL_JOB = 'client-cancel-job';
    public const WORKER_RESCHEDULE = 'worker-reschedule';
    public const OPENING_JOB = 'opening-job';
    public const RESCHEDULE_MEETING = 'reschedule-meeting';
    public const FILES = 'files';
    public const PAYMENT_FAILED = 'payment-failed';
    public const PAYMENT_PAID = 'payment-paid';
    public const PAYMENT_PARTIAL_PAID = 'payment-partial-paid';
    public const CONVERTED_TO_CLIENT = 'converted-to-client';
    public const JOB_REVIEWED = 'job-reviewed';
    public const FORM101_SIGNED = 'form101-signed';
    public const WORKER_CONTRACT_SIGNED = 'worker-contract-signed';
    public const SAFETY_GEAR_SIGNED = 'safety-gear-signed';
    public const INSURANCE_SIGNED = 'insurance-signed';
    public const CLIENT_COMMENTED = 'client-commented';
    public const JOB_SCHEDULE_CHANGE = 'job-schedule-change';
    public const WORKER_NOT_APPROVED_JOB = 'worker-not-approved-job';
    public const WORKER_NOT_LEFT_FOR_JOB = 'worker-not-left-for-job';
    public const WORKER_NOT_STARTED_JOB = 'worker-not-started-job';
    public const WORKER_NOT_FINISHED_JOB_ON_TIME = 'worker-not-finished-job-on-time';
    public const WORKER_EXCEED_JOB_TIME = 'worker-exceed-job-time';
}
