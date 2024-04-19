<?php

declare(strict_types=1);

namespace App\Enums;

final class OrderPaidStatusEnum extends AbstractEnum
{
    public const UNPAID = 'unpaid';
    public const PAID = 'paid';
    public const PROBLEM = 'problem';
    public const UNDONE = 'undone';
}
