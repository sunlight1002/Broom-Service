<?php

declare(strict_types=1);

namespace App\Enums;

final class ContractStatusEnum extends AbstractEnum
{
    public const VERIFIED = 'verified';
    public const UN_VERIFIED = 'un-verified';
    public const NOT_SIGNED = 'not-signed';
    public const DECLINED = 'declined';
}
