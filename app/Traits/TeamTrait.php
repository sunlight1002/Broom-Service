<?php

namespace App\Traits;

use App\Models\ClientPropertyAddress;
use App\Models\TeamMemberAvailability;

trait TeamTrait
{
    private function getAvailabilities($teamMember)
    {
        $team_member_availabilities = $teamMember->availabilities()
            ->orderBy('date', 'asc')
            ->get(['date', 'start_time', 'end_time']);

        $availabilities = [];
        foreach ($team_member_availabilities->groupBy('date') as $date => $times) {
            $availabilities[$date] = $times->map(function ($item, $key) {
                return $item->only(['start_time', 'end_time']);
            });
        }

        $default_availabilities = $teamMember->defaultAvailabilities()
            ->orderBy('id', 'asc')
            ->get(['weekday', 'start_time', 'end_time', 'until_date'])
            ->groupBy('weekday');

        return [$availabilities, $default_availabilities];
    }

    private function saveTeamAvailabilities($teamMember, $data)
    {
        $teamMember->availabilities()->delete();

        foreach ($data['time_slots'] as $key => $availabilties) {
            $date = trim($key);

            foreach ($availabilties as $key => $availabilty) {
                TeamMemberAvailability::create([
                    'team_member_id' => $teamMember->id,
                    'date' => $date,
                    'start_time' => $availabilty['start_time'],
                    'end_time' => $availabilty['end_time'],
                    'status' => '1',
                ]);
            }
        }

        $teamMember->defaultAvailabilities()->delete();

        if (isset($data['default']['time_slots'])) {
            foreach ($data['default']['time_slots'] as $weekday => $availabilties) {
                foreach ($availabilties as $key => $timeSlot) {
                    $teamMember->defaultAvailabilities()->create([
                        'weekday' => $weekday,
                        'start_time' => $timeSlot['start_time'],
                        'end_time' => $timeSlot['end_time'],
                        'until_date' => $data['default']['until_date'],
                    ]);
                }
            }
        }
    }
}
