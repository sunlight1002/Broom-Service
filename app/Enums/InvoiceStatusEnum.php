<?php

declare(strict_types=1);

namespace App\Enums;

final class InvoiceStatusEnum extends AbstractEnum
{
    public const PAID = 'Paid';
    public const UNPAID = 'Unpaid';
    public const PARTIALLY_PAID = 'Partially Paid';
}
