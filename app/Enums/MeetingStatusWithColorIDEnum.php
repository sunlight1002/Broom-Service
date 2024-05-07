<?php

declare(strict_types=1);

namespace App\Enums;

final class MeetingStatusWithColorIDEnum extends AbstractEnum
{
    public const status = [
        'pending' => '1',
        'confirmed' => '6',
        'declined' => '4',
        'completed' => '2',
        'rescheduled' => '11',
    ];
}
