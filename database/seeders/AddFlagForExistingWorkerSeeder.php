<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AddFlagForExistingWorkerSeeder extends Seeder
{
    public function run()
    {
        $allWorkers = User::query()->update([
            'is_exist' => 1,
        ]);
    }
}