<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\WorkerInvitationsImport;

class WorkerInvitationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Path to your Excel file
        $filePath = database_path('seeders/data/WORKERS.xlsx');

        // Import the data from the Excel file
        Excel::import(new WorkerInvitationsImport, $filePath);
    }
}
