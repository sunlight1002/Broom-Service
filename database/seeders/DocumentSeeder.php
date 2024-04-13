<?php

namespace Database\Seeders;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{   
    public function run()
    {
        $docTypeArr = [
            'Pension form',
            'Training fund form',
            'Payslip'
        ];
        for ($i = 0; $i < count($docTypeArr); $i++) {
            $type  = $docTypeArr[$i];
            DocumentType::updateOrCreate([
                'slug'  => Str::slug($type),
            ], [
                'name'  => $type,
            ]);
        }
    }
}
