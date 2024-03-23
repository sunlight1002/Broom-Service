<?php

declare(strict_types=1);

namespace App\Enums;

final class TransactionStatusEnum extends AbstractEnum
{
    public const INITIATED = 'initiated';
    public const PENDING = 'pending';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
}
