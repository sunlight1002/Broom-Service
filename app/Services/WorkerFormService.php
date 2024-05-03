<?php

namespace App\Services;

use App\Helpers\PDF as TCPPDF;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class WorkerFormService
{
    public function generateForm101PDF($form, $outputName)
    {
        $formData = $form->data;

        $financialYear = $form->created_at->year;
        if (!Storage::drive('public')->exists('signed-docs')) {
            Storage::drive('public')->makeDirectory('signed-docs');
        }

        $filePath = public_path('pdfs/tofes-101.pdf');
        // $font = 'helvetica';
        $pdf = new TCPPDF(null, 'px');
        $pdf->SetAutoPageBreak(FALSE, PDF_MARGIN_BOTTOM);
        $pdf->numPages = $pdf->setSourceFile($filePath);
        $pdf->SetTextColor(0, 7, 224);

        $total_page = 0;
        foreach (range(1, $pdf->numPages, 1) as $page) {
            $pdf->_tplIdx = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($pdf->_tplIdx);
            $pdf->AddPage(self::orientation($size['w'], $size['h']), array($size['w'], $size['h']), true);
            $pdf->useTemplate($pdf->_tplIdx);

            if ($page == 1) {
                $text = (string)$financialYear;
                $text = $this->addWhiteSpaceBetweenChars($text, ' &nbsp;');
                $w = 198;
                $h = 25;
                $x = 222;
                $y = 83;
                $fontsize = 14;

                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                if (isset($formData['employeeDob'])) {
                    $isDate = true;

                    try {
                        Carbon::parse($formData['employeeDob']);
                    } catch (\Throwable $th) {
                        //throw $th;
                        $isDate = false;
                    }

                    if ($isDate) {
                        $text = (string)Carbon::parse($formData['employeeDob'])->format('dmY');
                        $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                        $w = 198;
                        $h = 98;
                        $x = 117;
                        $y = 220;
                        $fontsize = 14;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                }

                if (isset($formData['employeeFirstName'])) {
                    $text = (string)$formData['employeeFirstName'];
                    $w = 198;
                    $h = 98;
                    $x = 237;
                    $y = 220;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeLastName'])) {
                    $text = (string)$formData['employeeLastName'];
                    $w = 198;
                    $h = 98;
                    $x = 347;
                    $y = 220;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeIdNumber'])) {
                    $text = (string)$formData['employeeIdNumber'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 435;
                    $y = 220;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeDateOfAliyah'])) {
                    $isDate = true;

                    try {
                        Carbon::parse($formData['employeeDateOfAliyah']);
                    } catch (\Throwable $th) {
                        //throw $th;
                        $isDate = false;
                    }

                    if ($isDate) {
                        $text = (string)Carbon::parse($formData['employeeDateOfAliyah'])->format('dmY');
                        $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                        $w = 198;
                        $h = 98;
                        $x = 27;
                        $y = 220;
                        $fontsize = 14;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                }

                if (isset($formData['employeePostalCode'])) {
                    $text = (string)$formData['employeePostalCode'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 26;
                    $y = 243;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeCity'])) {
                    $text = (string)$formData['employeeCity'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 126;
                    $y = 243;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeHouseNo'])) {
                    $text = (string)$formData['employeeHouseNo'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 202;
                    $y = 243;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeStreet'])) {
                    $text = (string)$formData['employeeStreet'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 266;
                    $y = 243;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeePassportNumber'])) {
                    $text = (string)$formData['employeePassportNumber'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 391;
                    $y = 250;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeePassportNumber'])) {
                    $text = (string)$formData['employeePassportNumber'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 391;
                    $y = 250;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (
                    isset($formData['employeeHealthFundMember']) &&
                    $formData['employeeHealthFundMember'] === 'Yes'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 125;
                    $y = 293;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                    if (isset($formData['employeeHealthFundname'])) {
                        $text = (string)$formData['employeeHealthFundname'];
                        $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                        $w = 150;
                        $h = 50;
                        $x = 25;
                        $y = 286;
                        $fontsize = 14;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                } else {
                    $w = 4;
                    $h = 4;
                    $x = 125;
                    $y = 280;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['employeeCollectiveMoshavMember']) &&
                    $formData['employeeCollectiveMoshavMember'] === 'Yes'
                ) {
                    if (
                        isset($formData['employeemyIncomeToKibbutz']) &&
                        $formData['employeemyIncomeToKibbutz'] === 'Yes'
                    ) {
                        $w = 4;
                        $h = 4;
                        $x = 250;
                        $y = 282;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    } else {
                        $w = 4;
                        $h = 4;
                        $x = 272;
                        $y = 295;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }
                } else {
                    $w = 4;
                    $h = 4;
                    $x = 272;
                    $y = 282;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['employeeIsraeliResident']) &&
                    $formData['employeeIsraeliResident'] === 'Yes'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 311;
                    $y = 282;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } else {
                    $w = 4;
                    $h = 4;
                    $x = 311;
                    $y = 295;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Single'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 491;
                    $y = 281;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } elseif (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Married'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 432;
                    $y = 281;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } elseif (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Divorced'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 365;
                    $y = 281;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } elseif (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Widowed'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 491;
                    $y = 294;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } elseif (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Separated'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 444;
                    $y = 294;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['employeeSex']) &&
                    $formData['employeeSex'] === 'Male'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 530;
                    $y = 282;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } else {
                    $w = 4;
                    $h = 4;
                    $x = 530;
                    $y = 295;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (isset($formData['DateOfBeginningWork'])) {
                    $isDate = true;

                    try {
                        Carbon::parse($formData['DateOfBeginningWork']);
                    } catch (\Throwable $th) {
                        //throw $th;
                        $isDate = false;
                    }

                    if ($isDate) {
                        $text = (string)Carbon::parse($formData['DateOfBeginningWork'])->format('dmY');
                        $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                        $w = 198;
                        $h = 98;
                        $x = 28;
                        $y = 377;
                        $fontsize = 14;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Monthly salary'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 240;
                    $y = 360;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Salary for additional employment'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 240;
                    $y = 372;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Partial salary'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 240;
                    $y = 384;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Wage (Daily rate of pay)'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 240;
                    $y = 396;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['allowance']) &&
                    $formData['allowance'] === true
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 240;
                    $y = 408;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['scholarship']) &&
                    $formData['scholarship'] === true
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 240;
                    $y = 420;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }
            } else if ($page == 2) {
                if (isset($formData['employeeIdNumber'])) {
                    $text = (string)$formData['employeeIdNumber'];
                    $w = 198;
                    $h = 98;
                    $x = 95;
                    $y = 13;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['date'])) {
                    $isDate = true;

                    try {
                        Carbon::parse($formData['date']);
                    } catch (\Throwable $th) {
                        //throw $th;
                        $isDate = false;
                    }

                    if ($isDate) {
                        $text = (string)Carbon::parse($formData['date'])->format('d/m/Y');
                        $w = 198;
                        $h = 98;
                        $x = 148;
                        $y = 641;
                        $fontsize = 14;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                }

                if (isset($formData['signature'])) {
                    $img = '<img src="' . $formData['signature'] . '" width="150" height="50">';
                    $pdf->writeHTML($img, true, false, true, false, '');
                }
            }
        }
        $total_page += (int)$pdf->numPages;

        $output = $pdf->Output('', 'S');
        Storage::disk('public')->put("signed-docs/{$outputName}", $output);

        return $output;
    }

    public function addWhiteSpaceBetweenChars($word, $addString)
    {
        $result = '';
        $length = strlen($word);

        for ($i = 0; $i < $length; $i++) {
            $result .= $word[$i] . $addString;
        }

        return trim($result);
    }

    public function addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y)
    {
        $font = 'helvetica';

        $pdf->SetFont($font, '', $fontsize);
        $pdf->setCellHeightRatio(1.1);
        $pdf->writeHTMLCell($w, $h, $x, $y, str_replace("%22", '"', $text), 0, 0, false, true, '', true);
        $pdf->setCellHeightRatio(1.25);
    }

    /**
     * Scale element dimension
     *
     * @param   int $dimension
     * @return  int
     */
    public static function scale($dimension, $scale)
    {
        return round($dimension * $scale);
    }

    public static function orientation($width, $height)
    {
        if ($width > $height) {
            return "L";
        } else {
            return "P";
        }
    }
}
