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
    employeeDateOfAliyah: null,
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
        employerEmail: "",
    },
    signature: "",
};

const formSchema = yup.object({
    employerName: yup.string().trim(),
    employerAddress: yup.string().trim(),
    employerPhone: yup
        .string()
        .trim()
        .matches(/^\d{10}$/, "Invalid phone number"),
    employerDeductionsFileNo: yup
        .number()
        .typeError("Deductions file number must be a number"),
    employeeFirstName: yup.string().required("Name is required"),
    employeeLastName: yup.string().required("Name is required"),
    employeeIdentityType: yup.string().required("Identity type is required"),
    employeeIdNumber: yup.string().when("employeeIdentityType", {
        is: "IDNumber",
        then: () => yup.string().required("ID Number is required"),
    }),
    employeeIdCardCopy: yup.mixed().when("employeeIdentityType", {
        is: "IDNumber",
        then: () => yup.mixed().required("ID card copy is required"),
    }),
    employeecountry: yup.string().when("employeeIdentityType", {
        is: "Passport",
        then: () => yup.string().required("Country is required"),
    }),
    employeePassportNumber: yup.string().when("employeeIdentityType", {
        is: "Passport",
        then: () => yup.string().required("Passport number is required"),
    }),
    employeepassportCopy: yup.mixed().when("employeeIdentityType", {
        is: "Passport",
        then: () => yup.mixed().required("Passport copy is required"),
    }),
    employeeResidencePermit: yup.mixed(),
    employeeDob: yup.date().required("Date of birth is required"),
    employeeDateOfAliyah: yup.date().nullable(),
    employeeCity: yup.string().required("City is required"),
    employeeStreet: yup.string().required("Street is required"),
    employeeHouseNo: yup.string().required("House number is required"),
    employeePostalCode: yup.string().required("Postal Code is required"),
    employeeMobileNo: yup.string().required("Mobile number is required"),
    employeePhoneNo: yup.string(),
    employeeEmail: yup
        .string()
        .email("Invalid email")
        .required("Email is required"),
    employeeSex: yup.string().required("Sex is required"),
    employeeMaritalStatus: yup.string().required("Marital status is required"),
    employeeIsraeliResident: yup
        .string()
        .required("Israeli resident is required"),
    employeeCollectiveMoshavMember: yup
        .string()
        .required("Kibbutz / Collective moshav member is required"),
    employeeHealthFundMember: yup
        .string()
        .required("Health fund member is required"),
    employeeHealthFundname: yup.string().when("employeeHealthFundMember", {
        is: "Yes",
        then: () => yup.string().required("Health fund name is required"),
    }),
    employeemyIncomeToKibbutz: yup
        .string()
        .when("employeeCollectiveMoshavMember", {
            is: "Yes",
            then: () => yup.string().required("this field is required"),
        }),
    children: yup.array().of(
        yup.object().shape({
            firstName: yup.string().required("Name is required"),
            IdNumber: yup.string().required("Name is required"),
            Dob: yup.date().required("Date of birth is required"),
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
            "Please select one type of income"
        ),
    allowance: yup.boolean(),
    scholarship: yup.boolean(),
    DateOfBeginningWork: yup
        .date()
        .required("Date of beginning of work is required"),
    otherIncome: yup.object().shape({
        haveincome: yup.string().required(),
        incomeType: yup
            .array()
            .of(yup.string())
            .when("haveincome", {
                is: "Yes",
                then: () =>
                    yup
                        .array()
                        .of(yup.string())
                        .min(1, "Please select at least one type of income")
                        .required("Please select at least one type of income"),
            }),
        scholarship: yup.boolean(),
        taxCreditsAtOtherIncome: yup.string().when("haveincome", {
            is: "Yes",
            then: () =>
                yup
                    .string()
                    .required("Tax credits at other income is required"),
        }),
        studyFund: yup.boolean(),
        pensionInsurance: yup.boolean(),
    }),

    Spouse: yup.mixed().when("employeeMaritalStatus", {
        is: "Married",
        then: () =>
            yup.object().shape({
                firstName: yup.string().required("First Name is required"),
                lastName: yup.string().required("Last Name is required"),
                Identity: yup
                    .string()
                    .required("Please select an option")
                    .oneOf(["IDNumber", "Passport"], "Invalid option"),
                Country: yup.string().when("Identity", {
                    is: "Passport",
                    then: () => yup.string().required("Country is required"),
                }),
                passportNumber: yup.string().when("Identity", {
                    is: "Passport",
                    then: () =>
                        yup.string().required("Passport Number is required"),
                }),
                IdNumber: yup.string().when("Identity", {
                    is: "IDNumber",
                    then: () => yup.string().required("ID Number is required"),
                }),
                Dob: yup.date().required("Date of birth is required"),
                DateOFAliyah: yup.date(),
                hasIncome: yup.string().required("Income is required"),
                incomeType: yup.string().when("hasIncome", {
                    is: "Yes",
                    then: () =>
                        yup.string().required("Income Type is required"),
                }),
            }),
        otherwise: () => yup.mixed().nullable(),
    }),
    TaxExemption: yup.object().shape({
        isIsraelResident: yup.string(), // Add validation rules if needed
        disabled: yup.boolean(),
        disabledCertificate: yup.mixed().when("disabled", {
            is: true,
            then: () => yup.mixed().required("Certificate is required"),
            otherwise: () => yup.mixed(),
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
                            "Certificate for receiving monthly compensation is required"
                        ),
                otherwise: () => yup.mixed(),
            }),
        exm3: yup.boolean(),
        exm3Date: yup.date().when("exm3", {
            is: true,
            then: () => yup.date().required("From date is required"),
            otherwise: () => yup.date(),
        }),
        exm3Locality: yup.string().when("exm3", {
            is: true,
            then: () => yup.string().required("Locality is required"),
            otherwise: () => yup.string(),
        }),
        exm3Certificate: yup.mixed().when("exm3", {
            is: true,
            then: () => yup.mixed().required("Certificate is required"),
            otherwise: () => yup.mixed(),
        }),
        exm4: yup.boolean(),
        exm4FromDate: yup.date().when("TaxExemption.exm4", {
            is: true,
            then: () => yup.date().required("From date is required for exm4"),
            otherwise: () => yup.date(),
        }),
        exm4ImmigrationCertificate: yup.mixed().when("TaxExemption.exm4", {
            is: true,
            then: () =>
                yup
                    .mixed()
                    .required("Immigration certificate is required for exm4"),
            otherwise: () => yup.mixed(),
        }),
        exm4NoIncomeDate: yup.date().when("exm4", {
            is: true,
            then: () =>
                yup.date().required("No income date is required for exm4"),
            otherwise: () => yup.date(),
        }),

        exm5: yup.boolean(),
        exm5disabledCirtificate: yup.mixed().when("exm5", {
            is: true,
            then: () =>
                yup
                    .mixed()
                    .required(
                        "Disabled or blind certificate is required for exm5"
                    ),
            otherwise: () => yup.mixed(),
        }),

        exm6: yup.boolean(),
        exm7: yup.boolean(),
        exm7NoOfChild: yup.number().when(["exm7", "children"], {
            is: (exm7, children) =>
                exm7 && Array.isArray(children) && children.length > 0,
            then: () =>
                yup
                    .number()
                    .required(
                        "Number of children born in the tax year is required for exm7"
                    ),
            otherwise: () => yup.number(),
        }),
        exm7NoOfChild1to5: yup.number().when(["exm7", "children"], {
            is: (exm7, children) =>
                exm7 && Array.isArray(children) && children.length > 0,
            then: () =>
                yup
                    .number()
                    .required(
                        "Number of children between the ages of 1 and 5 in the tax year is required for exm7"
                    ),
            otherwise: () => yup.number(),
        }),
        exm7NoOfChild6to17: yup.number().when(["exm7", "children"], {
            is: (exm7, children) =>
                exm7 && Array.isArray(children) && children.length > 0,
            then: () =>
                yup
                    .number()
                    .required(
                        "Number of children between the ages of 6 and 17 in the tax year is required for exm7"
                    ),
            otherwise: () => yup.number(),
        }),
        exm7NoOfChild18: yup.number().when(["exm7", "children"], {
            is: (exm7, children) =>
                exm7 && Array.isArray(children) && children.length > 0,
            then: () =>
                yup
                    .number()
                    .required(
                        "Number of children or who turn 18 years old in the tax year is required for exm7"
                    ),
            otherwise: () => yup.number(),
        }),

        exm8: yup.boolean(),
        exm8NoOfChild: yup.number().when(["exm8", "children"], {
            is: (exm8, children) =>
                exm8 && Array.isArray(children) && children.length > 0,
            then: () =>
                yup
                    .number()
                    .required(
                        "Number of children born in the tax year is required for exm8"
                    ),
            otherwise: () => yup.number(),
        }),
        exm8NoOfChild1to5: yup.number().when(["exm8", "children"], {
            is: (exm8, children) =>
                exm8 && Array.isArray(children) && children.length > 0,
            then: () =>
                yup
                    .number()
                    .required(
                        "Number of children between the ages of 1 and 5 in the tax year is required for exm8"
                    ),
            otherwise: () => yup.number(),
        }),
        exm8NoOfChild6to17: yup.number().when(["exm8", "children"], {
            is: (exm8, children) =>
                exm8 && Array.isArray(children) && children.length > 0,
            then: () =>
                yup
                    .number()
                    .required(
                        "Number of children between the ages of 6 and 17 in the tax year is required for exm8"
                    ),
            otherwise: () => yup.number(),
        }),

        exm9: yup.boolean(),
        // No need to specify validation for exm9

        exm10: yup.boolean(),
        exm10Certificate: yup.mixed().when("exm10", {
            is: true,
            then: () =>
                yup
                    .mixed()
                    .required(
                        "Photocopy of a court order for child support is required for exm10"
                    ),
            otherwise: () => yup.mixed(),
        }),
        exm11: yup.boolean(),
        exm11NoOfChildWithDisibility: yup.number().when("exm11", {
            is: true,
            then: () =>
                yup
                    .number()
                    .required(
                        "Number of children with disability is required for exm11"
                    ),
            otherwise: () => yup.number(),
        }),
        exm11Certificate: yup.mixed().when("exm11", {
            is: true,
            then: () =>
                yup
                    .mixed()
                    .required(
                        "Children's disability benefit certificate is required for exm11"
                    ),
            otherwise: () => yup.mixed(),
        }),

        exm12: yup.boolean(),
        exm12Certificate: yup.mixed().when("exm12", {
            is: true,
            then: () =>
                yup
                    .mixed()
                    .required(
                        "Photocopy of a court order for alimony is required for exm12"
                    ),
            otherwise: () => yup.mixed(),
        }),

        exm13: yup.boolean(),
        // No need to specify validation for exm13

        exm14: yup.boolean(),
        exm14BeginingDate: yup.date().when("exm14", {
            is: true,
            then: () =>
                yup
                    .date()
                    .required(
                        "Date of beginning of service is required for exm14"
                    ),
            otherwise: () => yup.date(),
        }),
        exm14EndDate: yup.date().when("exm14", {
            is: true,
            then: () =>
                yup
                    .date()
                    .required("Date of end of service is required for exm14"),
            otherwise: () => yup.date(),
        }),
        exm14Certificate: yup.mixed().when("exm14", {
            is: true,
            then: () =>
                yup
                    .mixed()
                    .required(
                        "Discharge/end of service certificate is required for exm14"
                    ),
            otherwise: () => yup.mixed(),
        }),

        exm15: yup.boolean(),
        exm15Certificate: yup.mixed().when("exm15", {
            is: true,
            then: () =>
                yup
                    .mixed()
                    .required("Declaration in Form 119 is required for exm15"),
            otherwise: () => yup.mixed(),
        }),
    }),

    TaxCoordination: yup.object().shape({
        hasTaxCoordination: yup.boolean(),
        requestReason: yup.string().when("hasTaxCoordination", {
            is: true,
            then: () =>
                yup
                    .string()
                    .required(
                        "Reason for tax coordination request is required"
                    ),
        }),
        requestReason1Certificate: yup
            .mixed()
            .nullable()
            .when("requestReason", {
                is: "reason1",
                then: () =>
                    yup
                        .mixed()
                        .required(
                            "Proofs for lack of previous incomes is required"
                        ),
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
                            "Tax coordination certificate from the assessing officer is required"
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
                                .required("First Name is required"),
                            address: yup
                                .string()
                                .required("Address is required"),
                            fileNumber: yup
                                .string()
                                .required("Deductions file number is required"),
                            MonthlyIncome: yup
                                .number()
                                .required("Monthly income is required"),
                            Tax: yup
                                .number()
                                .required("Tax deducted is required"),
                            incomeType: yup
                                .string()
                                .required("Type of income is required"),
                            payslip: yup
                                .mixed()
                                .required("Photocopy of payslip is required"),
                        })
                    )
                    .required(
                        "At least one employer/payer of salary is required"
                    ),
        }),
    }),
    date: yup.date().required("Date is required"),
    sender: yup.object().shape({
        employeeEmail: yup
            .string()
            .email()
            .required("Employee email is required"),
        employerEmail: yup
            .string()
            .email()
            .required("Employer email is required"),
    }),
    signature: yup.mixed().required(),
});
const Form101Component = () => {
    const sigRef = useRef();
    const { t } = useTranslation();
    const alert = useAlert();
    const param = useParams();
    const id = Base64.decode(param.id);
    const [formValues, setFormValues] = useState(null);
    const {
        values,
        touched,
        errors,
        handleBlur,
        handleChange,
        handleSubmit,
        setFieldValue,
    } = useFormik({
        initialValues: formValues ?? initialValues,
        enableReinitialize: true,
        validationSchema: formSchema,
        onSubmit: (values) => {
            axios
                .post(`/api/form101`, { id: id, data: values })
                .then((res) => {
                    alert.success("Successfuly signed");
                    setTimeout(() => {
                        window.location.reload(true);
                    }, 2000);
                })
                .catch((e) => {
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
            if (res.data.form) {
                setFormValues(res.data.form);
                disableInputs();
            }
        });
    };

    // const printPdf = (e) => {
    //     window.location.href = `/pdf/${param.id}`;
    // };

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
                <hr />
                <div className="agg-list">
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
                </div>
                <div className="box-heading">
                    <h2>{t("form101.texYearTitle")}</h2>
                    <p>
                        <strong>{t("form101.year_2023")}</strong>{" "}
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
                                                className: "sign101",
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
                                        label={"Date"}
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
                            <h2>Send the form to you and the employer</h2>

                            <TextField
                                name="sender.employerEmail"
                                label="Employer's email address"
                                value={values.sender.employerEmail}
                                onChange={handleChange}
                                onBlur={handleBlur}
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
                                label="Employee's email address"
                                value={values.sender.employeeEmail}
                                onChange={handleChange}
                                onBlur={handleBlur}
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
                        {!formValues && (
                            <button type="submit" className="btn btn-success">
                                submit
                            </button>
                        )}
                    </form>
                </div>
            </div>
        </div>
    );
};

export default Form101Component;
