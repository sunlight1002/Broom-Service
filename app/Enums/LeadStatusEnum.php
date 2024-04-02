<?php

declare(strict_types=1);

namespace App\Enums;

final class LeadStatusEnum extends AbstractEnum
{
    public const UNINTERESTED = 'Uninterested';
    public const PENDING = 'Pending';
    public const MEETING_PENDING = 'Meeting pending';
    public const MEETING_SET = 'Meeting Set';
    public const MEETING_RESCHEDULED = 'Meeting Rescheduled';
    public const MEETING_REJECTED = 'Meeting Rejected';
    public const OFFER_SENT = 'Offer Sent';
    public const OFFER_ACCEPTED = 'Offer Accepted';
    public const OFFER_REJECTED = 'Offer Rejected';
    public const CONTRACT_ACCEPTED = 'Contract Accepted';
    public const CONTRACT_REJECTED = 'Contract Rejected';
    public const CONTRACT_VERIFIED = 'Contract Verified';
    public const CONTRACT_UNVERIFIED = 'Contract Unverified';
}
