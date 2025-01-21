<?php

declare(strict_types=1);

namespace App\Enums;

final class ClientMetaEnum extends AbstractEnum
{
    public const NOTIFICATION_SENT_24_HOURS = 'notification_sent_24_hour';
    public const NOTIFICATION_SENT_OFFSITE_7DAYS = 'notification_sent_offsite_7days';
    public const NOTIFICATION_SENT_OFFSITE_3DAYS = 'notification_sent_offsite_3days';
    public const NOTIFICATION_SENT_OFFSITE_24HOURS = 'notification_sent_offsite_24hours';
    public const NOTIFICATION_SENT_3_DAY = 'notification_sent_3_day';
    public const NOTIFICATION_SENT_7_DAY = 'notification_sent_7_day';
    public const NOTIFICATION_SENT_OFFSITE = 'notification_sent_offsite';
    public const NOTIFICATION_SENT_CONTRACT24HOUR = 'notification_sent_contract24hour';
    public const NOTIFICATION_SENT_CONTRACT3DAY = 'notification_sent_contract3day';
    public const NOTIFICATION_SENT_CONTRACT7DAY = 'notification_sent_contract7day';
    public const STOP_NOTIFICATION = 'stop_notification';
    public const NOTIFICATION_SENT_UNANSWERED_3DAYS = 'notification_sent_unanswered_3days';
    public const NOTIFICATION_SENT_UNANSWERED_7DAYS = 'notification_sent_unanswered_7days';
    public const NOTIFICATION_SENT_UNANSWERED_8DAYS = 'notification_sent_unanswered_8days';
    public const NOTIFICATION_SENT_CONTRACT_NOTSIGNED_24HOURS = 'notification_sent_contract_notsigned_24hours';
    public const NOTIFICATION_SENT_CONTRACT_NOTSIGNED_3DAYS = 'notification_sent_contract_notsigned_3days';
    public const NOTIFICATION_SENT_CONTRACT_NOTSIGNED_7DAYS = 'notification_sent_contract_notsigned_7days';
}
