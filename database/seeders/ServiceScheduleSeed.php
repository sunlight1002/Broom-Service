<?php

namespace Database\Seeders;

use App\Models\ServiceSchedule;
use Illuminate\Database\Seeder;

class ServiceScheduleSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    private static $service_schedule_array = [
        [
            'name'         => 'One Time',
            'name_heb'     => 'חד פעמי',
            'cycle'        => 1,
            'period'       => 'na',
            'status'       => 1,
            'color_code'   => '#808080'
        ],[
            'name'         => 'Once Time week',
            'name_heb'     => 'פעם בשבוע',
            'cycle'        => 1,
            'period'       => 'w',
            'status'       => 1,
            'color_code'   => '#FFFFFF'
        ],[
            'name'         => 'Twice in week',
            'name_heb'     => 'פעמיים בשבוע',
            'cycle'        => 2,
            'period'       => 'w',
            'status'       => 1,
            'color_code'   => '#ADD8E6'
        ],[
            'name'         => '3 times a week',
            'name_heb'     => '3 פעמים בשבוע',
            'cycle'        => 3,
            'period'       => 'w',
            'status'       => 1
        ],[
            'name'         => '4 times a week',
            'name_heb'     => '4 פעמים בשבוע',
            'cycle'        => 4,
            'period'       => 'w',
            'status'       => 1
        ],[
            'name'         => '5 times a week',
            'name_heb'     => '5 פעמים בשבוע',
            'cycle'        => 5,
            'period'       => 'w',
            'status'       => 1
        ],[
            'name'         => 'Once in every two weeks',
            'name_heb'     => 'פעם בשבועיים',
            'cycle'        => 1,
            'period'       => '2w',
            'status'       => 1,
            'color_code'   => '#0000FF'
        ],[
            'name'         => 'Once in every three weeks',
            'name_heb'     => 'פעם בשלושה שבועות',
            'cycle'        => 1,
            'period'       => '3w',
            'status'       => 1
        ],[
            'name'         => 'Once a month',
            'name_heb'     => 'פעם בחודש',
            'cycle'        => 1,
            'period'       => 'm',
            'status'       => 1
        ],[
            'name'         => 'Once every 2 Months',
            'name_heb'     => 'פעם בחודשיים',
            'cycle'        => 1,
            'period'       => '2m',
            'status'       => 1
        ],[
            'name'         => 'Once every 3 Months',
            'name_heb'     => 'פעם ב3 חודשים',
            'cycle'        => 1,
            'period'       => '3m',
            'status'       => 1
        ]
        ];
    public function run()
    {
        for ($i=0; $i < count($this::$service_schedule_array); $i++) {
            $service_schedule  = $this::$service_schedule_array[$i];
            ServiceSchedule::updateOrCreate([
                'name'         => $service_schedule['name'],
            ],[
                'name_heb'     => $service_schedule['name_heb'],
                'cycle'     => $service_schedule['cycle'],
                'period'       => $service_schedule['period'],
                'status'     => $service_schedule['status'],
                'color_code'   => isset($service_schedule['color_code'])?$service_schedule['color_code']:'#FFFFFF'
            ]);
        }
    }
}
