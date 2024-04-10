<?php

declare(strict_types=1);

namespace App\Enums;

final class ChangeWorkerRequestStatusEnum extends AbstractEnum
{
    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
}
