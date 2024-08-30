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
    public const CLIENT_REVIEWED = 'client-reviewed';
    public const FORM101_SIGNED = 'form101-signed';
    public const WORKER_CONTRACT_SIGNED = 'worker-contract-signed';
    public const SAFETY_GEAR_SIGNED = 'safety-gear-signed';
    public const INSURANCE_SIGNED = 'insurance-signed';
    public const CLIENT_COMMENTED = 'client-commented';
    public const ADMIN_COMMENTED = 'admin-commented';
    public const WORKER_COMMENTED = 'worker-commented';
    public const JOB_SCHEDULE_CHANGE = 'job-schedule-change';
    public const WORKER_NOT_APPROVED_JOB = 'worker-not-approved-job';
    public const WORKER_NOT_LEFT_FOR_JOB = 'worker-not-left-for-job';
    public const WORKER_NOT_STARTED_JOB = 'worker-not-started-job';
    public const WORKER_NOT_FINISHED_JOB_ON_TIME = 'worker-not-finished-job-on-time';
    public const WORKER_EXCEED_JOB_TIME = 'worker-exceed-job-time';
    public const NEW_LEAD_ARRIVED = 'new-lead-arrived';
    public const CLIENT_LEAD_STATUS_CHANGED = 'client-lead-status-changed';
    public const CLIENT_CHANGED_JOB_SCHEDULE = 'client-changed-job-schedule';
    public const WORKER_CHANGED_AVAILABILITY_AFFECT_JOB = 'worker-changed-availability-affect-job';
    public const WORKER_LEAVES_JOB = 'worker-leaves-job';
    public const ORDER_CANCELLED = 'order-cancelled';
    public const CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY = "client-invoice-created-and-sent-to-pay";
    public const CLIENT_INVOICE_PAID_CREATED_RECEIPT = "client-invoice-paid-created-receipt";
    public const ORDER_CREATED_WITH_EXTRA = "order-created-with-extra";
    public const ORDER_CREATED_WITH_DISCOUNT = "order-created-with-discount";
    public const SICK_LEAVE_CREATED = "sick_leave_created";
}
