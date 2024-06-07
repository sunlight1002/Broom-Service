<?php

namespace Database\Seeders;

use App\Models\LeadStatus;
use Illuminate\Database\Seeder;

class ChangeLeadStatus extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        LeadStatus::where('lead_status', 'pending lead')
            ->update([
                'lead_status' => 'pending'
            ]);

        LeadStatus::where('lead_status', 'potential lead')
            ->update([
                'lead_status' => 'potential'
            ]);
    }
}
