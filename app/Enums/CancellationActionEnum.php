<?php

declare(strict_types=1);

namespace App\Enums;

final class CancellationActionEnum extends AbstractEnum
{
    public const CANCELLATION = 'cancellation';
    public const SWITCH_WORKER = 'switch-worker';
    public const CHANGE_WORKER = 'change-worker';
    public const CHANGE_SHIFT = 'change-shift';
}
