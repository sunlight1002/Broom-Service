<?php

declare(strict_types=1);

namespace App\Enums;

final class MeetingStatusWithColorIDEnum extends AbstractEnum
{
    public const status = [
        'pending' => '3',
        'confirmed' => '2',
        'declined' => '6',
        'completed' => '2',
        'rescheduled' => '6',
    ];
}
