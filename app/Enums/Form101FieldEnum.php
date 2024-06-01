<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\Carbon;

final class Form101FieldEnum extends AbstractEnum
{
    private $currentDate;
    private $defaultFields;

    public function __construct()
    {
        $this->currentDate = Carbon::now()->toDateTimeString();
        $this->defaultFields = [
            "employerName" => null,
            "employerAddress" => null,
            "employerPhone" => null,
            "employerDeductionsFileNo" => null,
            "employeeFirstName" => null,
            "employeeLastName" => null,
            "employeeIdentityType" => "IDNumber",
            "employeeIdNumber" => null,
            "employeecountry" => null,
            "employeePassportNumber" => null,
            "employeeDob" => null,
            "employeeDateOfAliyah" => null,
            "employeeCity" => null,
            "employeeStreet" => null,
            "employeeHouseNo" => null,
            "employeePostalCode" => null,
            "employeeMobileNo" => null,
            "employeePhoneNo" => null,
            "employeeEmail" => null,
            "employeeSex" => "Male",
            "employeeMaritalStatus" => null,
            "employeeIsraeliResident" => null,
            "employeeCollectiveMoshavMember" => null,
            "employeeHealthFundMember" => null,
            "employeeHealthFundname" => null,
            "employeemyIncomeToKibbutz" => null,
            "incomeType" => null,
            "DateOfBeginningWork" => null,
            "otherIncome" => [
                "haveincome" => null,
                "taxCreditsAtOtherIncome" => null,
                "allowance" => false,
                "scholarship" => false,
            ],
            "Spouse" => [
                "firstName" => null,
                "lastName" => null,
                "Identity" => null,
                "Country" => null,
                "passportNumber" => null,
                "IdNumber" => null,
                "Dob" => null,
                "DateOFAliyah" => null,
                "hasIncome" => null,
                "incomeType" => null,
                "incomeTypeOpt1" => false,
                "incomeTypeOpt2" => false
            ],
            "TaxExemption" => [
                "isIsraelResident" => null,
                "disabled" => false,
                "disabledCertificate" => null,
                "disabledCompensation" => false,
                "disabledCompensationCertificate" => null,
                "exm3" => false,
                "exm3Date" => null,
                "exm3Locality" => null,
                "exm3Certificate" => null,
                "exm4" => false,
                "exm4FromDate" => null,
                "exm4ImmigrationCertificate" => null,
                "exm4NoIncomeDate" => null,
                "exm5" => false,
                "exm5disabledCirtificate" => null,
                "exm6" => false,
                "exm7" => false,
                "exm7NoOfChild" => "0",
                "exm7NoOfChild1to5" => "0",
                "exm7NoOfChild6to17" => "0",
                "exm7NoOfChild18" => "0",
                "exm8" => false,
                "exm8NoOfChild" => "0",
                "exm8NoOfChild1to5" => "0",
                "exm8NoOfChild6to17" => "0",
                "exm9" => false,
                "exm10" => false,
                "exm10Certificate" => null,
                "exm11" => false,
                "exm11NoOfChildWithDisibility" => "0",
                "exm11Certificate" => null,
                "exm12" => false,
                "exm12Certificate" => null,
                "exm13" => false,
                "exm14" => false,
                "exm14BeginingDate" => null,
                "exm14EndDate" => null,
                "exm14Certificate" => null,
                "exm15" => false,
                "exm15Certificate" => null,
            ],
            "TaxCoordination" => [
                "hasTaxCoordination" => false,
                "requestReason" => null,
                "employer" => [
                    0 =>  [
                        "firstName" => null,
                        "address" => null,
                        "fileNumber" => null,
                        "MonthlyIncome" => null,
                        "Tax" => null,
                        "incomeType" => null,
                    ]
                ],
            ],
            "date" => $this->currentDate,
            "sender" =>  [
                "employeeEmail" => null,
                "employerEmail" => "office@broomservice.co.il"
            ],
            "signature" => null,
            "savingType" => "draft"
        ];
    }

    public function getDefaultFields(): array
    {
        return $this->defaultFields;
    }
}
