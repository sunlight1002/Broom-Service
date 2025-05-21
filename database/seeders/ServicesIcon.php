<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicesIcon extends Seeder
{
    public function run()
    {
        // Example: updating icons by service template or name
        $services = [
            ['template' => "office_cleaning", 'icon' => '🏢'],
            ['template' => "בייסיק", 'icon' => '✨1️⃣'],
            ['template' => "סטנדרט", 'icon' => '2️⃣✨'],
            ['template' => "פרמיום", 'icon' => '✨3️⃣'],
            ['template' => "others", 'icon' => '✍'],
            ['template' => "polish", 'icon' => '🧽'],
            ['template' => "airbnb", 'icon' => '🏨'],
            ['template' => "ניקיון כללי", 'icon' => '🧹🪣'],
            ['template' => "after_renovation", 'icon' => '👷'],
            ['template' => "window_cleaning", 'icon' => '🪟'],
            ['template' => "2 כוכבים", 'icon' => '2⭐'],
            ['template' => "3 כוכבים", 'icon' => '3⭐'],
            ['template' => "4 כוכבים", 'icon' => '4⭐'],
            ['template' => "5 כוכבים", 'icon' => '5⭐'],
        ];

        foreach ($services as $service) {
            DB::table('services')->where('template', $service['template'])->orWhere('heb_name', $service['template'])->update([
                'icon' => $service['icon'],
            ]);
        }
    }
}
