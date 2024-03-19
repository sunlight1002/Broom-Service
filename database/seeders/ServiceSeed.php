<?php

namespace Database\Seeders;

use App\Models\Services;
use Illuminate\Database\Seeder;

class ServiceSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    private static $serviceArray = [
        [
            'name'         => 'Office Cleaning',
            'heb_name'     => 'ניקיון משרד',
            'template'     => 'office_cleaning',
            'status'       => 1,
            'color_code'   => '#FFA500',
        ], [
            'name'         => 'Cleaning After Renovation',
            'heb_name'     => 'ניקיון לאחר שיפוץ',
            'template'     => 'after_renovation',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => 'Thorough Cleaning - Basic',
            'heb_name'     => 'בייסיק',
            'template'     => 'thorough_cleaning',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => '5 Star',
            'heb_name'     => '5 כוכבים',
            'template'     => 'regular',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => '4 Star',
            'heb_name'     => '4 כוכבים',
            'template'     => 'regular',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => '3 Star',
            'heb_name'     => '3 כוכבים',
            'template'     => 'regular',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => '2 Star',
            'heb_name'     => '2 כוכבים',
            'template'     => 'regular',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => 'window cleaning',
            'heb_name'     => 'ניקוי חלונות',
            'template'     => 'window_cleaning',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => 'Floor Polishing',
            'heb_name'     => 'פוליש\ חידוש רצפות',
            'template'     => 'polish',
            'status'       => 1
        ], [
            'name'         => 'Others',
            'heb_name'     => 'אחרים',
            'template'     => 'others',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => 'Thorough Cleaning - Standard',
            'heb_name'     => 'סטנדרט',
            'template'     => 'thorough_cleaning',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => 'Thorough Cleaning - Premium',
            'heb_name'     => 'פרמיום',
            'template'     => 'thorough_cleaning',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ], [
            'name'         => 'General Cleaning',
            'heb_name'     => 'ניקיון כללי',
            'template'     => 'regular',
            'status'       => 1,
            'color_code'   => '#00FF00',
        ]
    ];
    public function run()
    {
        for ($i = 0; $i < count($this::$serviceArray); $i++) {
            $service  = $this::$serviceArray[$i];
            Services::updateOrCreate([
                'name'         => $service['name'],
            ], [
                'heb_name'     => $service['heb_name'],
                'template'     => $service['template'],
                'status'       => $service['status'],
                'color_code'   => isset($service['color_code']) ? $service['color_code'] : '#FFFFFF'
            ]);
        }
    }
}
