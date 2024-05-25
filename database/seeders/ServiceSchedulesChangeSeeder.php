<?php

namespace Database\Seeders;

use App\Models\ServiceSchedule;
use Illuminate\Database\Seeder;

class ServiceSchedulesChangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ServiceSchedule::where('period', 'bdm')->delete();
        ServiceSchedule::where('period', 'D')->update(['period' => 'd']);
    }
}
