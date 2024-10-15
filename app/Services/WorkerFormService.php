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
    public function generateForm101PDF($form, $outputName, $lng)
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

        // $lng = $form->lng;

        if ($lng == "heb") {
            // set some language dependent data:
            $lg = array();
            $lg['a_meta_charset'] = 'UTF-8';
            $lg['a_meta_dir'] = 'rtl';
            $lg['a_meta_language'] = 'fa';
            $lg['w_page'] = 'page';

            // set some language-dependent strings (optional)
            $pdf->setLanguageArray($lg);
        }

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
                $x = $lng == "heb" ? 313 : 224;
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
                        $x = $lng == "heb" ? 385 : 119;
                        $y = 220;
                        $fontsize = 15;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                }

                if (isset($formData['employeeFirstName'])) {
                    $text = (string)$formData['employeeFirstName'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 282 : 212;
                    $y = 220;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeLastName'])) {
                    $text = (string)$formData['employeeLastName'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 158 : 316;
                    $y = 220;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeIdNumber'])) {
                    $text = (string)$formData['employeeIdNumber'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 54 : 438;
                    $y = 220;
                    $fontsize = 15;

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
                        $x = $lng == "heb" ? 100 : 27;
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
                    $x = $lng == "heb" ? 497 : 29;
                    $y = 243;
                    $fontsize = 15;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeCity'])) {
                    $text = (string)$formData['employeeCity'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 393 : 109;
                    $y = 246;
                    $fontsize = 12;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeHouseNo'])) {
                    $text = (string)$formData['employeeHouseNo'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 376 : 202;
                    $y = 246;
                    $fontsize = 12;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeStreet'])) {
                    $text = (string)$formData['employeeStreet'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 203 : 230;
                    $y = 246;
                    $fontsize = 12;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeePassportNumber'])) {
                    $text = (string)$formData['employeePassportNumber'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 88 : 393;
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
                    $x = $lng == "heb" ? 129 : 125;
                    $y = 293;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                    if (isset($formData['employeeHealthFundname'])) {
                        $text = (string)$formData['employeeHealthFundname'];
                        $w = 150;
                        $h = 50;
                        $x = $lng == "heb" ? 524 : 29;
                        $y = 291;
                        $fontsize = 10;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                } else {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 129 : 125;
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
                        $x = $lng == "heb" ? 254 : 250;
                        $y = 282;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    } else {
                        $w = 4;
                        $h = 4;
                        $x = $lng == "heb" ? 276 : 272;
                        $y = 295;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }
                } else {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 276 : 272;
                    $y = 282;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['employeeIsraeliResident']) &&
                    $formData['employeeIsraeliResident'] === 'Yes'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 315 : 311;
                    $y = 282;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } else {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 315 : 311;
                    $y = 295;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Single'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 495 : 491;
                    $y = 281;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } elseif (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Married'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 436 : 432;
                    $y = 281;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } elseif (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Divorced'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 369 : 365;
                    $y = 281;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } elseif (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Widowed'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 495 : 491;
                    $y = 294;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } elseif (
                    isset($formData['employeeMaritalStatus']) &&
                    $formData['employeeMaritalStatus'] === 'Separated'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 449 : 444;
                    $y = 294;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['employeeSex']) &&
                    $formData['employeeSex'] === 'Male'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 534 : 530;
                    $y = 282;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                } else {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 534 : 530;
                    $y = 295;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (isset($formData['employeeMobileNo'])) {
                    $text = (string)$formData['employeeMobileNo'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 439 : 74;
                    $y = 312;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeePhoneNo'])) {
                    $text = (string)$formData['employeePhoneNo'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 279 : 240;
                    $y = 312;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['employeeEmail'])) {
                    $text = (string)$formData['employeeEmail'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 110 : 364;
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
                        $x = $lng == "heb" ? 473 : 30;
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
                    $x = $lng == "heb" ? 244 : 240;
                    $y = 360;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Salary for additional employment'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 244 : 240;
                    $y = 372;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Partial salary'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 244 : 240;
                    $y = 384;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Wage (Daily rate of pay)'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 244 : 240;
                    $y = 396;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Allowance'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 244 : 240;
                    $y = 408;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (
                    isset($formData['incomeType']) &&
                    $formData['incomeType'] === 'Scholarship'
                ) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 244 : 240;
                    $y = 420;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (isset($formData['otherIncome']['haveincome'])) {

                    if ($formData['otherIncome']['haveincome'] == "No") {
                        $w = 4;
                        $h = 4;
                        $x = $lng == "heb" ? 242 : 238;
                        $y = 457;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    } else {
                        $w = 4;
                        $h = 4;
                        $x = $lng == "heb" ? 242 : 238;
                        $y = 481;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (in_array('Monthly salary', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = $lng == "heb" ? 242 : 238;
                            $y = 493;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Salary for additional employment', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = $lng == "heb" ? 242 : 238;
                            $y = 504;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Salary for additional employment', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = $lng == "heb" ? 242 : 238;
                            $y = 515;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Wage (Daily rate of pay)', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = $lng == "heb" ? 124 : 120;
                            $y = 493;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Allowance', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = $lng == "heb" ? 124 : 120;
                            $y = 504;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (in_array('Scholarship', $formData['otherIncome']['incomeType'])) {
                            $w = 4;
                            $h = 4;
                            $x = $lng == "heb" ? 124 : 120;
                            $y = 515;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }
                    }
                }

                if (isset($formData['otherIncome']['taxCreditsAtOtherIncome'])) {

                    if ($formData['otherIncome']['taxCreditsAtOtherIncome'] == "request") {
                        $w = 4;
                        $h = 4;
                        $x = $lng == "heb" ? 241 : 237;
                        $y = 539;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    } elseif ($formData['otherIncome']['taxCreditsAtOtherIncome'] == "receive") {
                        $w = 4;
                        $h = 4;
                        $x = $lng == "heb" ? 241 : 237;
                        $y = 562;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }
                }

                if (isset($formData['otherIncome']['studyFund'])) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 241 : 237;
                    $y = 586;

                    $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                }

                if (isset($formData['otherIncome']['pensionInsurance'])) {
                    $w = 4;
                    $h = 4;
                    $x = $lng == "heb" ? 241 : 237;
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
                            $x = $lng == "heb" ? 248 : 256;
                            $y = 386 + $ypos;
                            $fontsize = 15;

                            $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                        }

                        if (isset($value['IdNumber'])) {
                            $text = (string)$value['IdNumber'];
                            $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                            $w = 198;
                            $h = 98;
                            $x = $lng == "heb" ? 147 : 348;
                            $y = 386 + $ypos;
                            $fontsize = 13;

                            $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                        }

                        if (isset($value['firstName'])) {
                            $text = $value['firstName'];
                            $w = 198;
                            $h = 98;
                            $x = $lng == "heb" ? 77 : 450;
                            $y = 386 + $ypos;
                            $fontsize = 13;

                            $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                        }

                        if (isset($value['inCustody']) && $value['inCustody'] == true) {
                            $w = 5;
                            $h = 5;
                            $x = $lng == "heb" ? 538 : 533;
                            $y = 390 + $ypos;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (isset($value['haveChildAllowance']) && $value['haveChildAllowance'] == true) {
                            $w = 5;
                            $h = 5;
                            $x = $lng == "heb" ? 528 : 523;
                            $y = 390 + $ypos;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
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
                        $x = $lng == "heb" ? 475 : 28;
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
                    $x = $lng == "heb" ? 384 : 119;
                    $y = 694;
                    $fontsize = 15;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['Spouse']['firstName'])) {
                    $text = (string)$formData['Spouse']['firstName'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 282 : 210;
                    $y = 694;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['Spouse']['lastName'])) {
                    $text = (string)$formData['Spouse']['lastName'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 158 : 315;
                    $y = 694;
                    $fontsize = 14;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['Spouse']['IdNumber'])) {
                    $text = (string)$formData['Spouse']['IdNumber'];
                    $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 241 : 439;
                    $y = 694;
                    $fontsize = 13;

                    $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                }

                if (isset($formData['Spouse']['hasIncome'])) {
                    if ($formData['Spouse']['hasIncome'] == "No") {
                        $w = 4;
                        $h = 4;
                        $x = $lng == "heb" ? 416 : 412;
                        $y = 719;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    } else {
                        $w = 4;
                        $h = 4;
                        $x = $lng == "heb" ? 288 : 284;
                        $y = 719;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['Spouse']['incomeTypeOpt1'])) {
                            $w = 4;
                            $h = 4;
                            $x = $lng == "heb" ? 166 : 162;
                            $y = 720;

                            $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                        }

                        if (isset($formData['Spouse']['incomeTypeOpt2'])) {
                            $w = 4;
                            $h = 4;
                            $x = $lng == "heb" ? 86 : 82;
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
                        $x = $lng == "heb" ? 55 : 422;
                        $y = 718;
                        $fontsize = 8;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                        $text = (string)$formData['Spouse']['passportNumber'];
                        $text = $this->addWhiteSpaceBetweenChars($text, ' ');
                        $w = 198;
                        $h = 98;
                        $x = $lng == "heb" ? 55 : 420;
                        $y = 724;
                        $fontsize = 12;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                }
                // Details of Spouse End

            } else if ($page == 2) {
                if (isset($formData['TaxExemption'])) {
                    if (
                        isset($formData['employeeIsraeliResident']) &&
                        $formData['employeeIsraeliResident'] === 'Yes'
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 55;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['disabled']) &&
                        $formData['TaxExemption']['disabled'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 70;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['disabledCompensation']) &&
                        $formData['TaxExemption']['disabledCompensation'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 93;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm3']) &&
                        $formData['TaxExemption']['exm3'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 111;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['TaxExemption']['exm3Date'])) {
                            $isDate = true;

                            try {
                                Carbon::parse($formData['TaxExemption']['exm3Date']);
                            } catch (\Throwable $th) {
                                //throw $th;
                                $isDate = false;
                            }

                            if ($isDate) {
                                $text = (string)Carbon::parse($formData['TaxExemption']['exm3Date'])->format('d/m/Y');
                                $w = 198;
                                $h = 98;
                                $x = $lng == "heb" ? 272 : 260;
                                $y = 105;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }

                        if (isset($formData['TaxExemption']['exm3Locality'])) {
                            $text = (string)$formData['TaxExemption']['exm3Locality'];
                            $w = 198;
                            $h = 98;
                            $x = $lng == "heb" ? 157 : 298;
                            $y = 118;
                            $fontsize = 12;

                            $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                        }
                    }

                    if (
                        isset($formData['TaxExemption']['exm4']) &&
                        $formData['TaxExemption']['exm4'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 518 : 514;
                        $y = 138;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['TaxExemption']['exm4FromDate'])) {
                            $isDate = true;

                            try {
                                Carbon::parse($formData['TaxExemption']['exm4FromDate']);
                            } catch (\Throwable $th) {
                                //throw $th;
                                $isDate = false;
                            }

                            if ($isDate) {
                                $text = (string)Carbon::parse($formData['TaxExemption']['exm4FromDate'])->format('d/m/Y');
                                $w = 198;
                                $h = 98;
                                $x = $lng == "heb" ? 196 : 334;
                                $y = 132;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }

                        if (isset($formData['TaxExemption']['exm4NoIncomeDate'])) {
                            $isDate = true;

                            try {
                                Carbon::parse($formData['TaxExemption']['exm4NoIncomeDate']);
                            } catch (\Throwable $th) {
                                //throw $th;
                                $isDate = false;
                            }

                            if ($isDate) {
                                $text = (string)Carbon::parse($formData['TaxExemption']['exm4NoIncomeDate'])->format('d/m/Y');
                                $w = 198;
                                $h = 98;
                                $x = $lng == "heb" ? 344 : 190;
                                $y = 146;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }
                    }

                    if (
                        isset($formData['TaxExemption']['exm5']) &&
                        $formData['TaxExemption']['exm5'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 188;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm6']) &&
                        $formData['TaxExemption']['exm6'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 211;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm7']) &&
                        $formData['TaxExemption']['exm7'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 234;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['TaxExemption']['exm7NoOfChild'])) {
                            $text = (int)$formData['TaxExemption']['exm7NoOfChild'];

                            if ($text > 0) {
                                $w = 2;
                                $h = 2;
                                $x = $lng == "heb" ? 503 : 501;
                                $y = 256;

                                $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                                $w = 50;
                                $h = 100;
                                $x = $lng == "heb" ? 210 : 370;
                                $y = 250;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }

                        if (isset($formData['TaxExemption']['exm7NoOfChild1to5'])) {
                            $text = (int)$formData['TaxExemption']['exm7NoOfChild1to5'];

                            if ($text > 0) {
                                $w = 2;
                                $h = 2;
                                $x = $lng == "heb" ? 503 : 501;
                                $y = 267;

                                $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                                $w = 50;
                                $h = 100;
                                $x = $lng == "heb" ? 297 : 285;
                                $y = 260;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }

                        if (isset($formData['TaxExemption']['exm7NoOfChild6to17'])) {
                            $text = (int)$formData['TaxExemption']['exm7NoOfChild6to17'];

                            if ($text > 0) {
                                $w = 2;
                                $h = 2;
                                $x = $lng == "heb" ? 261 : 259;
                                $y = 256;

                                $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                                $w = 50;
                                $h = 100;
                                $x = $lng == "heb" ? 540 : 45;
                                $y = 250;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }

                        if (isset($formData['TaxExemption']['exm7NoOfChild18'])) {
                            $text = (int)$formData['TaxExemption']['exm7NoOfChild18'];

                            if ($text > 0) {
                                $w = 2;
                                $h = 2;
                                $x = $lng == "heb" ? 261 : 259;
                                $y = 267;

                                $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                                $w = 50;
                                $h = 100;
                                $x = $lng == "heb" ? 500 : 80;
                                $y = 260;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }
                    }

                    if (
                        isset($formData['TaxExemption']['exm8']) &&
                        $formData['TaxExemption']['exm8'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 281;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['TaxExemption']['exm8NoOfChild'])) {
                            $text = (int)$formData['TaxExemption']['exm8NoOfChild'];

                            if ($text > 0) {
                                $w = 2;
                                $h = 2;
                                $x = $lng == "heb" ? 504 : 502;
                                $y = 294;

                                $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                                $w = 50;
                                $h = 100;
                                $x = $lng == "heb" ? 207 : 370;
                                $y = 288;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }

                        if (isset($formData['TaxExemption']['exm8NoOfChild1to5'])) {
                            $text = (int)$formData['TaxExemption']['exm8NoOfChild1to5'];

                            if ($text > 0) {
                                $w = 2;
                                $h = 2;
                                $x = $lng == "heb" ? 505 : 503;
                                $y = 305;

                                $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                                $w = 50;
                                $h = 100;
                                $x = $lng == "heb" ? 295 : 285;
                                $y = 299;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }

                        if (isset($formData['TaxExemption']['exm8NoOfChild6to17'])) {
                            $text = (int)$formData['TaxExemption']['exm8NoOfChild6to17'];

                            if ($text > 0) {
                                $w = 2;
                                $h = 2;
                                $x = $lng == "heb" ? 261 : 259;
                                $y = 294;

                                $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                                $w = 50;
                                $h = 100;
                                $x = $lng == "heb" ? 535 : 45;
                                $y = 288;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }
                    }

                    if (
                        isset($formData['TaxExemption']['exm9']) &&
                        $formData['TaxExemption']['exm9'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 320;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm10']) &&
                        $formData['TaxExemption']['exm10'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 338;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm11']) &&
                        $formData['TaxExemption']['exm11'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 364;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);

                        if (isset($formData['TaxExemption']['exm11NoOfChildWithDisibility'])) {
                            $text = (string)$formData['TaxExemption']['exm11NoOfChildWithDisibility'];
                            $w = 50;
                            $h = 100;
                            $x = $lng == "heb" ? 132 : 449;
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
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 388;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm13']) &&
                        $formData['TaxExemption']['exm13'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 404;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxExemption']['exm14']) &&
                        $formData['TaxExemption']['exm14'] === true
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 517 : 514;
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
                                $x = $lng == "heb" ? 340 : 192;
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
                                $x = $lng == "heb" ? 472 : 58;
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
                        $x = $lng == "heb" ? 517 : 514;
                        $y = 444;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }
                }

                if (
                    isset($formData['TaxCoordination']['hasTaxCoordination']) &&
                    $formData['TaxCoordination']['hasTaxCoordination'] == true
                ) {
                    if (
                        isset($formData['TaxCoordination']['requestReason']) &&
                        $formData['TaxCoordination']['requestReason'] === 'reason1'
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 518 : 515;
                        $y = 481;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxCoordination']['requestReason']) &&
                        $formData['TaxCoordination']['requestReason'] === 'reason2'
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 518 : 515;
                        $y = 509;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxCoordination']['requestReason']) &&
                        $formData['TaxCoordination']['requestReason'] === 'reason3'
                    ) {
                        $w = 3;
                        $h = 3;
                        $x = $lng == "heb" ? 518 : 515;
                        $y = 594;

                        $pdf->Image(public_path('images/icons/cross.png'), $x, $y, $w, $h, '', '', '', true);
                    }

                    if (
                        isset($formData['TaxCoordination']['employer']) &&
                        is_array($formData['TaxCoordination']['employer']) &&
                        !empty($formData['TaxCoordination']['employer'])
                    ) {
                        foreach ($formData['TaxCoordination']['employer'] as $key => $employer_) {
                            $ypos = 16 * $key;

                            if (isset($employer_['Tax'])) {
                                $text = (string)$employer_['Tax'];
                                $w = 50;
                                $h = 50;
                                $x = $lng == "heb" ? 542 : 40;
                                $y = 545 + $ypos;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }

                            if (isset($employer_['MonthlyIncome'])) {
                                $text = (string)$employer_['MonthlyIncome'];
                                $w = 50;
                                $h = 50;
                                $x = $lng == "heb" ? 453 : 115;
                                $y = 545 + $ypos;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }

                            if (isset($employer_['incomeType'])) {
                                $text = (string)$employer_['incomeType'];
                                $w = 150;
                                $h = 50;
                                $x = $lng == "heb" ? 365 : 172;
                                $y = 545 + $ypos;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }

                            if (isset($employer_['fileNumber'])) {
                                $text = (string)$employer_['fileNumber'];
                                $w = 150;
                                $h = 50;
                                $x = $lng == "heb" ? 302 : 240;
                                $y = 544 + $ypos;
                                $fontsize = 11;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }

                            if (isset($employer_['address'])) {
                                $text = (string)$employer_['address'];
                                $w = 300;
                                $h = 50;
                                $x = $lng == "heb" ? 152 : 315;
                                $y = 545 + $ypos;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }

                            if (isset($employer_['firstName'])) {
                                $text = (string)$employer_['firstName'];
                                $w = 250;
                                $h = 50;
                                $x = $lng == "heb" ? 58 : 447;
                                $y = 545 + $ypos;
                                $fontsize = 12;

                                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                            }
                        }
                    }
                }

                if (isset($formData['employeeIdNumber'])) {
                    $text = (string)$formData['employeeIdNumber'];
                    $w = 198;
                    $h = 98;
                    $x = $lng == "heb" ? 430 : 95;
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
                        $x = $lng == "heb" ? 376 : 148;
                        $y = 641;
                        $fontsize = 14;

                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);
                    }
                }

                if (isset($formData['signature'])) {
                    $img = '<img src="' . $formData['signature'] . '" width="150" height="50">';
                    if ($lng == "heb") {
                        $pdf->writeHTMLCell(120, 30, 450, 620, $img, 0, 1);
                    } else {
                        $pdf->writeHTMLCell(120, 30, 26, 620, $img, 0, 1);
                    }
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
                $x = $lng == "heb" ? 560 : 30;
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
                $x = $lng == "heb" ? 560 : 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['employeeResidencePermit']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['employeeIdentityType']) &&
            $formData['employeeIdentityType'] == 'IDNumber'
        ) {
            \Log::info($formData['employeeIdCardCopy']);
            //create a page
            $pdf->AddPage();

            $text = 'B. Employee details | Photocopy of ID Card';
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
            $x = $lng == "heb" ? 560 : 30;
            $y = 60;

            $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['employeeIdCardCopy']), $x, $y, $w, $h, '', '', '', true);
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
                $x = $lng == "heb" ? 560 : 30;
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
                        $x = $lng == "heb" ? 560 : 30;
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
                $x = $lng == "heb" ? 560 : 30;
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
                $x = $lng == "heb" ? 560 : 30;
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
                $x = $lng == "heb" ? 560 : 30;
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
                $x = $lng == "heb" ? 560 : 30;
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
                $x = $lng == "heb" ? 560 : 30;
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
                $x = $lng == "heb" ? 560 : 30;
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
                $x = $lng == "heb" ? 560 : 30;
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
                $x = $lng == "heb" ? 560 : 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxExemption']['exm15Certificate']), $x, $y, $w, $h, '', '', '', true);
            }
        }

        if (
            isset($formData['TaxCoordination']['hasTaxCoordination']) &&
            $formData['TaxCoordination']['hasTaxCoordination'] === true
        ) {
            if (
                isset($formData['TaxCoordination']['requestReason1Certificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxCoordination']['requestReason1Certificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = 'Attachment of tax coordination | Proofs for lack of previous incomes';
                $w = 540;
                $h = 100;
                $x = 90;
                $y = 20;
                $fontsize = 14;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = $lng == "heb" ? 560 : 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxCoordination']['requestReason1Certificate']), $x, $y, $w, $h, '', '', '', true);
            }

            if (
                isset($formData['TaxCoordination']['employer']) &&
                is_array($formData['TaxCoordination']['employer']) &&
                !empty($formData['TaxCoordination']['employer'])
            ) {
                foreach ($formData['TaxCoordination']['employer'] as $key => $employer_) {
                    if (
                        isset($employer_['payslip']) &&
                        Storage::disk('public')->exists('uploads/form101/documents/' . $employer_['payslip'])
                    ) {
                        //create a page
                        $pdf->AddPage();

                        $text = 'Attachment of tax coordination | Salary Payslip ' . ($key + 1) . '.';
                        $w = 540;
                        $h = 100;
                        $x = 150;
                        $y = 20;
                        $fontsize = 14;

                        $pdf->SetTextColor(0, 0, 0);
                        $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                        $pdf->SetTextColor(0, 7, 224);
                        $w = 530;
                        $h = 300;
                        $x = $lng == "heb" ? 560 : 30;
                        $y = 50;

                        $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $employer_['payslip']), $x, $y, $w, $h, '', '', '', true);
                    }
                }
            }

            if (
                isset($formData['TaxCoordination']['requestReason3Certificate']) &&
                Storage::disk('public')->exists('uploads/form101/documents/' . $formData['TaxCoordination']['requestReason3Certificate'])
            ) {
                //create a page
                $pdf->AddPage();

                $text = 'Attachment of tax coordination | Tax coordination certificate from the assessing officer';
                $w = 540;
                $h = 100;
                $x = 50;
                $y = 20;
                $fontsize = 14;

                $pdf->SetTextColor(0, 0, 0);
                $this->addTextContent($pdf, $text, $fontsize, $w, $h, $x, $y);

                $pdf->SetTextColor(0, 7, 224);
                $w = 530;
                $h = 300;
                $x = $lng == "heb" ? 560 : 30;
                $y = 50;

                $pdf->Image(Storage::disk('public')->path('uploads/form101/documents/' . $formData['TaxCoordination']['requestReason3Certificate']), $x, $y, $w, $h, '', '', '', true);
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
