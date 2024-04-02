<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Illuminate\Support\Str;

class ClientSampleFileExport implements FromCollection, WithHeadings, WithStrictNullComparison
{
    /**
     * Set export header
     * 
     * @return array
     */
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
                "first_name" => "Test1",
                "last_name" => "Test2",
                "invoice_name" => "Example",
                "primary_email" => "test@gmail.com",
                "password" => "12345@test",
                "primary_phone" => "1234567890",
                "alternate_email_1" => "test1@gmail.com",
                "person_name_1" => "Test2",
                "alternate_phone_1" => "1234567890",
                "alternate_email_2" => "test2@gmail.com",
                "person_name_2" => "Test3",
                "alternate_phone_2" => "1234567890",
                "date_of_birth" => "1988-07-27",
                "payment_method" => "cc",
                "language" => "heb",
                "color" => "#28a745",
                "status" => "Customer",
                "full_address" => "Test Test",
                "lat" => 23.0235188,
                "lng" => 72.5323136,
                "address_name" => "Home1",
                "floor" => "2nd",
                "apt_number_and_apt_name" => "456, Test",
                "enterance_code" => "E123",
                "zip_code" => "123456",
                "city" => "",
                "dog_in_the_property" => "Yes",
                "cat_in_the_property" => "No",
                "prefered_type" => "Male",
                "has_offer" => "Yes",
                "service_name" => "5 Star",
                "name" => "Office Cleaning",
                "frequency" => "One Time",
                "type" => "fixed",
                "fixed_price" => "100",
                "rateperhour" => "",
                "other_title" => "",
                "worker_hours" => "5,7,10",
                "has_contract" => "Yes",
                "additional_address" => "Home Address",
                "card_number" => "0000000000000000",
                "card_type" => "Visa",
                "card_holder_id" => "123456789",
                "card_holder_name" => "Card Holder",
                "valid" => "12/25",
                "cvv" => "123",
            ]
        ]);
    }
}
