<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceSchedule;

class ServiceScheduleSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schedules = [
            [
                'id' => 1,
                'name' => 'One Time',
                'name_heb' => 'חד פעמי',
                'icon' => '🙋',
                'cycle' => 1,
                'period' => 'na',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 3,
                'name' => 'Once Time week',
                'name_heb' => 'פעם בשבוע',
                'icon' => '1️⃣',
                'cycle' => 1,
                'period' => 'w',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-04-17 06:08:06',
            ],
            [
                'id' => 4,
                'name' => 'Twice in week',
                'name_heb' => 'פעמיים בשבוע',
                'icon' => '2️⃣',
                'cycle' => 2,
                'period' => 'w',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 5,
                'name' => '3 times a week',
                'name_heb' => '3 פעמים בשבוע',
                'icon' => '3️⃣',
                'cycle' => 3,
                'period' => 'w',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 6,
                'name' => '4 times a week',
                'name_heb' => '4 פעמים בשבוע',
                'icon' => '4️⃣',
                'cycle' => 4,
                'period' => 'w',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 7,
                'name' => '5 times a week',
                'name_heb' => '5 פעמים בשבוע',
                'icon' => '5️⃣',
                'cycle' => 5,
                'period' => 'w',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 8,
                'name' => '6 times a week',
                'name_heb' => '6 פעמים בשבוע',
                'icon' => '6️⃣',
                'cycle' => 6,
                'period' => 'w',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 9,
                'name' => 'Once in every two weeks',
                'name_heb' => 'פעם בשבועיים',
                'icon' => '½',
                'cycle' => 1,
                'period' => '2w',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 10,
                'name' => 'Once in every three weeks',
                'name_heb' => 'פעם בשלושה שבועות',
                'icon' => '⅓',
                'cycle' => 1,
                'period' => '3w',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 11,
                'name' => 'Once a month',
                'name_heb' => 'פעם בחודש',
                'icon' => '🗓1️⃣',
                'cycle' => 1,
                'period' => 'm',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 12,
                'name' => 'Once every 2 Months',
                'name_heb' => 'פעם בחודשיים',
                'icon' => '🗓2️⃣',
                'cycle' => 1,
                'period' => '2m',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 13,
                'name' => 'Once every 3 Months',
                'name_heb' => 'פעם ב3 חודשים',
                'icon' => '🗓3️⃣',
                'cycle' => 1,
                'period' => '3m',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2023-03-21 05:38:28',
                'updated_at' => '2023-03-21 05:38:28',
            ],
            [
                'id' => 14,
                'name' => 'On demand',
                'name_heb' => 'לפי דרישה',
                'icon' => '📣',
                'cycle' => 0,
                'period' => 'na',
                'status' => 1,
                'color_code' => '#FFFFFF',
                'created_at' => '2024-12-26 12:19:43',
                'updated_at' => '2024-12-26 12:19:43',
            ],
        ];

        foreach ($schedules as $schedule) {
            ServiceSchedule::updateOrCreate(['id' => $schedule['id']], $schedule);
        }
    }
}
