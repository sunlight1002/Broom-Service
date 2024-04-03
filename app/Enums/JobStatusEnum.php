<?php

declare(strict_types=1);

namespace App\Enums;

final class JobStatusEnum extends AbstractEnum
{
    public const PROGRESS = 'progress';
    public const SCHEDULED = 'scheduled';
    public const UNSCHEDULED = 'unscheduled';
    public const COMPLETED = 'completed';
    public const CANCEL = 'cancel';
}
