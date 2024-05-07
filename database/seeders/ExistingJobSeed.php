<?php

namespace Database\Seeders;

use App\Models\Job;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExistingJobSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Job::whereNull('original_worker_id')->update([
            'original_worker_id' => DB::raw('worker_id')
        ]);

        Job::whereNull('original_shifts')->update([
            'original_shifts' => DB::raw('shifts')
        ]);
    }
}
