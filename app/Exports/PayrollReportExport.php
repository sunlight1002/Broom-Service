<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PayrollReportExport implements  FromArray, WithHeadings, WithMapping,WithStyles, WithStrictNullComparison, WithColumnFormatting,WithEvents
{
    protected $reportData;
    protected $doctorReports;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;   
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function array(): array
    {
        return $this->reportData;
    }

    /**
     * Define the headings for the Excel sheet
     */
    public function headings(): array
    {
        $headings = [
            'מס/number',
            'מספר זהות/דרכון/id passport',
            'שם משפחה/LAST NAME',
            'שם פרטי/FIRST NAME',
            'מחלקה/ ROLE',
            'סה"כ שעות/ TOTAL HOUR',
            'שעות 100%/ HOURS',
            'נוספות 125%',
            'נוספות 150%',
            '175% תוספת עבור עבודה בחגים',
            '200% תוספת עבור עבודה בחגים',
            'ימי עבודה/ TOTAL DAYS',
            'שכר לשעה/HOURLY RATE',
            'תשלום רגיל/NORMAL PAYMENT',
            'תשלום ב-125% בונוס/BONUS PAYMENT',
            'תשלום ב-150% בונוס/BONUS PAYMENT',
            'תשלום חג ב-175%/HOLIDAY PAYMENT AT 175%',
            'תשלום חג ב-200%/HOLIDAY PAYMENT AT 200%',
            'עמלת הבראה/RECOVERY FEE',
            'בונוס לחגים ציבוריים/HOLIDAY BONUS', 
            'תשלום דמי מחלה/SICK LEAVE PAYMENT',
            'סה"כ תשלום/TOTAL PAYMENT',
            'ביטוח/INSURANCE',
            'מפרעה/ ADVANCE',
            'תשלום נטו/NET PAYMENT',
            'דוח הרופא/DOCTOR REPORT',
            '101 טופס/Form 101',
            'Final Letter',

        ];

        return $headings;
    }

    public function map($row): array
    {
        $doctorReportUrl = $row['Doctor Report'] ? url(Storage::url($row['Doctor Report'])) : '';
        $form101Url = $row['Form 101'] ? url(Storage::url($row['Form 101'])) : '';
        $finalLetterUrl = $row['Final Letter'] ? url(Storage::url($row['Final Letter'])) : '';

        Log::info("FINAL LETTER URL IS:", $finalLetterUrl);

        $mappedRow = [
              $row['Number'],
              $row['Passport Id'],
              $row['Last Name' ],
              $row['First Name'],
              $row['Role' ],
              $row['Total Hours Worked'],
              $row['Normal Rate Hours (100%)'] ,
              $row['Hours at 125% Salary'] ,
              $row['Hours at 150% Salary'],
              $row['Holiday/Weekend Hours at 175% Salary'],
              $row['Holiday/Weekend Hours at 200% Salary'],
              $row['Total Days'],
              $row['Hourly Rate'] ,
              $row['Normal Payment'],
              $row['125% Bonus Payment'],
              $row['150% Bonus Payment' ],
              $row['Holiday Payment at 175%'],
              $row['Holiday Payment at 200%'],
              $row['Recovery Fee'],
              $row['Public Holiday Bonus'],
              $row['Sick Leave Payment'],
              $row['Total Payment'],
              $row['Insurance'],
              $row['loan'],
              $row['Net Payment'],
              $doctorReportUrl,
              $form101Url,
              $finalLetterUrl,
        ];

            return $mappedRow;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $doctorReportColumn = 'Z'; // Adjust if your Doctor Report column is different

                // Apply hyperlinks to the "Doctor Report" column
                for ($row = 2; $row <= $lastRow; $row++) {
                    $cell = $doctorReportColumn . $row;
                    $url = $sheet->getCell($cell)->getValue();

                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        $sheet->getCell($cell)->setHyperlink(new Hyperlink($url, 'Click here'));
                    }
                }
            },
        ];
    }


    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        // Apply RTL alignment to all cells
        $sheet->getStyle('A:Z')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A:Z')->getAlignment()->setTextRotation(0);
        $sheet->getStyle('A:Z')->getAlignment()->setWrapText(true);

        // Apply RTL alignment specifically to headers
        $sheet->getStyle('A1:Z1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A1:Z1')->getAlignment()->setTextRotation(0);
        $sheet->getStyle('A1:Z1')->getAlignment()->setWrapText(true);


        $sheet->getStyle('F1:Z1')->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle('F1:Z1')->getFill()->getStartColor()->setARGB('33D4FF'); // Light grey background

        // Set font styles for headers
        $sheet->getStyle('A1:Z1')->getFont()->setBold(true);

        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'], // Black color
                ],
            ],
        ];
        $sheet->getStyle('A1:Z1')->applyFromArray($borderStyle);

        $columnWidths = [
            'A' => 10, 'B' => 15, 'C' => 20, 'D' => 20, 'E' => 10, 'F' => 10, 'G' => 10, 'H' => 10,
            'I' => 10, 'J' => 10, 'K' => 10, 'L' => 10, 'M' => 10, 'N' => 10, 'O' => 10, 'P' => 10,
            'Q' => 10, 'R' => 10, 'S' => 10, 'T' => 10, 'U' => 10, 'V' => 15, 'W' => 10, 'X' => 10,
            'Y' => 15, 'Z' => 25
        ];
        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

         $sheet->getRowDimension(1)->setRowHeight(95);

         $sheet->freezePane('A2'); 
        return [];
    }
    public function columnFormats(): array
    {
        return [
            // Specify column formats if needed
            'Z' => NumberFormat::FORMAT_TEXT, // Format 'Doctor Report' column as text to display hyperlinks correctly
        ];
    }
}