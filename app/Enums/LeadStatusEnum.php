<?php

declare(strict_types=1);

namespace App\Enums;

final class LeadStatusEnum extends AbstractEnum
{
    public const PENDING = 'pending';
    public const POTENTIAL = 'potential';
    public const IRRELEVANT = 'irrelevant';
    public const UNINTERESTED = 'uninterested';
    public const UNANSWERED = 'unanswered';
    public const UNANSWERED_FINAL = 'unanswered_final';
    public const POTENTIAL_CLIENT = 'potential client';
    public const PENDING_CLIENT = 'pending client';
    public const FREEZE_CLIENT = 'freeze client';
    public const ACTIVE_CLIENT = 'active client';
    public const UNHAPPY = 'unhappy';
    public const PRICE_ISSUE = 'price issue';
    public const MOVED = 'moved';
    public const ONE_TIME = 'one-time';
    public const PAST = 'past';
}
