<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;

class FixContractsSeeder extends Seeder
{
    public function run()
    {
        // Fetch data from the old database's contracts table
        $oldContracts = DB::connection('old_db')->table('contracts')
                        ->select('id', 'name_on_card', 'card_sign', 'card_type', 'cvv')
                        ->get();

        // Loop through each old contract and update the corresponding record in the new database
        foreach ($oldContracts as $oldContract) {
            // Find the contract in the new database by ID
            $contract = Contract::find($oldContract->id);

            if ($contract) {
                // Update the form_data column in JSON format
                $contract->update([
                    'form_data' => json_encode([
                        'card_type' => $oldContract->card_type,
                        'card_holder_name' => $oldContract->name_on_card, // Mapping name_on_card to card_holder_name
                        'cvv' => $oldContract->cvv,
                        'card_signature' => $oldContract->card_sign, // Mapping card_sign to card_signature
                    ])
                ]);
            }
        }
    }
}
