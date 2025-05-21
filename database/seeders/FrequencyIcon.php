<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FrequencyIcon extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
   public function run()
    {
        // Example: updating icons by service template or name
        $services = [
            // ['template' => "3w", 'icon' => '¼'],
            ['template' => "3w", 'icon' => '⅓'],
            ['template' => "2w", 'icon' => '½'],
            ['template' => "חד פעמי", 'icon' => '🙋'],
            ['template' => "לפי דרישה", 'icon' => '📣'],
            ['template' => "m", 'icon' => '🗓1️⃣'],
            ['template' => "2m", 'icon' => '🗓2️⃣'],
            ['template' => "3m", 'icon' => '🗓3️⃣'],
            ['template' => "פעם בשבוע", 'icon' => '1️⃣'],
            ['template' => "פעמיים בשבוע", 'icon' => '2️⃣'],
            ['template' => "3 פעמים בשבוע", 'icon' => '3️⃣'],
            ['template' => "4 פעמים בשבוע", 'icon' => '4️⃣'],
            ['template' => "5 פעמים בשבוע", 'icon' => '5️⃣'],
            ['template' => "6 פעמים בשבוע", 'icon' => '6️⃣'],
        ];

        foreach ($services as $service) {
            DB::table('service_schedules')->where('period', $service['template'])->orWhere('name_heb', $service['template'])->update([
                'icon' => $service['icon'],
            ]);
        }
    }
}
