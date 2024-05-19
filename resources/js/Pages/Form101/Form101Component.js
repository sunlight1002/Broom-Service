import { useFormik } from "formik";
import React, { useEffect, useRef, useState } from "react";
import { Base64 } from "js-base64";
import Swal from "sweetalert2";
import * as yup from "yup";
import { useParams } from "react-router-dom";
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

const initialValues = {
    employerName: "",
    employerAddress: "",
    employerPhone: "",
    employerDeductionsFileNo: "",
    employeeFirstName: "",
    employeeLastName: "",
    employeeIdentityType: "IDNumber",
    employeeIdNumber: "",
    employeeIdCardCopy: null,
    employeecountry: "",
    employeePassportNumber: "",
    employeepassportCopy: null,
    employeeResidencePermit: null,
    employeeDob: "",
    employeeDateOfAliyah: "",
    employeeCity: "",
    employeeStreet: "",
    employeeHouseNo: "",
    employeePostalCode: "",
    employeeMobileNo: "",
    employeePhoneNo: "",
    employeeEmail: "",
    employeeSex: "",
    employeeMaritalStatus: "",
    employeeIsraeliResident: "",
    employeeCollectiveMoshavMember: "",
    employeeHealthFundMember: "",
    employeeHealthFundname: "",
    employeemyIncomeToKibbutz: "",
    incomeType: "",
    allowance: false,
    scholarship: false,
    DateOfBeginningWork: "",
    children: [],
    otherIncome: {
        haveincome: "",
        incomeType: [],
        taxCreditsAtOtherIncome: "",
        allowance: false,
        scholarship: false,
    },
    Spouse: {
        firstName: "",
        lastName: "",
        Identity: "",
        Country: "",
        passportNumber: "",
        IdNumber: "",
        Dob: "",
        DateOFAliyah: "",
        hasIncome: "",
        incomeType: "",
        incomeTypeOpt1: false,
        incomeTypeOpt2: false,
    },
    TaxExemption: {
        isIsraelResident: "", // Initial value for isIsraelResident
        disabled: false,
        disabledCertificate: "",
        disabledCompensation: false,
        disabledCompensationCertificate: "",
        exm3: false,
        exm3Date: "",
        exm3Locality: "",
        exm3Certificate: "",
        exm4: false,
        exm4FromDate: "",
        exm4ImmigrationCertificate: "",
        exm4NoIncomeDate: "",
        exm5: false,
        exm5disabledCirtificate: "",
        exm6: false,
        exm7: false,
        exm7NoOfChild: 0,
        exm7NoOfChild1to5: 0,
        exm7NoOfChild6to17: 0,
        exm7NoOfChild18: 0,
        exm8: false,
        exm8NoOfChild: 0,
        exm8NoOfChild1to5: 0,
        exm8NoOfChild6to17: 0,
        exm9: false,
        exm10: false,
        exm10Certificate: "",
        exm11: false,
        exm11NoOfChildWithDisibility: 0,
        exm11Certificate: "",
        exm12: false,
        exm12Certificate: "",
        exm13: false,
        exm14: false,
        exm14BeginingDate: "",
        exm14EndDate: "",
        exm14Certificate: "",
        exm15: false,
        exm15Certificate: "",
    },
    TaxCoordination: {
        hasTaxCoordination: false,
        requestReason: "",
        requestReason1Certificate: null,
        requestReason3Certificate: null,
        employer: [employerInitial],
    },
    date: "",
    sender: {
        employeeEmail: "",
        employerEmail: "office@broomservice.co.il",
    },
    signature: "",
};

