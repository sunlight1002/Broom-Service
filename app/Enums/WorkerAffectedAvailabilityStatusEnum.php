<?php

declare(strict_types=1);

namespace App\Enums;

final class WorkerAffectedAvailabilityStatusEnum extends AbstractEnum
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
}
