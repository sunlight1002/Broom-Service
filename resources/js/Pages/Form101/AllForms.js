import { useFormik } from "formik";
import React, { useEffect, useRef, useState } from "react";
import { Base64 } from "js-base64";
import Swal from "sweetalert2";
import * as yup from "yup";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import i18next from "i18next";

import EmployerDetails from "./EmployerDetails";
import EmployeeDetails from "./EmployeeDetails";
import ChildrenDetails from "./ChildrenDetails";
import IncomeDetails from "./IncomeDetails";
import OtherIncome from "./OtherIncome";
import SpouseDetail from "./SpouseDetail";
import TaxExemption from "./TaxExemption";
import TaxCoordination, { employerInitial } from "./TaxCoordination";
import SignatureCanvas from "react-signature-canvas";
import { useTranslation } from "react-i18next";
import DateField from "./inputElements/DateField";
import logo from "../../Assets/image/logo.png";
import check from "../../Assets/image/icons/check-mark.png";
import TextField from "./inputElements/TextField";
import ChangeYear from "./ChangeYear";
import { objectToFormData } from "../../Utils/common.utils";
import moment from "moment";
import FullPageLoader from "../../Components/common/FullPageLoader";
import GeneralInfo from "./GeneralInfo";
import SafeAndGear from "../../Admin/Pages/safeAndGear/SafeAndGear";
import WorkerContract from "../WorkerContract";
import InsuranceForm from "../InsuranceForm";
import { GrFormPreviousLink } from "react-icons/gr";
import { GrFormNextLink } from "react-icons/gr";
import ManpowerSaftyForm from "../ManpowerSaftyForm";
import useWindowWidth from "../../Hooks/useWindowWidth";

const currentDate = moment().format("YYYY-MM-DD");