const Form101Component = () => {
    const sigRef = useRef();
    const { t } = useTranslation();
    const alert = useAlert();
    const param = useParams();
    const id = Base64.decode(param.id);

    const [formValues, setFormValues] = useState(null);
    const [isSubmitted, setIsSubmitted] = useState(false);
    const [savingType, setSavingType] = useState("submit");

    const currentYear = new Date().getFullYear();

    const formSchema = yup.object({
        employerName: yup.string().trim().nullable(),
        employerAddress: yup.string().trim().nullable(),
        employerPhone: yup
            .string()
            .trim()
            .matches(/^\d{10}$/, t("form101.errorMsg.invalidPhone"))
            .nullable(),
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
        employeeIdNumber: yup.string().when("employeeIdentityType", {
            is: "IDNumber",
            then: () =>
                yup.string().required(t("form101.errorMsg.IdNumberReq")),
            otherwise: () => yup.string(),
        }),
        employeeIdCardCopy: yup.mixed().when("employeeIdentityType", {
            is: "IDNumber",
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
        employeeResidencePermit: yup.mixed().nullable(),
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
        employeeIsraeliResident: yup
            .string()
            .required(t("form101.errorMsg.IsraeliResidentReq")),
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
                ],
                t("form101.errorMsg.incomeTypeReq")
            ),
        allowance: yup.boolean(),
        scholarship: yup.boolean(),
        DateOfBeginningWork: yup
            .date()
            .required(t("form101.errorMsg.dateOfBeginReq")),
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
                yup.object().shape({
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
                                    .required(t("form101.errorMsg.countryReq")),
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
                                .required(t("form101.errorMsg.IdNumberReq")),
                    }),
                    Dob: yup.date().required(t("form101.errorMsg.dobReq")),
                    DateOFAliyah: yup.date(),
                    hasIncome: yup
                        .string()
                        .required(t("form101.errorMsg.incomeReq")),
                    incomeTypeOpt1: yup.boolean(),
                    incomeTypeOpt2: yup.boolean(),
                }).test({
                    name: 'atLeastOneCheckbox',
                    test: function(value) {
                      if (value.hasIncome === 'Yes' && !value.incomeTypeOpt1 && !value.incomeTypeOpt2) {
                        throw this.createError({
                          path: 'Spouse.hasIncome',
                          message: 'Please select at least one income type',
                        });
                      }
                      return true;
                    }
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
    });
    const {
        values,
        touched,
        errors,
        handleBlur,
        handleChange,
        handleSubmit,
        setFieldValue,
        isSubmitting,
    } = useFormik({
        initialValues: formValues ?? initialValues,
        enableReinitialize: true,
        validationSchema: formSchema,
        onSubmit: (values) => {
            // Convert JSON object to FormData
            let formData = objectToFormData(values);
            formData.append("savingType", savingType);

            axios
                .post(`/api/form101/${id}`, formData, {
                    headers: {
                        Accept: "application/json, text/plain, */*",
                        "Content-Type": "multipart/form-data",
                    },
                })
                .then((response) => {
                    setSavingType("submit");
                    alert.success(response.data.message);
                    setTimeout(() => {
                        window.location.reload(true);
                    }, 2000);
                })
                .catch((e) => {
                    setSavingType("submit");
                    Swal.fire({
                        title: "Error!",
                        text: e.response.data.message,
                        icon: "error",
                    });
                });
        },
    });
    const handleSignatureEnd = () => {
        setFieldValue("signature", sigRef.current.toDataURL());
    };
    const clearSignature = () => {
        sigRef.current.clear();
        setFieldValue("signature", "");
    };
    useEffect(() => {
        setFieldValue("sender.employerEmail", "office@broomservice.co.il");
        getForm();
    }, []);

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
    };

    const getForm = () => {
        axios.get(`/api/get101/${id}`).then((res) => {
            i18next.changeLanguage(res.data.lng);
            if (res.data.lng == "heb") {
                import("../../Assets/css/rtl.css");
                document.querySelector("html").setAttribute("dir", "rtl");
            } else {
                document.querySelector("html").removeAttribute("dir");
            }
            if (res.data.worker) {
                const worker = res.data.worker;
                setFieldValue("employeeFirstName", worker.firstname);
                setFieldValue("employeeLastName", worker.lastname);
                setFieldValue("employeeMobileNo", worker.phone);
                const workerGender = worker.gender;
                const gender =
                    workerGender.charAt(0).toUpperCase() +
                    workerGender.slice(1);
                setFieldValue("employeeEmail", worker.email);
                setFieldValue("sender.employeeEmail", worker.email);
                setFieldValue("employeeSex", gender);
            }
            if (res.data.form) {
                setFormValues(res.data.form.data);

                if (res.data.form.submitted_at) {
                    setTimeout(() => {
                        disableInputs();
                    }, 2000);
                    setIsSubmitted(true);
                }
            }
        });
    };

    // const printPdf = (e) => {
    //     window.location.href = `/pdf/${param.id}`;
    // };

    const handleSaveAsDraft = () => {
        setSavingType("draft");
        handleSubmit();
    };

    return (
        <div className="container targetDiv">
            <div className="form101 p-4">
                {/* {values.signature != null ? (
                    <>
                        <a
                            style={{ color: "white" }}
                            className="btn btn-pink float-right m-3 pdfbtn"
                            onClick={(e) => printPdf(e)}
                        >
                            {" "}
                            Print Pdf{" "}
                        </a>
                        <span className="btn btn-success float-right m-3">
                            Signed
                        </span>
                    </>
                ) : (
                    ""
                )} */}

                <img
                    src={logo}
                    className="img-fluid broom-logo"
                    alt="Broom Services"
                />
                <h1 className="text-center">{t("form101.title")}</h1>
                <p className="text-center max600">
                    {t("form101.employee_card")}
                </p>
                <p className="text-center max600">
                    {t("form101.declare_week")}
                </p>
                {/* <hr /> */}
                {/* <div className="agg-list">
                    <div className="icons">
                        <img src={check} />
                    </div>
                    <div className="agg-text">
                        <p>{t("form101.notes_1")}</p>
                    </div>
                </div>
                <div className="agg-list">
                    <div className="icons">
                        <img src={check} />
                    </div>
                    <div className="agg-text">
                        <p>{t("form101.notes_2")}</p>
                    </div>
                </div>
                <div className="agg-list">
                    <div className="icons">
                        <img src={check} />
                    </div>
                    <div className="agg-text">
                        <p>{t("form101.notes_3")}</p>
                    </div>
                </div>
                <div className="agg-list">
                    <div className="icons">
                        <img src={check} />
                    </div>
                    <div className="agg-text">
                        <p>{t("form101.notes_4")}</p>
                    </div>
                </div>
                <div className="agg-list">
                    <div className="icons">
                        <img src={check} />
                    </div>
                    <div className="agg-text">
                        <p>{t("form101.notes_5")}</p>
                    </div>
                </div>
                <div className="agg-list">
                    <div className="icons">
                        <img src={check} />
                    </div>
                    <div className="agg-text">
                        <p>{t("form101.notes_6")}</p>
                    </div>
                </div> */}
                <div className="box-heading">
                    <h2>{t("form101.texYearTitle")}</h2>
                    <p>
                        <strong>{currentYear}</strong>{" "}
                        {t("form101.year_2023_details")}
                    </p>
                </div>
                <div>
                    <form onSubmit={handleSubmit}>
                        {/* A */}
                        <div className="box-heading">
                            <EmployerDetails
                                handleBlur={handleBlur}
                                handleChange={handleChange}
                                errors={errors}
                                values={values}
                                touched={touched}
                            />
                        </div>
                        {/* B */}
                        <div className="box-heading">
                            <EmployeeDetails
                                handleBlur={handleBlur}
                                handleChange={handleChange}
                                errors={errors}
                                values={values}
                                touched={touched}
                                setFieldValue={setFieldValue}
                            />
                        </div>
                        {/* C */}
                        <div className="box-heading">
                            <ChildrenDetails
                                handleBlur={handleBlur}
                                handleChange={handleChange}
                                errors={errors}
                                values={values}
                                touched={touched}
                                setFieldValue={setFieldValue}
                            />
                        </div>
                        {/* D */}
                        <div className="box-heading">
                            <IncomeDetails
                                handleBlur={handleBlur}
                                handleChange={handleChange}
                                errors={errors}
                                values={values}
                                touched={touched}
                                setFieldValue={setFieldValue}
                            />
                        </div>{" "}
                        {/* E */}
                        <div className="box-heading">
                            <OtherIncome
                                handleBlur={handleBlur}
                                handleChange={handleChange}
                                errors={errors}
                                values={values}
                                touched={touched}
                                setFieldValue={setFieldValue}
                            />
                        </div>
                        {/* F */}
                        <div className="box-heading">
                            <SpouseDetail
                                handleBlur={handleBlur}
                                handleChange={handleChange}
                                errors={errors}
                                values={values}
                                touched={touched}
                                setFieldValue={setFieldValue}
                            />
                        </div>
                        {/* G */}
                        <div className="box-heading">
                            <ChangeYear />
                        </div>
                        <div className="box-heading">
                            <TaxExemption
                                handleBlur={handleBlur}
                                handleChange={handleChange}
                                errors={errors}
                                values={values}
                                touched={touched}
                                setFieldValue={setFieldValue}
                            />
                        </div>
                        <div className="box-heading">
                            <h2>{t("form101.year_changes")}</h2>
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
                            />
                        </div>
                        <div className="box-heading">
                            <h2>{t("form101.text_disclaimer")}</h2>
                            <p>{t("form101.disclaimer_details1")}</p>
                            <p>{t("form101.disclaimer_details2")}</p>
                            <div>
                                {formValues && formValues.signature != null ? (
                                    <img src={formValues.signature} />
                                ) : (
                                    <div>
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
                                        <p>
                                            <button
                                                className="btn btn-warning mb-2"
                                                onClick={clearSignature}
                                            >
                                                {t("form101.button_clear")}
                                            </button>
                                        </p>
                                    </div>
                                )}
                                <div className="form-group">
                                    <DateField
                                        label={t("form101.Date")}
                                        name={"date"}
                                        required={true}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        value={values.date}
                                        error={touched.date && errors.date}
                                    />
                                </div>
                            </div>
                        </div>
                        <div className="box-heading">
                            <h2> {t("form101.sendTOYouAndEmployer")}</h2>

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
                        {!isSubmitted && (
                            <>
                                <button
                                    type="button"
                                    className="btn btn-primary"
                                    disabled={isSubmitting}
                                    onClick={() => {
                                        handleSaveAsDraft();
                                    }}
                                >
                                    {t("form101.save")}
                                </button>
                                <button
                                    type="submit"
                                    className="btn btn-success ml-2"
                                    disabled={isSubmitting}
                                >
                                    {t("form101.Accept")}
                                </button>
                            </>
                        )}
                    </form>
                </div>
            </div>
        </div>
    );
};

export default Form101Component;
