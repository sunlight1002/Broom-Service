<?php

declare(strict_types=1);

namespace App\Enums;

final class JobNotificationState extends AbstractEnum
{
    public const NOT_NOTIFIED = 0;
    public const WORKER_NOTIFIED = 1;
    public const ADMIN_NOTIFIED = 2;
}
