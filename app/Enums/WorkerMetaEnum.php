<?php

declare(strict_types=1);

namespace App\Enums;

final class WorkerMetaEnum extends AbstractEnum
{
    public const NOTIFICATION_SENT_5_PM = 'notification_sent_5_pm';
    public const NOTIFICATION_SENT_5_30PM = 'notification_sent_5_30_pm';
    public const NOTIFICATION_SENT_30MIN_BEFORE_JOB_ENDTIME = 'notification_sent_30min_before_job_endtime';
}
