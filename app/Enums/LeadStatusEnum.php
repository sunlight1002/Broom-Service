<?php

declare(strict_types=1);

namespace App\Enums;

final class LeadStatusEnum extends AbstractEnum
{
    public const PENDING_LEAD = 'pending lead';
    public const POTENTIAL_LEAD = 'potential lead';
    public const IRRELEVANT = 'irrelevant';
    public const UNINTERESTED = 'uninterested';
    public const UNANSWERED = 'unanswered';
    public const POTENTIAL_CLIENT = 'potential client';
    public const PENDING_CLIENT = 'pending client';
    public const FREEZE_CLIENT = 'freeze client';
    public const ACTIVE_CLIENT = 'active client';
}
