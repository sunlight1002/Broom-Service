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

        $lng = $form->lng;

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
                $x = 224;
                $y = 83;
                $fontsize = 15;

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
                        $x = 119;
                        $y = 220;
                        $fontsize = 15;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                }

                if (isset($formData['employeeFirstName'])) {
                    $text = (string)$formData['employeeFirstName'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 277 : 212;
                    $y = 220;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeLastName'])) {
                    $text = (string)$formData['employeeLastName'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 374 : 316;
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
                    $x = 29;
                    $y = 243;
                    $fontsize = 15;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeCity'])) {
                    $text = (string)$formData['employeeCity'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 156 : 109;
                    $y = 246;
                    $fontsize = 12;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeHouseNo'])) {
                    $text = (string)$formData['employeeHouseNo'];
                    $w = 198;
                    $h = 98;
                    $x = 202;
                    $y = 246;
                    $fontsize = 12;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeStreet'])) {
                    $text = (string)$formData['employeeStreet'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 360 : 233;
                    $y = 246;
                    $fontsize = 12;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeePassportNumber'])) {
                    $text = (string)$formData['employeePassportNumber'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 393;
                    $y = 250;
                    $fontsize = 15;

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
                        $w = 150;
                        $h = 50;
                        $x = $lng == "heb" ? 40 : 29;
                        $y = 291;
                        $fontsize = 10;

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

                if (isset($formData['employeeMobileNo'])) {
                    $text = (string)$formData['employeeMobileNo'];
                    $w = 198;
                    $h = 98;
                    $x = 74;
                    $y = 312;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeePhoneNo'])) {
                    $text = (string)$formData['employeePhoneNo'];
                    $w = 198;
                    $h = 98;
                    $x = 240;
                    $y = 312;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeEmail'])) {
                    $text = (string)$formData['employeeEmail'];
                    $w = 198;
                    $h = 98;
                    $x = 364;
                    $y = 312;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
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
                        $x = 30;
                        $y = 376;
                        $fontsize = 15;

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
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Allowance'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 240;
                    $y = 408;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Scholarship'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = 240;
                    $y = 420;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (isset($formData['otherIncome']['haveincome'])) {

                    if ($formData['otherIncome']['haveincome'] == "No") {
                        $w = 4;
                        $h = 4;
                        $x = 238;
                        $y = 457;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    } else {
                        $w = 4;
                        $h = 4;
                        $x = 238;
                        $y = 481;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (in_array('Monthly salary', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = 238;
                            $y = 493;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Salary for additional employment', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = 238;
                            $y = 504;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Salary for additional employment', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = 238;
                            $y = 515;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Wage (Daily rate of pay)', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = 120;
                            $y = 493;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Allowance', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = 120;
                            $y = 504;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Scholarship', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = 120;
                            $y = 515;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }
                    }
                }

                if (isset($formData['otherIncome']['taxCreditsAtOtherIncome'])) {

                    if ($formData['otherIncome']['taxCreditsAtOtherIncome'] == "request") {
                        $w = 4;
                        $h = 4;
                        $x = 237;
                        $y = 539;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    } elseif ($formData['otherIncome']['taxCreditsAtOtherIncome'] == "receive") {
                        $w = 4;
                        $h = 4;
                        $x = 237;
                        $y = 562;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }
                }

                if (isset($formData['otherIncome']['studyFund'])) {
                    $w = 4;
                    $h = 4;
                    $x = 237;
                    $y = 586;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (isset($formData['otherIncome']['pensionInsurance'])) {
                    $w = 4;
                    $h = 4;
                    $x = 237;
                    $y = 621;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (isset($formData['children'])) {
                    foreach ($formData['children'] as $key => $value) {
                        $ypos = 22 * $key;
                        if (isset($value['Dob'])) {
                            $text = (string)Carbon::parse($value['Dob'])->format('dmY');
                            $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                            $w = 198;
                            $h = 98;
                            $x = 256;
                            $y = 386 + $ypos;
                            $fontsize = 15;

                            $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                        }

                        if (isset($value['IdNumber'])) {
                            $text = (string)$value['IdNumber'];
                            $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                            $w = 198;
                            $h = 98;
                            $x = 348;
                            $y = 386 + $ypos;
                            $fontsize = 13;

                            $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                        }

                        if (isset($value['firstName'])) {
                            $text = $value['firstName'];
                            $w = 198;
                            $h = 98;
                            $x = $lng == "heb" ? 477 : 450;
                            $y = 386 + $ypos;
                            $fontsize = 13;

                            $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                        }
                    }
                }

                // Details of Spouse Start
                if (isset($formData['Spouse']['DateOFAliyah'])) {
                    try {
                        $text = (string)Carbon::parse($formData['Spouse']['DateOFAliyah'])->format('dmY');
                        $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                        $w = 198;
                        $h = 98;
                        $x = 28;
                        $y = 694;
                        $fontsize = 15;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }

                if (isset($formData['Spouse']['Dob'])) {
                    $text = (string)Carbon::parse($formData['Spouse']['Dob'])->format('dmY');
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 119;
                    $y = 694;
                    $fontsize = 15;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['Spouse']['firstName'])) {
                    $text = (string)$formData['Spouse']['firstName'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 278 : 210;
                    $y = 694;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['Spouse']['lastName'])) {
                    $text = (string)$formData['Spouse']['lastName'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 384 : 315;
                    $y = 694;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['Spouse']['IdNumber'])) {
                    $text = (string)$formData['Spouse']['IdNumber'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = 439;
                    $y = 694;
                    $fontsize = 13;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['Spouse']['hasIncome'])) {
                    if ($formData['Spouse']['hasIncome'] == "No") {
                        $w = 4;
                        $h = 4;
                        $x = 412;
                        $y = 719;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    } else {
                        $w = 4;
                        $h = 4;
                        $x = 284;
                        $y = 719;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['Spouse']['incomeTypeOpt1'])) {
                            $w = 4;
                            $h = 4;
                            $x = 162;
                            $y = 720;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (isset($formData['Spouse']['incomeTypeOpt2'])) {
                            $w = 4;
                            $h = 4;
                            $x = 82;
                            $y = 720;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }
                    }
                }

                if (isset($formData['Spouse']['Identity'])) {
                    if ($formData['Spouse']['Identity'] == "Passport") {
                        $text = (string)$formData['Spouse']['Country'];
                        $w = 198;
                        $h = 98;
                        $x = $lng == "heb" ? 505 : 422;
                        $y = 718;
                        $fontsize = 8;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                        $text = (string)$formData['Spouse']['passportNumber'];
                        $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                        $w = 198;
                        $h = 98;
                        $x = 420;
                        $y = 724;
                        $fontsize = 12;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                }
                // Details of Spouse End

            } else if ($page == 2) {
                if (isset($formData['TaxExemption'])) {
                    if (
                        isset($formData['TaxExemption']['isIsraelResident']) &&
                        $formData['TaxExemption']['isIsraelResident'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 55;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['disabled']) &&
                        $formData['TaxExemption']['disabled'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 70;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['disabledCompensation']) &&
                        $formData['TaxExemption']['disabledCompensation'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 93;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm3']) &&
                        $formData['TaxExemption']['exm3'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 111;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm4']) &&
                        $formData['TaxExemption']['exm4'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 138;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm5']) &&
                        $formData['TaxExemption']['exm5'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 188;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm6']) &&
                        $formData['TaxExemption']['exm6'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 211;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm7']) &&
                        $formData['TaxExemption']['exm7'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 234;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm8']) &&
                        $formData['TaxExemption']['exm8'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 281;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['TaxExemption']['exm11NoOfChildWithDisibility'])) {
                            $text = (string)$formData['TaxExemption']['exm11NoOfChildWithDisibility'];
                            $w = 50;
                            $h = 50;
                            $x = 449;
                            $y = 281;
                            $fontsize = 12;

                            $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                        }
                    }

                    if (
                        isset($formData['TaxExemption']['exm9']) &&
                        $formData['TaxExemption']['exm9'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 320;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm10']) &&
                        $formData['TaxExemption']['exm10'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 338;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm11']) &&
                        $formData['TaxExemption']['exm11'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 364;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['TaxExemption']['exm11NoOfChildWithDisibility'])) {
                            $text = (string)$formData['TaxExemption']['exm11NoOfChildWithDisibility'];
                            $w = 50;
                            $h = 100;
                            $x = 449;
                            $y = 355;
                            $fontsize = 12;

                            $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                        }
                    }

                    if (
                        isset($formData['TaxExemption']['exm12']) &&
                        $formData['TaxExemption']['exm12'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 388;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm13']) &&
                        $formData['TaxExemption']['exm13'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 404;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm14']) &&
                        $formData['TaxExemption']['exm14'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 420;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['TaxExemption']['exm14BeginingDate'])) {
                            $isDate = true;

                            try {
                                Carbon::parse($formData['TaxExemption']['exm14BeginingDate']);
                            } catch (\Throwable $th) {
                                //throw $th;
                                $isDate = false;
                            }

                            if ($isDate) {
                                $text = (string)Carbon::parse($formData['TaxExemption']['exm14BeginingDate'])->format('d/m/Y');
                                $w = 198;
                                $h = 98;
                                $x = 192;
                                $y = 413;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }

                        if (isset($formData['TaxExemption']['exm14EndDate'])) {
                            $isDate = true;

                            try {
                                Carbon::parse($formData['TaxExemption']['exm14EndDate']);
                            } catch (\Throwable $th) {
                                //throw $th;
                                $isDate = false;
                            }

                            if ($isDate) {
                                $text = (string)Carbon::parse($formData['TaxExemption']['exm14EndDate'])->format('d/m/Y');
                                $w = 198;
                                $h = 50;
                                $x = 58;
                                $y = 413;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }
                    }

                    if (
                        isset($formData['TaxExemption']['exm15']) &&
                        $formData['TaxExemption']['exm15'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = 514;
                        $y = 444;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }
                }

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

        if (
            isset($formData['employeeIdentityType']) &&
            $formData['employeeIdentityType'] == 'Passport'
        ) {
            if (Storage::disk('public')->exists('uploads/form101/documents/' . $formData['employeepassportCopy'])) {
                //create a page
                $pdf->AddPage();

                $text = 'B. Employee details | Photocopy of passport';
                $w = 500;
                $h = 100;
                $x = 85;
                $y = 20;
                $fontsize = 20;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 60;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['employeepassportCopy']), $x, $y, $w, $h, '', '', '', true);
            }

            if (Storage::disk('public')->exists('uploads/form101/documents/' . $formData['employeeResidencePermit'])) {
                //create a page
                $pdf->AddPage();

                $text = 'B. Employee details | Photocopy of residence permit in Israel for a foreign employee';
                $w = 540;
                $h = 100;
                $x = 30;
                $y = 20;
                $fontsize = 14;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['employeeResidencePermit']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['TaxExemption']['disabled']) &&
            $formData['TaxExemption']['disabled'] === true
        ) {
            if (
                isset($formData['TaxExemption']['disabledCertificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['disabledCertificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = "H. Tax Exemption | 2. Certificate from the Ministry of Defence/the Treasury/assessing officer/Certification of Blindness issued after 1/1/94.";
                $w = 540;
                $h = 100;
                $x = 40;
                $y = 20;
                $fontsize = 9;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['disabledCertificate']), $x, $y, $w, $h, '', '', '', true);

                if (
                    isset($formData['TaxExemption']['disabledCompensation']) &&
                    $formData['TaxExemption']['disabledCompensation'] === true
                ) {
                    if (
                        isset($formData['TaxExemption']['disabledCompensationCertificate']) &&
                        Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['disabledCompensationCertificate'])
                    ) {
                        //create a page
                        $pdf->AddPage();

                        $text = "H. Tax Exemption | 2. Certificate for receiving the monthly compensation";
                        $w = 540;
                        $h = 100;
                        $x = 80;
                        $y = 20;
                        $fontsize = 14;

                        $pdf->SetTextColor(0, 0, 0);
                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                        $pdf->SetTextColor(0, 7, 224);
                        $w = 530;
                        $h = 300;
                        $x = 30;
                        $y = 50;

                        $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['disabledCompensationCertificate']), $x, $y, $w, $h, '', '', '', true);
                    }
                }
            }
        }

        if (
            isset($formData['TaxExemption']['exm3']) &&
            $formData['TaxExemption']['exm3'] === true
        ) {
            if (
                isset($formData['TaxExemption']['exm3Certificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['exm3Certificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = "H. Tax Exemption | 3. Locality Certificate from the locality on Form 1312-Aleph";
                $w = 540;
                $h = 100;
                $x = 60;
                $y = 20;
                $fontsize = 14;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['exm3Certificate']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['TaxExemption']['exm4']) &&
            $formData['TaxExemption']['exm4'] === true
        ) {
            if (
                isset($formData['TaxExemption']['exm4ImmigrationCertificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['exm4ImmigrationCertificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = "H. Tax Exemption | 4. New immigrant certificate";
                $w = 540;
                $h = 100;
                $x = 100;
                $y = 20;
                $fontsize = 20;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['exm4ImmigrationCertificate']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['TaxExemption']['exm5']) &&
            $formData['TaxExemption']['exm5'] === true
        ) {
            if (
                isset($formData['TaxExemption']['exm5disabledCirtificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['exm5disabledCirtificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = "H. Tax Exemption | 5. Disabled or blind certificate for the employee or the spouse.";
                $w = 540;
                $h = 100;
                $x = 65;
                $y = 20;
                $fontsize = 14;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['exm5disabledCirtificate']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['TaxExemption']['exm10']) &&
            $formData['TaxExemption']['exm10'] === true
        ) {
            if (
                isset($formData['TaxExemption']['exm10Certificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['exm10Certificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = "H. Tax Exemption | 10. Photocopy of a court order for child support";
                $w = 540;
                $h = 100;
                $x = 70;
                $y = 20;
                $fontsize = 16;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['exm10Certificate']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['TaxExemption']['exm11']) &&
            $formData['TaxExemption']['exm11'] === true
        ) {
            if (
                isset($formData['TaxExemption']['exm11Certificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['exm11Certificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = "H. Tax Exemption | 11. Children's disability benefit certificate from the National Insurance Institute for the current tax year";
                $w = 540;
                $h = 100;
                $x = 40;
                $y = 20;
                $fontsize = 10;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['exm11Certificate']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['TaxExemption']['exm12']) &&
            $formData['TaxExemption']['exm12'] === true
        ) {
            if (
                isset($formData['TaxExemption']['exm12Certificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['exm12Certificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = 'H. Tax Exemption | 12. Photocopy of a court order for alimony';
                $w = 540;
                $h = 100;
                $x = 75;
                $y = 20;
                $fontsize = 18;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['exm12Certificate']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['TaxExemption']['exm14']) &&
            $formData['TaxExemption']['exm14'] === true
        ) {
            if (
                isset($formData['TaxExemption']['exm14Certificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['exm14Certificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = 'H. Tax Exemption | 14. Discharge / end of service certificate';
                $w = 540;
                $h = 100;
                $x = 75;
                $y = 20;
                $fontsize = 18;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['exm14Certificate']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['TaxExemption']['exm15']) &&
            $formData['TaxExemption']['exm15'] === true
        ) {
            if (
                isset($formData['TaxExemption']['exm15Certificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxExemption']['exm15Certificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = 'H. Tax Exemption | 15. Declaration in Form 119';
                $w = 540;
                $h = 100;
                $x = 90;
                $y = 20;
                $fontsize = 20;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['exm15Certificate']), $x, $y, $w, $h, '', '', '', true);
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
        // $font = 'helvetica';
        $font = 'freeserif';

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
