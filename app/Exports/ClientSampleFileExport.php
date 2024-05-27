<?php

namespace App\Exports;

use App\Models\Services;
use App\Models\ServiceSchedule;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ClientSampleFileExport implements FromCollection, WithHeadings, WithEvents, WithStrictNullComparison
{
    protected $serviceNameOptions = [];
    protected $frequencyOptions = [];

    public function __construct()
    {
        $this->serviceNameOptions = Services::select('name')->get()->pluck('name')->toArray();
        $this->frequencyOptions = ServiceSchedule::select('name')->get()->pluck('name')->toArray();
    }

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

    public function registerEvents(): array
    {
        return [
            // handle by a closure.
            AfterSheet::class => function (AfterSheet $event) {
                // get layout counts (add 1 to rows for heading row)
                $row_count = 2000;

                $paymentMethodOptions = ['Credit Card', 'Money Transfer', 'By Cheque', 'By Cash',];
                $languageOptions = ['Hebrew', 'English'];
                $colorOptions = ['White', 'Green', 'Blue', 'Violet', 'Red', 'Orange', 'Yellow'];
                $statusOptions = ['Lead', 'Potential Customer', 'Customer'];
                $yesNoOptions = ['Yes', 'No'];
                $preferTypeOptions = ['Female', 'Male', 'Both'];
                $rateTypeOptions = ['fixed', 'hourly'];

                // set dropdown list for first data row
                $validationN = $event->sheet->getCell("N2")->getDataValidation();
                $validationO = $event->sheet->getCell("O2")->getDataValidation();
                $validationP = $event->sheet->getCell("P2")->getDataValidation();
                $validationQ = $event->sheet->getCell("Q2")->getDataValidation();
                $validationS = $event->sheet->getCell("S2")->getDataValidation();
                $validationT = $event->sheet->getCell("T2")->getDataValidation();
                $validationU = $event->sheet->getCell("U2")->getDataValidation();
                $validationV = $event->sheet->getCell("V2")->getDataValidation();
                $validationW = $event->sheet->getCell("W2")->getDataValidation();
                $validationX = $event->sheet->getCell("X2")->getDataValidation();
                $validationY = $event->sheet->getCell("Y2")->getDataValidation();
                $validationAD = $event->sheet->getCell("AD2")->getDataValidation();

                $validationN = $this->setDropDownValidation($validationN, $paymentMethodOptions);
                $validationO = $this->setDropDownValidation($validationO, $languageOptions);
                $validationP = $this->setDropDownValidation($validationP, $colorOptions);
                $validationQ = $this->setDropDownValidation($validationQ, $statusOptions);
                $validationS = $this->setDropDownValidation($validationS, $yesNoOptions);
                $validationT = $this->setDropDownValidation($validationT, $yesNoOptions);
                $validationU = $this->setDropDownValidation($validationU, $preferTypeOptions);
                $validationV = $this->setDropDownValidation($validationV, $yesNoOptions);
                $validationW = $this->setDropDownValidation($validationW, $this->serviceNameOptions);
                $validationX = $this->setDropDownValidation($validationX, $this->frequencyOptions);
                $validationY = $this->setDropDownValidation($validationY, $rateTypeOptions);
                $validationAD = $this->setDropDownValidation($validationAD, $yesNoOptions);

                // clone validation to remaining rows
                for ($i = 3; $i <= $row_count; $i++) {
                    $event->sheet->getCell("N{$i}")->setDataValidation(clone $validationN);
                    $event->sheet->getCell("O{$i}")->setDataValidation(clone $validationO);
                    $event->sheet->getCell("P{$i}")->setDataValidation(clone $validationP);
                    $event->sheet->getCell("Q{$i}")->setDataValidation(clone $validationQ);
                    $event->sheet->getCell("S{$i}")->setDataValidation(clone $validationS);
                    $event->sheet->getCell("T{$i}")->setDataValidation(clone $validationT);
                    $event->sheet->getCell("U{$i}")->setDataValidation(clone $validationU);
                    $event->sheet->getCell("V{$i}")->setDataValidation(clone $validationV);
                    $event->sheet->getCell("W{$i}")->setDataValidation(clone $validationW);
                    $event->sheet->getCell("X{$i}")->setDataValidation(clone $validationX);
                    $event->sheet->getCell("Y{$i}")->setDataValidation(clone $validationY);
                    $event->sheet->getCell("AD{$i}")->setDataValidation(clone $validationAD);
                }
            },
        ];
    }

    private function setDropDownValidation($validation, $options)
    {
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Input error');
        $validation->setError('Value is not in list.');
        $validation->setPromptTitle('Pick from list');
        $validation->setPrompt('Please pick a value from the drop-down list.');
        $validation->setFormula1(sprintf('"%s"', implode(',', $options)));

        return $validation;
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
                "payment_method" => "Credit Card",
                "language" => "Hebrew",
                "color" => "Green",
                "status" => "Customer",
                "full_address" => "HaHashmal St 5, Tel Aviv-Yafo, Israel",
                "dog_in_the_property" => "Yes",
                "cat_in_the_property" => "No",
                "prefered_type" => "Male",
                "has_offer" => "Yes",
                "service_name" => $this->serviceNameOptions[0],
                "frequency" => $this->frequencyOptions[0],
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
