<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addDefaultServiceComments extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $star4commentsMap = [
            1 => "doors + electricity light switch  + floor side panels",
            2 => "ac grills + air vents",
            3 => "basic windows cleaning",
            4 => "fridge",
        ];

        $star5commentsMap = [
            "default" => [
                "verify from supervisor",
            ],
            1 => "doors + electricity light switch  + floor side panels",
            2 => "ac grills + air vents",
            3 => "basic windows cleaning",
            4 => "fridge",
        ];

        $services = [
            ['service' => 7, 'comments' => $star4commentsMap],
            ['service' => 6, 'comments' => $star5commentsMap],
        ];

        foreach ($services as $service) {
            DB::table('default_service_comments')->updateOrInsert(
                ['service_id' => $service['service']], // Unique key for match
                [
                    'subservice_id' => $service['subservice_id'] ?? null,
                    'comments'    => json_encode($service['comments']),
                    'updated_at'  => now(),
                    'created_at'  => now(), // not necessary if exists, but won't harm
                ]
            );
        }

        $this->command->info('✅ Default comments seeded or updated.');
    }
}
