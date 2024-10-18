<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MonthlyReportExport implements FromArray, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Number',
            'Passport Id',
            'Last Name',
            'First Name',
            'Role',
            'Total Hours Worked',
            'Normal Rate Hours (100%)',
            'Hours at 125% Salary',
            'Hours at 150% Salary',
            'Holiday/Weekend Hours at 175% Salary',
            'Holiday/Weekend Hours at 200% Salary',
            'Total Days',
            'Hourly Rate',
            'Salary',
            'Normal Payment',
            '125% Bonus Payment',
            '150% Bonus Payment',
            'Holiday Payment at 175%',
            'Holiday Payment at 200%',
            'Recovery Fee',
            'Public Holiday Bonus',
            'Insurance',
            'Sick Leave Payment',
            'Total Payment',
            'Loan',
            'Net Payment',
            'Doctor Report',
            'Form 101', 
        ];
    }
}
