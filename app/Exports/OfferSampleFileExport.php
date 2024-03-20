<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Illuminate\Support\Str;

class OfferSampleFileExport implements FromCollection, WithHeadings, WithStrictNullComparison
{
    public function headings(): array
    {
        return array_map(function ($e) {
            return Str::title(Str::replace("_", " ", $e));
        }, array_keys(!empty($this->collection()->first()) ? $this->collection()->first() : []));
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect([
            [
                "client_email" => "test@gmail.com",
                "property_name" => "Home",
                "service_name" => "5 Star",
                "name" => "Office Cleaning",
                "frequency" => "One Time",
                "type" => "fixed",
                "fixed_price" => "100",
                "rateperhour" => "",
                "other_title" => "",
                "start_date" => "2024-03-30",
                "worker_hours" => "5,7,10",
            ]
        ]);
    }
}
