<?php

namespace App\Traits;

use App\Models\ClientCard;

trait ClientCardTrait
{
    private function getClientCard($clientID)
    {
        return ClientCard::query()
            ->where('client_id', $clientID)
            ->where(function ($q) {
                $q->where('is_default', true)
                    ->orWhere('is_default', false);
            })
            ->orderBy('is_default', 'desc')
            ->first();
    }
}