function AllForms() {

    const sigRef = useRef();
    const alert = useAlert();
    const param = useParams();
    const navigate = useNavigate()
    const { t } = useTranslation();
    const windowWidth = useWindowWidth();
    const [loading, setLoading] = useState(false)
    const [mobileView, setMobileView] = useState(false);

    const QueryParams = new URLSearchParams(location.search);
    const page = QueryParams.get("page");
    const type = QueryParams.get("type");

    const decodeId = Base64.decode(param.id);
    let numbersOnly = decodeId.replace(/\D/g, ''); // This will remove all non-digit characters

    const [id, setId] = useState(numbersOnly);
    const [formId, setFormId] = useState(param.formId ? Base64.decode(param.formId) : null)

    const [formValues, setFormValues] = useState(null);
    const [isSubmitted, setIsSubmitted] = useState(false);
    const [savingType, setSavingType] = useState("submit");
    const [formSubmitted, setFormSubmitted] = useState(false);
    const [worker, setWorker] = useState([])
    const [nextStep, setNextStep] = useState(page ? parseInt(page) : 1);
    const [isManpower, setIsManpower] = useState(false)
    const [activeBubble, setActiveBubble] = useState(null);

    useEffect(() => {
        if (windowWidth < 767) {
            setMobileView(true)
        } else {
            setMobileView(false)
        }
    }, [windowWidth])

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const handleBubbleToggle = (fieldName) => {
        // Toggle the bubble visibility by comparing with the current active bubble
        setActiveBubble((prev) => (prev === fieldName ? null : fieldName));
    };

    const scrollToError = (errors) => {
        const errorFields = Object.keys(errors);
        if (errorFields.length > 0) {
            const firstErrorField = errorFields[0];

            const errorElement = document.getElementById(firstErrorField);
            if (errorElement) {
                errorElement.scrollIntoView({ behavior: "smooth" });
                errorElement.focus();
            }
        }
    };

    const handleNextPrev = (e) => {
        window.scrollTo(0, 0);
        // Check if the condition is met to prevent incrementing nextStep
        if (param.formId && nextStep === 3) {
            // Optional: Show a message or handle specific logic here
            return; // Do nothing if the condition is met
        }

        if (e.target.name === "prev") {
            setNextStep(prev => prev - 1);
        } else {
            setNextStep(prev => prev + 1);
        }

    }


    const getWorker = () => {
        axios
            .get(`/api/worker/${param.id}`)
            .then((res) => {
                const { worker: workData, forms: formData } = res.data;
                setIsManpower(workData.manpower_company_id == 1 ? true : false)
                setWorker(workData);
                setFormId(formData.form101Form.id)

            })
            .catch((err) => {
                if (err?.response?.data?.message) {
                    alert.error(err.response.data.message);
                }
            });
    };

    useEffect(() => {
        getWorker();
    }, []);


    const formSchema = {
        step1: yup.object({
            employeeIsraeliResident: yup
                .string()
                .required(t("form101.errorMsg.IsraeliResidentReq")),
            employerName: yup.string().trim().nullable(),
            employerAddress: yup.string().trim().nullable(),
            // employerPhone: yup
            //     .string()
            //     .trim()
            //     .matches(/^\d{10}$/, t("form101.errorMsg.invalidPhone"))
            //     .nullable(),
            employerDeductionsFileNo: yup
                .number()
                .typeError(t("form101.errorMsg.deductionsFileNumber"))
                .nullable(),
            employeeFirstName: yup
                .string()
                .required(t("form101.errorMsg.NameRequired")),
            employeeLastName: yup
                .string()
                .required(t("form101.errorMsg.NameRequired")),
            employeeIdentityType: yup
                .string()
                .required(t("form101.errorMsg.IdentityType")),
            employeeIdNumber: yup.string().when("employeecountry", {
                is: "Israel",
                then: () =>
                    yup.string().required(t("form101.errorMsg.IdNumberReq")),
                otherwise: () => yup.string().nullable(),
            }),
            employeeIdCardCopy: yup.mixed().when("employeecountry", {
                is: "Israel",
                then: () =>
                    yup.mixed().required(t("form101.errorMsg.IdCardCopyReq")),
                otherwise: () => yup.mixed().nullable(),
            }),
            employeecountry: yup.string().when("employeeIdentityType", {
                is: "Passport",
                then: () => yup.string().required(t("form101.errorMsg.countryReq")),
                otherwise: () => yup.string().nullable(),
            }),
            employeePassportNumber: yup.string().when("employeeIdentityType", {
                is: "Passport",
                then: () =>
                    yup.string().required(t("form101.errorMsg.passportNumReq")),
                otherwise: () => yup.string().nullable(),
            }),
            employeepassportCopy: yup.mixed().when("employeeIdentityType", {
                is: "Passport",
                then: () =>
                    yup.mixed().required(t("form101.errorMsg.passpoerCopyReq")),
                otherwise: () => yup.mixed().nullable(),
            }),
            employeeResidencePermit: yup.mixed().when("employeeIdentityType", {
                is: "Passport",
                then: () =>
                    yup.mixed().required(t("form101.errorMsg.residancePermit")),
                otherwise: () => yup.mixed().nullable(),
            }),
            employeeDob: yup.date().required(t("form101.errorMsg.dobReq")),
            employeeDateOfAliyah: yup.date().nullable(),
            employeeCity: yup.string().required(t("form101.errorMsg.CityReq")),
            employeeStreet: yup.string().required(t("form101.errorMsg.StreetReq")),
            employeeHouseNo: yup
                .string()
                .required(t("form101.errorMsg.HouseNoReq")),
            employeePostalCode: yup
                .string()
                .required(t("form101.errorMsg.PostalCodeReq")),
            employeeMobileNo: yup
                .string()
                .required(t("form101.errorMsg.MobileNoReq")),
            employeePhoneNo: yup.string().nullable(),
            employeeEmail: yup
                .string()
                .email(t("form101.errorMsg.EmailInvalid"))
                .required(t("form101.errorMsg.EmailReq")),
            employeeSex: yup.string().required(t("form101.errorMsg.SexReq")),
            employeeMaritalStatus: yup
                .string()
                .required(t("form101.errorMsg.MaritalStatusReq")),

            employeeCollectiveMoshavMember: yup
                .string()
                .required(t("form101.errorMsg.CollectiveMoshavMemberReq")),
            employeeHealthFundMember: yup
                .string()
                .required(t("form101.errorMsg.HealthFundMemberReq")),
            employeeHealthFundname: yup
                .string()
                .when("employeeHealthFundMember", {
                    is: "Yes",
                    then: () =>
                        yup
                            .string()
                            .required(t("form101.errorMsg.HealthFundnameReq")),
                })
                .nullable(),
            employeemyIncomeToKibbutz: yup
                .string()
                .when("employeeCollectiveMoshavMember", {
                    is: "Yes",
                    then: () =>
                        yup.string().required(t("form101.errorMsg.thisFieldReq")),
                })
                .nullable(),
            DateOfBeginningWork: yup
                .date()
                .required(t("form101.errorMsg.dateOfBeginReq")),
        }),
        step2: yup.object({
            children: yup.array().of(
                yup.object().shape({
                    firstName: yup
                        .string()
                        .required(t("form101.errorMsg.NameRequired")),
                    IdNumber: yup
                        .string()
                        .required(t("form101.errorMsg.NameRequired")),
                    Dob: yup.date().required(t("form101.errorMsg.dobReq")),
                    inCustody: yup.boolean(),
                    haveChildAllowance: yup.boolean(),
                })
            ),
            incomeType: yup
                .string()
                .oneOf(
                    [
                        "Monthly salary",
                        "Salary for additional employment",
                        "Partial salary",
                        "Wage (Daily rate of pay)",
                        "Allowance",
                        "Scholarship",
                    ],
                    t("form101.errorMsg.incomeTypeReq")
                )
        }),
        step3: yup.object({
            otherIncome: yup.object().shape({
                haveincome: yup
                    .string()
                    .required(t("form101.errorMsg.thisFieldReq")),
                incomeType: yup
                    .array()
                    .of(yup.string())
                    .when("haveincome", {
                        is: "Yes",
                        then: () =>
                            yup
                                .array()
                                .of(yup.string())
                                .min(1, t("form101.errorMsg.incomeTypeReq"))
                                .required(t("form101.errorMsg.incomeTypeReq")),
                    }),
                scholarship: yup.boolean(),
                taxCreditsAtOtherIncome: yup
                    .string()
                    .when("haveincome", {
                        is: "Yes",
                        then: () =>
                            yup
                                .string()
                                .required(t("form101.errorMsg.taxCreditReq")),
                    })
                    .nullable(),
                studyFund: yup.boolean(),
                pensionInsurance: yup.boolean(),
            }),
            Spouse: yup.mixed().when("employeeMaritalStatus", {
                is: "Married",
                then: () =>
                    yup
                        .object()
                        .shape({
                            firstName: yup
                                .string()
                                .required(t("form101.errorMsg.fNameReq")),
                            lastName: yup
                                .string()
                                .required(t("form101.errorMsg.lNameReq")),
                            Identity: yup
                                .string()
                                .required(t("form101.errorMsg.SelectOpt"))
                                .oneOf(
                                    ["IDNumber", "Passport"],
                                    t("form101.errorMsg.invalidopt")
                                ),
                            Country: yup
                                .string()
                                .when("Identity", {
                                    is: "Passport",
                                    then: () =>
                                        yup
                                            .string()
                                            .required(
                                                t("form101.errorMsg.countryReq")
                                            ),
                                })
                                .nullable(),
                            passportNumber: yup
                                .string()
                                .when("Identity", {
                                    is: "Passport",
                                    then: () =>
                                        yup
                                            .string()
                                            .required(
                                                t("form101.errorMsg.passportNumReq")
                                            ),
                                })
                                .nullable(),
                            IdNumber: yup.string().when("Identity", {
                                is: "IDNumber",
                                then: () =>
                                    yup
                                        .string()
                                        .required(
                                            t("form101.errorMsg.IdNumberReq")
                                        ),
                            }),
                            Dob: yup.date().required(t("form101.errorMsg.dobReq")),
                            DateOFAliyah: yup.date(),
                            hasIncome: yup
                                .string()
                                .required(t("form101.errorMsg.incomeReq")),
                            incomeTypeOpt1: yup.boolean(),
                            incomeTypeOpt2: yup.boolean(),
                        })
                        .test({
                            name: "atLeastOneCheckbox",
                            test: function (value) {
                                if (
                                    value.hasIncome === "Yes" &&
                                    !value.incomeTypeOpt1 &&
                                    !value.incomeTypeOpt2
                                ) {
                                    throw this.createError({
                                        path: "Spouse.hasIncome",
                                        message: t(
                                            "form101.errorMsg.pleaseSelectOneIncomeType"
                                        ),
                                    });
                                }
                                return true;
                            },
                        }),
                otherwise: () => yup.mixed().nullable(),
            }),
            TaxExemption: yup.object().shape({
                isIsraelResident: yup.string().nullable(), // Add validation rules if needed
                disabled: yup.boolean(),
                disabledCertificate: yup.mixed().when("disabled", {
                    is: true,
                    then: () =>
                        yup.mixed().required(t("form101.errorMsg.certificateReq")),
                    otherwise: () => yup.mixed().nullable(),
                }),
                disabledCompensation: yup.boolean(),
                disabledCompensationCertificate: yup
                    .mixed()
                    .when("disabledCompensation", {
                        is: true,
                        then: () =>
                            yup
                                .mixed()
                                .required(
                                    t(
                                        "form101.errorMsg.certificateOfMonthlyCompensation"
                                    )
                                ),
                        otherwise: () => yup.mixed().nullable(),
                    }),
                exm3: yup.boolean(),
                exm3Date: yup.date().when("exm3", {
                    is: true,
                    then: () =>
                        yup.date().required(t("form101.errorMsg.FromDateReq")),
                    otherwise: () => yup.date().nullable(),
                }),
                exm3Locality: yup.string().when("exm3", {
                    is: true,
                    then: () =>
                        yup.string().required(t("form101.errorMsg.LocalityReq")),
                    otherwise: () => yup.string().nullable(),
                }),
                exm3Certificate: yup.mixed().when("exm3", {
                    is: true,
                    then: () =>
                        yup.mixed().required(t("form101.errorMsg.certificateReq")),
                    otherwise: () => yup.mixed().nullable(),
                }),
                exm4: yup.boolean(),
                exm4FromDate: yup.date().when("TaxExemption.exm4", {
                    is: true,
                    then: () =>
                        yup.date().required(t("form101.errorMsg.FromDateReq")),
                    otherwise: () => yup.date().nullable(),
                }),
                exm4ImmigrationCertificate: yup.mixed().when("TaxExemption.exm4", {
                    is: true,
                    then: () =>
                        yup
                            .mixed()
                            .required(
                                t("form101.errorMsg.ImmegrationCertificateExm4")
                            ),
                    otherwise: () => yup.mixed().nullable(),
                }),
                exm4NoIncomeDate: yup.date().when("exm4", {
                    is: true,
                    then: () =>
                        yup
                            .date()
                            .required(t("form101.errorMsg.noIncomeDateReqExam4")),
                    otherwise: () => yup.date().nullable(),
                }),
                exm5: yup.boolean(),
                exm5disabledCirtificate: yup.mixed().when("exm5", {
                    is: true,
                    then: () =>
                        yup
                            .mixed()
                            .required(t("form101.errorMsg.disableCertificateReq")),
                    otherwise: () => yup.mixed().nullable(),
                }),
                exm6: yup.boolean(),
                exm7: yup.boolean(),
                exm7NoOfChild: yup.number().when(["exm7", "children"], {
                    is: (exm7, children) =>
                        exm7 && Array.isArray(children) && children.length > 0,
                    then: () =>
                        yup.number().required(t("form101.errorMsg.noOfBorn")),
                    otherwise: () => yup.number(),
                }),
                exm7NoOfChild1to5: yup.number().when(["exm7", "children"], {
                    is: (exm7, children) =>
                        exm7 && Array.isArray(children) && children.length > 0,
                    then: () =>
                        yup.number().required(t("form101.errorMsg.noOdBorn1to5")),
                    otherwise: () => yup.number(),
                }),
                exm7NoOfChild6to17: yup.number().when(["exm7", "children"], {
                    is: (exm7, children) =>
                        exm7 && Array.isArray(children) && children.length > 0,
                    then: () =>
                        yup.number().required(t("form101.errorMsg.noOdBorn6to17")),
                    otherwise: () => yup.number(),
                }),
                exm7NoOfChild18: yup.number().when(["exm7", "children"], {
                    is: (exm7, children) =>
                        exm7 && Array.isArray(children) && children.length > 0,
                    then: () =>
                        yup.number().required(t("form101.errorMsg.noOfBor18")),
                    otherwise: () => yup.number(),
                }),
                exm8: yup.boolean(),
                exm8NoOfChild: yup.number().when(["exm8", "children"], {
                    is: (exm8, children) =>
                        exm8 && Array.isArray(children) && children.length > 0,
                    then: () =>
                        yup.number().required(t("form101.errorMsg.noOfBorn")),
                    otherwise: () => yup.number(),
                }),
                exm8NoOfChild1to5: yup.number().when(["exm8", "children"], {
                    is: (exm8, children) =>
                        exm8 && Array.isArray(children) && children.length > 0,
                    then: () =>
                        yup.number().required(t("form101.errorMsg.noOdBorn1to5")),
                    otherwise: () => yup.number(),
                }),
                exm8NoOfChild6to17: yup.number().when(["exm8", "children"], {
                    is: (exm8, children) =>
                        exm8 && Array.isArray(children) && children.length > 0,
                    then: () =>
                        yup.number().required(t("form101.errorMsg.noOdBorn6to17")),
                    otherwise: () => yup.number(),
                }),
                exm9: yup.boolean(),
                // No need to specify validation for exm9
                exm10: yup.boolean(),
                exm10Certificate: yup.mixed().when("exm10", {
                    is: true,
                    then: () =>
                        yup.mixed().required(t("form101.errorMsg.courtOrder")),
                    otherwise: () => yup.mixed().nullable(),
                }),
                exm11: yup.boolean(),
                exm11NoOfChildWithDisibility: yup.number().when("exm11", {
                    is: true,
                    then: () =>
                        yup
                            .number()
                            .required(t("form101.errorMsg.NoOfChilDisability")),
                    otherwise: () => yup.number(),
                }),
                exm11Certificate: yup.mixed().when("exm11", {
                    is: true,
                    then: () =>
                        yup
                            .mixed()
                            .required(
                                t("form101.errorMsg.NoOfChilDisabilityCertificate")
                            ),
                    otherwise: () => yup.mixed().nullable(),
                }),
                exm12: yup.boolean(),
                exm12Certificate: yup.mixed().when("exm12", {
                    is: true,
                    then: () =>
                        yup
                            .mixed()
                            .required(t("form101.errorMsg.photocopyCourtOrder")),
                    otherwise: () => yup.mixed().nullable(),
                }),
                exm13: yup.boolean(),
                // No need to specify validation for exm13
                exm14: yup.boolean(),
                exm14BeginingDate: yup.date().when("exm14", {
                    is: true,
                    then: () =>
                        yup
                            .date()
                            .required(t("form101.errorMsg.dateOfBeginService")),
                    otherwise: () => yup.date().nullable(),
                }),
                exm14EndDate: yup.date().when("exm14", {
                    is: true,
                    then: () =>
                        yup.date().required(t("form101.errorMsg.dateOfEndService")),
                    otherwise: () => yup.date().nullable(),
                }),
                exm14Certificate: yup.mixed().when("exm14", {
                    is: true,
                    then: () =>
                        yup
                            .mixed()
                            .required(t("form101.errorMsg.dischargeCertificate")),
                    otherwise: () => yup.mixed().nullable(),
                }),
                exm15: yup.boolean(),
                exm15Certificate: yup.mixed().when("exm15", {
                    is: true,
                    then: () => yup.mixed().required(t("form101.errorMsg.form119")),
                    otherwise: () => yup.mixed().nullable(),
                }),
            }),
            TaxCoordination: yup.object().shape({
                hasTaxCoordination: yup.boolean(),
                requestReason: yup
                    .string()
                    .when("hasTaxCoordination", {
                        is: true,
                        then: () =>
                            yup
                                .string()
                                .required(
                                    t("form101.errorMsg.ReasonTaxCordination")
                                ),
                    })
                    .nullable(),
                requestReason1Certificate: yup
                    .mixed()
                    .nullable()
                    .when("requestReason", {
                        is: "reason1",
                        then: () =>
                            yup
                                .mixed()
                                .required(t("form101.errorMsg.proofPreIncome")),
                    }),
                requestReason3Certificate: yup
                    .mixed()
                    .nullable()
                    .when("requestReason", {
                        is: "reason3",
                        then: () =>
                            yup
                                .mixed()
                                .required(
                                    t("form101.errorMsg.taxCoordinationCerti")
                                ),
                    }),
                employer: yup.array().when("requestReason", {
                    is: "reason2",
                    then: () =>
                        yup
                            .array()
                            .of(
                                yup.object().shape({
                                    firstName: yup
                                        .string()
                                        .required(t("form101.errorMsg.fNameReq")),
                                    address: yup
                                        .string()
                                        .required(t("form101.errorMsg.addressReq")),
                                    fileNumber: yup
                                        .string()
                                        .required(
                                            t("form101.errorMsg.deductionFileReq")
                                        ),
                                    MonthlyIncome: yup
                                        .number()
                                        .required(
                                            t("form101.errorMsg.MonthlyIncomeReq")
                                        ),
                                    Tax: yup
                                        .number()
                                        .required(
                                            t("form101.errorMsg.taxDeductionReq")
                                        ),
                                    incomeType: yup
                                        .string()
                                        .required(
                                            t("form101.errorMsg.TypeIncomeReq")
                                        ),
                                    payslip: yup
                                        .mixed()
                                        .required(t("form101.errorMsg.paySlipReq")),
                                })
                            )
                            .required(t("form101.errorMsg.oneEmployer")),
                }),
            }),
            date: yup.date().required(t("form101.errorMsg.dateReq")),
            sender: yup.object().shape({
                employeeEmail: yup
                    .string()
                    .email()
                    .required(t("form101.errorMsg.employeeEmail")),
                employerEmail: yup
                    .string()
                    .email()
                    .required(t("form101.errorMsg.employerEmail")),
            }),
            signature: yup.mixed().required(t("form101.errorMsg.sign")),
        })
    }
    const {
        values,
        touched,
        errors,
        handleBlur,
        handleChange,
        handleSubmit,
        setFieldValue,
        validateForm,
        onValidationChange,
        isSubmitting,
        isValid,
    } = useFormik({
        initialValues: {
            employerName:
                formValues && formValues.employerName
                    ? formValues.employerName
                    : "",
            employerAddress:
                formValues && formValues.employerAddress
                    ? formValues.employerAddress
                    : "",
            employerPhone:
                formValues && formValues.employerPhone
                    ? formValues.employerPhone
                    : "",
            employerDeductionsFileNo:
                formValues && formValues.employerDeductionsFileNo
                    ? formValues.employerDeductionsFileNo
                    : "",
            employeeFirstName:
                formValues && formValues.employeeFirstName
                    ? formValues.employeeFirstName
                    : "",
            employeeLastName:
                formValues && formValues.employeeLastName
                    ? formValues.employeeLastName
                    : "",
            employeeIdentityType:
                formValues && formValues.employeeIdentityType
                    ? formValues.employeeIdentityType
                    : "IDNumber",
            employeeIdNumber:
                formValues && formValues.employeeIdNumber
                    ? formValues.employeeIdNumber
                    : "",
            employeeIdCardCopy:
                formValues && formValues.employeeIdCardCopy
                    ? formValues.employeeIdCardCopy
                    : null,
            employeecountry:
                formValues && formValues.employeecountry
                    ? formValues.employeecountry
                    : "",
            employeePassportNumber:
                formValues && formValues.employeePassportNumber
                    ? formValues.employeePassportNumber
                    : "",
            employeepassportCopy:
                formValues && formValues.employeepassportCopy
                    ? formValues.employeepassportCopy
                    : null,
            employeeResidencePermit:
                formValues && formValues.employeeResidencePermit
                    ? formValues.employeeResidencePermit
                    : null,
            employeeDob:
                formValues && formValues.employeeDob
                    ? formValues.employeeDob
                    : "",
            employeeDateOfAliyah:
                formValues && formValues.employeeDateOfAliyah
                    ? formValues.employeeDateOfAliyah
                    : "",
            employeeCity:
                formValues && formValues.employeeCity
                    ? formValues.employeeCity
                    : "",
            employeeStreet:
                formValues && formValues.employeeStreet
                    ? formValues.employeeStreet
                    : "",
            employeeHouseNo:
                formValues && formValues.employeeHouseNo
                    ? formValues.employeeHouseNo
                    : "",
            employeePostalCode:
                formValues && formValues.employeePostalCode
                    ? formValues.employeePostalCode
                    : "",
            employeeMobileNo:
                formValues && formValues.employeeMobileNo
                    ? formValues.employeeMobileNo
                    : "",
            employeePhoneNo:
                formValues && formValues.employeePhoneNo
                    ? formValues.employeePhoneNo
                    : "",
            employeeEmail:
                formValues && formValues.employeeEmail
                    ? formValues.employeeEmail
                    : "",
            employeeSex:
                formValues && formValues.employeeSex
                    ? formValues.employeeSex
                    : "",
            employeeMaritalStatus:
                formValues && formValues.employeeMaritalStatus
                    ? formValues.employeeMaritalStatus
                    : "",
            employeeIsraeliResident:
                formValues && formValues.employeeIsraeliResident
                    ? formValues.employeeIsraeliResident
                    : "",
            employeeCollectiveMoshavMember:
                formValues && formValues.employeeCollectiveMoshavMember
                    ? formValues.employeeCollectiveMoshavMember
                    : "",
            employeeHealthFundMember:
                formValues && formValues.employeeHealthFundMember
                    ? formValues.employeeHealthFundMember
                    : "",
            employeeHealthFundname:
                formValues && formValues.employeeHealthFundname
                    ? formValues.employeeHealthFundname
                    : "",
            employeemyIncomeToKibbutz:
                formValues && formValues.employeemyIncomeToKibbutz
                    ? formValues.employeemyIncomeToKibbutz
                    : "",
            incomeType:
                formValues && formValues.incomeType
                    ? formValues.incomeType
                    : "",
            DateOfBeginningWork:
                formValues && formValues.DateOfBeginningWork
                    ? formValues.DateOfBeginningWork
                    : "",
            children:
                formValues && formValues.children ? formValues.children : [],
            otherIncome: {
                haveincome:
                    formValues &&
                        formValues.otherIncome &&
                        formValues.otherIncome.haveincome
                        ? formValues.otherIncome.haveincome
                        : "",
                incomeType:
                    formValues &&
                        formValues.otherIncome &&
                        formValues.otherIncome.incomeType
                        ? formValues.otherIncome.incomeType
                        : [],
                taxCreditsAtOtherIncome:
                    formValues &&
                        formValues.otherIncome &&
                        formValues.otherIncome.taxCreditsAtOtherIncome
                        ? formValues.otherIncome.taxCreditsAtOtherIncome
                        : "",
                allowance:
                    formValues &&
                        formValues.otherIncome &&
                        formValues.otherIncome.allowance
                        ? formValues.otherIncome.allowance
                        : false,
                scholarship:
                    formValues &&
                        formValues.otherIncome &&
                        formValues.otherIncome.scholarship
                        ? formValues.otherIncome.scholarship
                        : false,
            },
            Spouse: {
                firstName:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.firstName
                        ? formValues.Spouse.firstName
                        : "",
                lastName:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.lastName
                        ? formValues.Spouse.lastName
                        : "",
                Identity:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.Identity
                        ? formValues.Spouse.Identity
                        : "",
                Country:
                    formValues && formValues.Spouse && formValues.Spouse.Country
                        ? formValues.Spouse.Country
                        : "",
                passportNumber:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.passportNumber
                        ? formValues.Spouse.passportNumber
                        : "",
                IdNumber:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.IdNumber
                        ? formValues.Spouse.IdNumber
                        : "",
                Dob:
                    formValues && formValues.Spouse && formValues.Spouse.Dob
                        ? formValues.Spouse.Dob
                        : "",
                DateOFAliyah:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.DateOFAliyah
                        ? formValues.Spouse.DateOFAliyah
                        : "",
                hasIncome:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.hasIncome
                        ? formValues.Spouse.hasIncome
                        : "",
                incomeType:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.incomeType
                        ? formValues.Spouse.incomeType
                        : "",
                incomeTypeOpt1:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.incomeTypeOpt1
                        ? formValues.Spouse.incomeTypeOpt1
                        : false,
                incomeTypeOpt2:
                    formValues &&
                        formValues.Spouse &&
                        formValues.Spouse.incomeTypeOpt2
                        ? formValues.Spouse.incomeTypeOpt2
                        : false,
            },
            TaxExemption: {
                isIsraelResident:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.isIsraelResident
                        ? formValues.TaxExemption.isIsraelResident
                        : "", // Initial value for isIsraelResident
                disabled:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.disabled
                        ? formValues.TaxExemption.disabled
                        : false,
                disabledCertificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.disabledCertificate
                        ? formValues.TaxExemption.disabledCertificate
                        : "",
                disabledCompensation:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.disabledCompensation
                        ? formValues.TaxExemption.disabledCompensation
                        : false,
                disabledCompensationCertificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.disabledCompensationCertificate
                        ? formValues.TaxExemption
                            .disabledCompensationCertificate
                        : "",
                exm3:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm3
                        ? formValues.TaxExemption.exm3
                        : false,
                exm3Date:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm3Date
                        ? formValues.TaxExemption.exm3Date
                        : "",
                exm3Locality:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm3Locality
                        ? formValues.TaxExemption.exm3Locality
                        : "",
                exm3Certificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm3Certificate
                        ? formValues.TaxExemption.exm3Certificate
                        : "",
                exm4:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm4
                        ? formValues.TaxExemption.exm4
                        : false,
                exm4FromDate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm4FromDate
                        ? formValues.TaxExemption.exm4FromDate
                        : "",
                exm4ImmigrationCertificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm4ImmigrationCertificate
                        ? formValues.TaxExemption.exm4ImmigrationCertificate
                        : "",
                exm4NoIncomeDate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm4NoIncomeDate
                        ? formValues.TaxExemption.exm4NoIncomeDate
                        : "",
                exm5:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm5
                        ? formValues.TaxExemption.exm5
                        : false,
                exm5disabledCirtificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm5disabledCirtificate
                        ? formValues.TaxExemption.exm5disabledCirtificate
                        : "",
                exm6:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm6
                        ? formValues.TaxExemption.exm6
                        : false,
                exm7:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm7
                        ? formValues.TaxExemption.exm7
                        : false,
                exm7NoOfChild:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm7NoOfChild
                        ? formValues.TaxExemption.exm7NoOfChild
                        : 0,
                exm7NoOfChild1to5:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm7NoOfChild1to5
                        ? formValues.TaxExemption.exm7NoOfChild1to5
                        : 0,
                exm7NoOfChild6to17:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm7NoOfChild6to17
                        ? formValues.TaxExemption.exm7NoOfChild6to17
                        : 0,
                exm7NoOfChild18:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm7NoOfChild18
                        ? formValues.TaxExemption.exm7NoOfChild18
                        : 0,
                exm8:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm8
                        ? formValues.TaxExemption.exm8
                        : false,
                exm8NoOfChild:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm8NoOfChild
                        ? formValues.TaxExemption.exm8NoOfChild
                        : 0,
                exm8NoOfChild1to5:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm8NoOfChild1to5
                        ? formValues.TaxExemption.exm8NoOfChild1to5
                        : 0,
                exm8NoOfChild6to17:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm8NoOfChild6to17
                        ? formValues.TaxExemption.exm8NoOfChild6to17
                        : 0,
                exm9:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm9
                        ? formValues.TaxExemption.exm9
                        : false,
                exm10:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm10
                        ? formValues.TaxExemption.exm10
                        : false,
                exm10Certificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm10Certificate
                        ? formValues.TaxExemption.exm10Certificate
                        : "",
                exm11:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm11
                        ? formValues.TaxExemption.exm11
                        : false,
                exm11NoOfChildWithDisibility:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm11NoOfChildWithDisibility
                        ? formValues.TaxExemption.exm11NoOfChildWithDisibility
                        : 0,
                exm11Certificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm11Certificate
                        ? formValues.TaxExemption.exm11Certificate
                        : "",
                exm12:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm12
                        ? formValues.TaxExemption.exm12
                        : false,
                exm12Certificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm12Certificate
                        ? formValues.TaxExemption.exm12Certificate
                        : "",
                exm13:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm13
                        ? formValues.TaxExemption.exm13
                        : false,
                exm14:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm14
                        ? formValues.TaxExemption.exm14
                        : false,
                exm14BeginingDate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm14BeginingDate
                        ? formValues.TaxExemption.exm14BeginingDate
                        : "",
                exm14EndDate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm14EndDate
                        ? formValues.TaxExemption.exm14EndDate
                        : "",
                exm14Certificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm14Certificate
                        ? formValues.TaxExemption.exm14Certificate
                        : "",
                exm15:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm15
                        ? formValues.TaxExemption.exm15
                        : false,
                exm15Certificate:
                    formValues &&
                        formValues.TaxExemption &&
                        formValues.TaxExemption.exm15Certificate
                        ? formValues.TaxExemption.exm15Certificate
                        : "",
            },
            TaxCoordination: {
                hasTaxCoordination:
                    formValues &&
                        formValues.TaxCoordination &&
                        formValues.TaxCoordination.hasTaxCoordination
                        ? formValues.TaxCoordination.hasTaxCoordination
                        : false,
                requestReason:
                    formValues &&
                        formValues.TaxCoordination &&
                        formValues.TaxCoordination.requestReason
                        ? formValues.TaxCoordination.requestReason
                        : "",
                requestReason1Certificate:
                    formValues &&
                        formValues.TaxCoordination &&
                        formValues.TaxCoordination.requestReason1Certificate
                        ? formValues.TaxCoordination.requestReason1Certificate
                        : null,
                requestReason3Certificate:
                    formValues &&
                        formValues.TaxCoordination &&
                        formValues.TaxCoordination.requestReason3Certificate
                        ? formValues.TaxCoordination.requestReason3Certificate
                        : null,
                employer:
                    formValues &&
                        formValues.TaxCoordination &&
                        formValues.TaxCoordination.employer
                        ? formValues.TaxCoordination.employer
                        : [employerInitial],
            },
            date: isSubmitted ? formValues.date : currentDate,
            sender: {
                employeeEmail:
                    formValues &&
                        formValues.sender &&
                        formValues.sender.employeeEmail
                        ? formValues.sender.employeeEmail
                        : "",
                employerEmail:
                    formValues && formValues.sender
                        ? formValues.sender.employerEmail
                        : "office@broomservice.co.il",
            },
            signature:
                formValues && formValues.signature ? formValues.signature : "",
        },
        enableReinitialize: true,
        validationSchema: formSchema[`step${nextStep}`], // Dynamically set schema based on current step
        onSubmit: (values) => {
            if (!isSubmitted) {
                setLoading(true);
                // Convert JSON object to FormData
                let formData = objectToFormData(values);
                formData.append("savingType", savingType);
                formData.append("formId", formId);
                formData.append("step", nextStep);

                axios
                    .post(`/api/form101/${id}`, formData, {
                        headers: {
                            Accept: "application/json, text/plain, */*",
                            "Content-Type": "multipart/form-data",
                        },
                    })
                    .then((response) => {
                        if (savingType == "submit" && formId) {
                            alert.success(response.data.message);
                        }
                        setLoading(false);
                        setSavingType("submit");
                        setFormSubmitted(true)
                    })
                    .catch((e) => {
                        setLoading(false);
                        setSavingType("submit");
                    });
            }
        },
        validateOnBlur: true,
        validateOnChange: false,
        validateOnMount: false,
    });

    const handleSignatureEnd = () => {
        setFieldValue("signature", sigRef.current.toDataURL());
    };
    const clearSignature = () => {
        sigRef.current.clear();
        setFieldValue("signature", "");
    };


    const disableInputs = () => {
        // Disable inputs within the div with the id "targetDiv"
        const inputs = document.querySelectorAll(".targetDiv input ");
        inputs.forEach((input) => {
            input.disabled = true;
        });
        const selects = document.querySelectorAll(".targetDiv select");
        selects.forEach((select) => {
            select.disabled = true;
        });

        const buttons = document.querySelectorAll("button.action-btn");
        buttons.forEach((_button) => {
            _button.style.display = "none";
        });
    };

    const getForm = () => {
        axios.get(`/api/get101/${id}/${formId}`).then((res) => {
            i18next.changeLanguage(res.data.lng);


            if (res.data.lng == "heb") {
                import("../../Assets/css/rtl.css");
                document.querySelector("html").setAttribute("dir", "rtl");
            } else {
                document.querySelector("html").removeAttribute("dir");
            }

            if (res.data.form) {
                console.log(res.data.form, "form");
                
                setFormValues(res.data.form.data);

                if (res.data.form.submitted_at) {
                    setTimeout(() => {
                        disableInputs();
                    }, 2000);
                    setIsSubmitted(true);
                }
            } else if (res.data.worker) {
                const _worker = res.data.worker;

                if (!page) {
                    setNextStep(res.data.worker.step)
                }

                if (_worker?.firstName !== null) {
                    setFieldValue("employeeFirstName", _worker.firstname);
                }
                if (_worker?.lastName !== null) {
                    setFieldValue("employeeLastName", _worker.lastname);
                }
                if (_worker?.address !== null) {
                    setFieldValue("employeeAddress", _worker.address);
                }
                if (_worker?.passport !== null) {
                    setFieldValue("employeePassportNumber", _worker.passport);
                }
                if (_worker?.phone !== null) {
                    setFieldValue("employeeMobileNo", _worker.phone);
                }
                if (_worker?.worker_id !== null) {
                    setFieldValue("employeeIdNumber", _worker.worker_id);
                }
                if (_worker?.country !== null) {
                    // setFieldValue("employeeCountry", _worker.country);
                    setFieldValue("employeecountry", _worker.country);
                }
                if (_worker?.first_date !== null) {
                    setFieldValue("DateOfBeginningWork", _worker?.first_date);
                }

                const workerGender = _worker.gender;
                const gender =
                    workerGender.charAt(0).toUpperCase() +
                    workerGender.slice(1);
                setFieldValue("employeeEmail", _worker.email);
                setFieldValue("sender.employeeEmail", _worker.email);
                setFieldValue("employeeSex", gender);
            }
        });
    };

    useEffect(() => {
        getForm();
    }, [id, formId, page]);

    const handleSaveAsDraft = async () => {
        if (nextStep === 3) {
            setSavingType("submit");
        } else {
            setSavingType("draft");
        }

        // if (nextStep === 7 && !param.formId && (worker.country !== "Israel" && worker.is_existing_worker !== 1)) {
        //     setSavingType("submit");
        // } else if (nextStep === 6 && !param.formId && !(worker.country !== "Israel" && worker.is_existing_worker !== 1)) {
        //     setSavingType("submit");
        // }else{
        //     setSavingType("draft");
        // }

        // setFormSubmitted(true);
        handleSubmit();
        const validationErrors = await validateForm();

        const errorsKey = Object.keys(validationErrors).length > 0;

        const errorsPresent = Object.keys(errors).length > 0;

        if (errorsKey) {
            scrollToError(validationErrors);
        } else {
            const e = {
                target: {
                    name: "next",
                }
            }
            handleNextPrev(e);
        }
    };

    const handleDocSubmit = (data) => {
        axios
            .post(`/api/document/save`, data, { headers })
            .then((res) => {
                if (res.data.errors) {
                    console.log(res.data.errors);

                } else {
                    console.log(res.data.message);
                }
            })
            .catch((err) => {
                console.log(err);
            });
    };


    const handleFileChange = (e, type) => {
        const data = new FormData();
        data.append("id", id);
        if (e.target.files.length > 0) {
            data.append(`${type}`, e.target.files[0]);
        }
        handleDocSubmit(data);
    };

    return (
        <div className=" mt-4 mb-5 bg-transparent " style={{
            margin: mobileView ? "0 20px" : "0 120px"
        }}>
            <div className="d-flex align-items-center justify-content-between flex-dir-co-1070">
                <img
                    src={logo}
                    className="img-fluid broom-logo"
                    alt="Broom Services"
                />
                {!isManpower ? (
                    <div className="d-flex flex-wrap align-items-center">
                        <span className={`badge mx-1 py-1 px-3 my-1 ${nextStep === 1 ? 'bluecolor' : 'lightgrey'}`}>{t("form101.step")} 1</span>
                        <span className="mx-2"> - </span>
                        <span className={`badge mx-1 py-1 px-3 my-1 ${nextStep === 2 ? 'bluecolor' : 'lightgrey'}`}>{t("form101.step")} 2</span>
                        <span className="mx-2"> - </span>
                        <span className={`badge mx-1 py-1 px-3 my-1 ${nextStep === 3 ? 'bluecolor' : 'lightgrey'}`}>{t("form101.step")} 3</span>
                        {
                            !param.formId ? (
                                <>
                                    <span className="mx-2"> - </span>
                                    <span className={`badge mx-1 py-1 px-3 my-1 ${nextStep === 4 ? 'bluecolor' : 'lightgrey'}`}>{t("form101.step")} 4</span>
                                    <span className="mx-2"> - </span>
                                    <span className={`badge mx-1 py-1 px-3 my-1 ${nextStep === 5 ? 'bluecolor' : 'lightgrey'}`}>{t("form101.step")} 5</span>
                                    <span className="mx-2"> - </span>
                                    <span className={`badge mx-1 py-1 px-3 my-1 ${nextStep === 6 ? 'bluecolor' : 'lightgrey'}`}>{t("form101.step")} 6</span>
                                </>
                            ) : ""
                        }
                        {
                            !param.formId && (worker.country !== "Israel" && worker.is_existing_worker !== 1) && (
                                <>
                                    <span className="mx-2"> - </span>
                                    <span className={`badge mx-1 py-1 px-3 my-1 ${nextStep === 7 ? 'bluecolor' : 'lightgrey'}`}>{t("form101.step")} 7</span>
                                </>
                            )
                        }
                    </div>
                ) : (
                    <div className="d-flex flex-wrap align-items-center">
                        {worker.country !== "Israel" && <>
                            <span className={`badge mx-1 py-1 px-3 my-1 ${nextStep === 1 ? 'bluecolor' : 'lightgrey'}`}>{t("form101.step")} 1</span>
                            <span className="mx-2"> - </span>
                            <span className={`badge mx-1 py-1 px-3 my-1 ${nextStep === 2 ? 'bluecolor' : 'lightgrey'}`}>{t("form101.step")} 2</span>
                        </>
                        }
                    </div>
                )}
            </div>

            <div className="targetDiv">
                {
                    nextStep === 1 && !isManpower ? (
                        <>
                            <GeneralInfo
                                handleBlur={handleBlur}
                                handleChange={handleChange}
                                errors={errors}
                                values={values}
                                touched={touched}
                                setFieldValue={setFieldValue}
                                handleBubbleToggle={handleBubbleToggle}
                                activeBubble={activeBubble}
                                handleFileChange={handleFileChange}
                            // identityType={identityType}
                            />
                        </>
                    ) : (
                        nextStep === 1 && <ManpowerSaftyForm setNextStep={setNextStep} />
                    )
                }

                {
                    nextStep === 2 && !isManpower ? (
                        <>
                            <p className="navyblueColor font-34 mt-4 font-w-500">{t("form101.title")}</p>
                            <div className="row mt-3">
                                <section className="col-xl ">
                                    <p className="font-w-500">
                                        {t("form101.step2(1)")}
                                    </p>
                                    <p className="font-w-500 mt-2">
                                        {t("form101.step2(2)")}
                                    </p>
                                    <p className="navyblueColor mt-4 font-24 font-w-500">{t("form101.tax_year")}</p>
                                    <p className="font-w-500 mt-2">
                                        {t("form101.step2(3)")}
                                    </p>
                                    <div className="box-heading">
                                        <EmployerDetails
                                            handleBlur={handleBlur}
                                            handleChange={handleChange}
                                            errors={errors}
                                            values={values}
                                            touched={touched}
                                            handleBubbleToggle={handleBubbleToggle}
                                            activeBubble={activeBubble}
                                        />
                                    </div>
                                    <div className="box-heading">
                                        <EmployeeDetails
                                            handleBlur={handleBlur}
                                            handleChange={handleChange}
                                            errors={errors}
                                            values={values}
                                            touched={touched}
                                            setFieldValue={setFieldValue}
                                            handleBubbleToggle={handleBubbleToggle}
                                            activeBubble={activeBubble}
                                            handleFileChange={handleFileChange}
                                        />
                                    </div>
                                </section>
                                <section className="col pl-4 pb-4 pr-4">
                                    <div className="box-heading">
                                        <ChildrenDetails
                                            handleBlur={handleBlur}
                                            handleChange={handleChange}
                                            errors={errors}
                                            values={values}
                                            touched={touched}
                                            setFieldValue={setFieldValue}
                                            handleBubbleToggle={handleBubbleToggle}
                                            activeBubble={activeBubble}
                                        />
                                    </div>
                                    <div className="box-heading">
                                        <IncomeDetails
                                            handleBlur={handleBlur}
                                            handleChange={handleChange}
                                            errors={errors}
                                            values={values}
                                            touched={touched}
                                            setFieldValue={setFieldValue}
                                            handleBubbleToggle={handleBubbleToggle}
                                            activeBubble={activeBubble}
                                        />
                                    </div>{" "}
                                </section>
                            </div>
                        </>
                    ) : (
                        nextStep === 2 && worker.country !== "Israel" ?
                            <InsuranceForm nextStep={nextStep} setNextStep={setNextStep} worker={worker} isManpower={isManpower} /> : ""
                    )
                }
                {
                    nextStep === 3 && !isManpower && (
                        <div className="row mt-3">
                            <section className="col-xl">
                                <div className="box-heading">
                                    <OtherIncome
                                        handleBlur={handleBlur}
                                        handleChange={handleChange}
                                        errors={errors}
                                        values={values}
                                        touched={touched}
                                        setFieldValue={setFieldValue}
                                        handleBubbleToggle={handleBubbleToggle}
                                        activeBubble={activeBubble}
                                    />
                                </div>
                                <div className="box-heading">
                                    <SpouseDetail
                                        handleBlur={handleBlur}
                                        handleChange={handleChange}
                                        errors={errors}
                                        values={values}
                                        touched={touched}
                                        setFieldValue={setFieldValue}
                                        handleBubbleToggle={handleBubbleToggle}
                                        activeBubble={activeBubble}
                                    />
                                </div>
                                <div className="box-heading">
                                    <TaxExemption
                                        handleBlur={handleBlur}
                                        handleChange={handleChange}
                                        errors={errors}
                                        values={values}
                                        touched={touched}
                                        setFieldValue={setFieldValue}
                                        handleBubbleToggle={handleBubbleToggle}
                                        activeBubble={activeBubble}
                                    />
                                </div>
                            </section>
                            <section className="col pt-4 pb-5">
                                <div className="box-heading ">
                                    <p className="navyblueColor font-24  font-w-500">{t("form101.year_changes")}</p>
                                    <p>{t("form101.year_changes_details1")}</p>
                                    <p>{t("form101.year_changes_details2")}</p>
                                </div>

                                <div className="box-heading">
                                    <TaxCoordination
                                        handleBlur={handleBlur}
                                        handleChange={handleChange}
                                        errors={errors}
                                        values={values}
                                        touched={touched}
                                        setFieldValue={setFieldValue}
                                        handleBubbleToggle={handleBubbleToggle}
                                        activeBubble={activeBubble}
                                    />
                                </div>

                                <div className="box-heading">
                                    <p className="navyblueColor font-24  font-w-500">{t("form101.text_disclaimer")}</p>
                                    <p>{t("form101.disclaimer_details1")}</p>
                                    <p>{t("form101.disclaimer_details2")}</p>
                                    <div className="row mt-3 justify-content-between">
                                        <div className="form-group col-sm">
                                            <DateField
                                                label={t("form101.Date")}
                                                name={"date"}
                                                required={true}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                value={values.date}
                                                readOnly
                                                error={touched.date && errors.date}
                                            />
                                        </div>
                                        {formValues && formValues.signature != null ? (
                                            <img src={formValues.signature} />
                                        ) : (
                                            <div className="col-sm">
                                                <p className="navyblueColor font-w-500"> Employee Signature *</p>
                                                <div id="signature">
                                                    <SignatureCanvas
                                                        penColor="black"
                                                        canvasProps={{
                                                            width: 250,
                                                            height: 100,
                                                            className:
                                                                "sign101 border mt-1",
                                                        }}
                                                        ref={sigRef}
                                                        onEnd={handleSignatureEnd}
                                                    />
                                                    {touched.signature &&
                                                        errors.signature &&
                                                        touched.signature &&
                                                        errors.signature && (
                                                            <p className="text-danger mr-3  ">
                                                                {errors.signature}
                                                            </p>
                                                        )}
                                                </div>
                                                <div className="text-right">
                                                    <div className="d-flex justify-content-end">
                                                        <button
                                                            className="btn navyblue px-3 mb-2"
                                                            onClick={clearSignature}
                                                        >
                                                            {t("form101.button_clear")}
                                                        </button>
                                                    </div>
                                                </div>

                                            </div>
                                        )}
                                    </div>
                                </div>
                                <div className="box-heading">
                                    <p className="navyblueColor font-24 my-2 font-w-500">{t("form101.sendTOYouAndEmployer")}</p>
                                    <div className="row justify-content-between mt-3">
                                        <div className="col-sm">
                                            <TextField
                                                name="sender.employerEmail"
                                                label={t("form101.employerEmail")}
                                                value={values.sender.employerEmail}
                                                onChange={handleChange}
                                                onBlur={handleBlur}
                                                readonly={true}
                                                error={
                                                    touched.sender &&
                                                        errors.sender &&
                                                        touched.sender.employerEmail &&
                                                        errors.sender.employerEmail
                                                        ? errors.sender.employerEmail
                                                        : ""
                                                }
                                                required
                                            />
                                        </div>
                                        <div className="col-sm">
                                            <TextField
                                                name="sender.employeeEmail"
                                                label={t("form101.employesEmail")}
                                                value={values.sender.employeeEmail}
                                                onChange={handleChange}
                                                onBlur={handleBlur}
                                                readonly={true}
                                                error={
                                                    touched.sender &&
                                                        errors.sender &&
                                                        touched.sender.employeeEmail &&
                                                        errors.sender.employeeEmail
                                                        ? errors.sender.employeeEmail
                                                        : ""
                                                }
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    )
                }
            </div>

            {
                nextStep === 4 && !isManpower && (
                    <SafeAndGear nextStep={nextStep} handleNextPrev={handleNextPrev} setNextStep={setNextStep}
                        handleBubbleToggle={handleBubbleToggle}
                        activeBubble={activeBubble}
                    />
                )
            }

            {
                (nextStep === 5 || nextStep === 6) && !isManpower && (
                    <WorkerContract nextStep={nextStep} setNextStep={setNextStep} worker={worker}
                        handleBubbleToggle={handleBubbleToggle}
                        activeBubble={activeBubble}
                    />
                )
            }
            {/* {
                    nextStep === 6 && (
                        <WorkerContract nextStep={nextStep} setNextStep={setNextStep} worker={worker}/>
                    )
                } */}

            {
                nextStep === 7 && (!param.formId && (worker.country !== "Israel" && worker.is_existing_worker !== 1)) && !isManpower && (
                    <InsuranceForm nextStep={nextStep} setNextStep={setNextStep} worker={worker}
                        handleBubbleToggle={handleBubbleToggle}
                        activeBubble={activeBubble}
                    />
                )
            }

            {
                ![4, 5, 6, 7].includes(nextStep) ? (
                    <div className="d-flex justify-content-end">
                        {nextStep !== 1 && !isManpower && (
                            <button
                                type="button"
                                onClick={(e) => handleNextPrev(e)}
                                className="navyblue py-2 px-4 mr-2"
                                name="prev"
                                style={{ borderRadius: "5px" }}
                            >
                                <GrFormPreviousLink /> Prev
                            </button>
                        )}
                        {!(param.formId && nextStep === 3) && nextStep < 7 && !isManpower && (
                            <button
                                type="button"
                                onClick={async (e) => {
                                    await handleSaveAsDraft();
                                    // handleNextPrev(e);
                                }}
                                name="next"
                                className="navyblue py-2 px-4"
                                style={{ borderRadius: "5px" }}
                            // disabled={isSubmitted}
                            >
                                Next <GrFormNextLink />
                            </button>
                        )}
                        {
                            (param.formId && nextStep === 3) && !isManpower && (
                                <button
                                    type="submit"
                                    onClick={handleSaveAsDraft}
                                    name="next"
                                    className="navyblue py-2 px-4"
                                    style={{ borderRadius: "5px" }}
                                    disabled={isSubmitted}
                                >
                                    Submit
                                </button>
                            )
                        }
                    </div>
                ) : null
            }


        </div>
    )
}

export default AllForms

