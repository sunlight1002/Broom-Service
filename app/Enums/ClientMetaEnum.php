<?php

declare(strict_types=1);

namespace App\Enums;

final class ClientMetaEnum extends AbstractEnum
{
    public const NOTIFICATION_SENT_24_HOURS = 'notification_sent_24_hour';
    public const NOTIFICATION_SENT_3_DAY = 'notification_sent_3_day';
    public const NOTIFICATION_SENT_7_DAY = 'notification_sent_7_day';
    public const NOTIFICATION_SENT_OFFSITE = 'notification_sent_offsite';
}
