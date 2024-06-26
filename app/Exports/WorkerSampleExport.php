<?php

namespace App\Exports;

use App\Models\Countries;
use App\Models\ManpowerCompany;
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

class WorkerSampleExport implements FromCollection, WithHeadings, WithEvents, WithStrictNullComparison
{
    protected $countryOptions = [];
    protected $manpowerOptions = [];
    protected $serviceNameOptions = [];

    public function __construct()
    {
        $this->countryOptions = Countries::select('code')->get()->pluck('code')->toArray();
        $this->manpowerOptions = ManpowerCompany::select('name')->get()->pluck('name')->toArray();
        $this->serviceNameOptions = Services::select('name')->get()->pluck('name')->toArray();
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

                $genderOptions = ['Female', 'Male'];
                $languageOptions = ['Hebrew', 'English', 'Russian', 'Spanish'];
                $companyTypeOptions = ['My Company', 'Manpower'];
                $yesNoOptions = ['Yes', 'No'];
                $statusOptions = ['Enable', 'Disable'];

                // set dropdown list for first data row
                $validationE = $event->sheet->getCell("E2")->getDataValidation();
                $validationJ = $event->sheet->getCell("J2")->getDataValidation();
                $validationK = $event->sheet->getCell("K2")->getDataValidation();
                $validationM = $event->sheet->getCell("M2")->getDataValidation();
                $validationN = $event->sheet->getCell("N2")->getDataValidation();
                $validationP = $event->sheet->getCell("P2")->getDataValidation();
                $validationQ = $event->sheet->getCell("Q2")->getDataValidation();
                $validationR = $event->sheet->getCell("R2")->getDataValidation();
                $validationS = $event->sheet->getCell("S2")->getDataValidation();
                $validationT = $event->sheet->getCell("T2")->getDataValidation();
                $validationU = $event->sheet->getCell("U2")->getDataValidation();
                $validationV = $event->sheet->getCell("V2")->getDataValidation();

                $validationE = $this->setDropDownValidation($validationE, $genderOptions);
                $validationJ = $this->setDropDownValidation($validationJ, $languageOptions);
                $validationK = $this->setDropDownValidation($validationK, $this->countryOptions);
                $validationM = $this->setDropDownValidation($validationM, $companyTypeOptions);
                $validationN = $this->setDropDownValidation($validationN, $this->manpowerOptions);
                $validationP = $this->setDropDownValidation($validationP, $yesNoOptions);
                $validationQ = $this->setDropDownValidation($validationQ, $yesNoOptions);
                $validationR = $this->setDropDownValidation($validationR, $statusOptions);
                $validationS = $this->setDropDownValidation($validationS, $yesNoOptions);
                $validationT = $this->setDropDownValidation($validationT, $yesNoOptions);
                $validationU = $this->setDropDownValidation($validationU, $yesNoOptions);
                $validationV = $this->setDropDownValidation($validationV, $yesNoOptions);

                // clone validation to remaining rows
                for ($i = 3; $i <= $row_count; $i++) {
                    $event->sheet->getCell("E{$i}")->setDataValidation(clone $validationE);
                    $event->sheet->getCell("J{$i}")->setDataValidation(clone $validationJ);
                    $event->sheet->getCell("K{$i}")->setDataValidation(clone $validationK);
                    $event->sheet->getCell("M{$i}")->setDataValidation(clone $validationM);
                    $event->sheet->getCell("N{$i}")->setDataValidation(clone $validationN);
                    $event->sheet->getCell("P{$i}")->setDataValidation(clone $validationP);
                    $event->sheet->getCell("Q{$i}")->setDataValidation(clone $validationQ);
                    $event->sheet->getCell("R{$i}")->setDataValidation(clone $validationR);
                    $event->sheet->getCell("S{$i}")->setDataValidation(clone $validationS);
                    $event->sheet->getCell("T{$i}")->setDataValidation(clone $validationT);
                    $event->sheet->getCell("U{$i}")->setDataValidation(clone $validationU);
                    $event->sheet->getCell("V{$i}")->setDataValidation(clone $validationV);
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
                "email" => "test@gmail.com",
                "phone" => "1234567890",
                "gender" => "Female",
                "role" => "Test",
                "payment_per_hour" => "35",
                "worker_id" => "123456",
                "password" => "12345@test",
                "language" => "Hebrew",
                "country" => $this->countryOptions[0],
                "renewal_of_visa" => "2025-07-27",
                "company_type" => "My Company",
                "manpower" => "",
                "full_address" => "HaHashmal St 5, Tel Aviv-Yafo, Israel",
                "are_you_afraid_of_dog" => "Yes",
                "are_you_afraid_of_cat" => "No",
                "status" => "Enable",
                "form101" => "No",
                "contract" => "Yes",
                "saftey_and_gear" => "No",
                "insurance" => "Yes",
            ]
        ]);
    }
}
