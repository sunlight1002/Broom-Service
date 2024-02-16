<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Language::create([
            'name' => 'Hebrew',
            'code' => 'heb'
        ]);
        Language::create([
            'name' => 'english',
            'code' => 'en'
        ]);
    }
}
