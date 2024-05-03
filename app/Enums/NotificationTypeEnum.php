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
}
