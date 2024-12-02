// IsrailContact.js

import React, { useEffect, useRef, useState } from "react";
import { Table } from "react-bootstrap";
import * as yup from "yup";
import { useFormik } from "formik";
import SignatureCanvas from "react-signature-canvas";
import moment from "moment";
import TextField from "../../../Pages/Form101/inputElements/TextField";
import DateField from "../../../Pages/Form101/inputElements/DateField";
import { useTranslation } from "react-i18next";
import { GrFormNextLink, GrFormPreviousLink } from "react-icons/gr";

export function IsrailContact({
    handleFormSubmit,
    workerDetail,
    workerFormDetails,
    isSubmitted,  // Passed in from parent
    isGeneratingPDF,
    contentRef,
    nextStep,
    setNextStep
}) {
    const sigRef = useRef();
    const { t } = useTranslation();
    const [formValues, setFormValues] = useState(null);
    const currentDate = moment().format("YYYY-MM-DD");

    const initialValues = {
        fullName: "",
        IdNumber: "",
        Address: "",
        startDate: "",
        signatureDate: currentDate,
        PhoneNo: "",
        MobileNo: "",
        signature: "",
        role: ""
    };

    const scrollToError = (errors) => {
        const errorFields = Object.keys(errors);
        if (errorFields.length > 0) {
            const firstErrorField = errorFields[0];
    
            const errorElement = document.getElementById(firstErrorField);
            if (errorElement) {
                const offset = 100;
                const elementPosition = errorElement.getBoundingClientRect().top + window.scrollY;
                const offsetPosition = elementPosition - offset;
    
                window.scrollTo({
                    top: offsetPosition,
                    behavior: "smooth",
                });
    
                // Focus the element after scrolling
                setTimeout(() => {
                    errorElement.focus();
                }, 500); 
            }
        }
    };
    

    const formSchema = {
        step5: yup.object({
            fullName: yup
                .string()
                .trim()
                .required(t("israilContract.errorMsg.FullName")),
            role: yup.string().trim().required(t("israilContract.errorMsg.Role")),
            IdNumber: yup
                .string()
                .trim()
                .required(t("israilContract.errorMsg.idRequired")),
            Address: yup
                .string()
                .trim()
                .required(t("israilContract.errorMsg.Address")),
            startDate: yup
                .date()
                .required(t("israilContract.errorMsg.StartDateOfJob")),
            PhoneNo: yup
                .string()
                .trim()
                .required(t("israilContract.errorMsg.Phone")),
        }),
        step6: yup.object({
            signatureDate: yup.date().required(t("israilContract.errorMsg.Date")),
            signature: yup
                .mixed()
                .required(t("israilContract.errorMsg.signRequired")),
        }),
    };

    const {
        errors,
        touched,
        handleBlur,
        handleChange,
        handleSubmit,
        values,
        setFieldValue,
        isSubmitting,
        validateForm,
    } = useFormik({
        initialValues: formValues ?? initialValues,
        enableReinitialize: true,
        validationSchema: formSchema[`step${nextStep}`],
        onSubmit: (values) => {
            handleFormSubmit(values);
        },
    });

    // if (isSubmitted) {
    //     setNextStep(prev => prev + 1)
    // }


    useEffect(() => {
        if (isSubmitted) {
            setFormValues(workerFormDetails);
            disableInputs();
        } else {
            setFieldValue(
                "fullName",
                workerDetail.firstname + " " + workerDetail.lastname
            );
            setFieldValue("IdNumber", workerDetail.worker_id);
            setFieldValue("Address", workerDetail.address);
            setFieldValue("MobileNo", workerDetail.phone);
            setFieldValue("role", workerDetail.role);
            setFieldValue("startDate", workerDetail.first_date);
    
        }


    }, [isSubmitted, workerFormDetails, workerDetail]);

    const disableInputs = () => {
        const inputs = document.querySelectorAll(".targetDiv input");
        inputs.forEach((input) => {
            input.disabled = true;
        });
    };

    const handleSignatureEnd = () => {
        setFieldValue("signature", sigRef.current.toDataURL());
    };
    const clearSignature = () => {
        sigRef.current.clear();
        setFieldValue("signature", "");
    };


    const handleNextPrev = async (e) => {
        e.preventDefault();

        // Validate the current step
        await validateForm(); // This will populate the errors object 
        scrollToError(errors);
        // Check if there are any errors
        if (!Object.keys(errors).length) {
            if (nextStep === 5) {
                // Move to Step 6 if Step 5 has no validation errors
                setNextStep(6);
            } else if (nextStep === 6) {
                // Submit the form if Step 6 has no validation errors
                handleSubmit();
            }
        } else {
            handleSubmit();
        }
    };

    return (
        <div className="mt-5 targetDiv rtlcon" ref={contentRef}>
            <div className="">
                <p className="navyblueColor font-30 mt-4 font-w-500">{t("israilContract.title1")}</p>
                <p className="mt-2">{t("israilContract.title2")}</p>
            </div>
            <form onSubmit={handleNextPrev}>
                {
                    nextStep === 5 && (
                        <div className="row">
                            <section className="col pl-0 pr-0">
                                <ol
                                    className="mt-5 lh-lg text-justify"
                                    style={{
                                        fontSize: "16px",
                                    }}
                                >
                                    <li>
                                        <strong>{t("israilContract.is1")}</strong>
                                        <div className="row gap-3">
                                            <div
                                                className={
                                                    "col-md-6 " +
                                                    (isGeneratingPDF ? "col-6" : "")
                                                }
                                            >
                                                <TextField
                                                    name={"fullName"}
                                                    onBlur={handleBlur}
                                                    onChange={handleChange}
                                                    label={t("israilContract.name")}
                                                    value={values.fullName}
                                                    required={true}
                                                    readonly={true}
                                                    error={
                                                        touched.fullName &&
                                                        errors.fullName
                                                    }
                                                />
                                            </div>
                                            <div
                                                className={
                                                    "col-md-6 " +
                                                    (isGeneratingPDF ? "col-6" : "")
                                                }
                                            >
                                                <TextField
                                                    name={"IdNumber"}
                                                    onBlur={handleBlur}
                                                    onChange={handleChange}
                                                    label={t(
                                                        "israilContract.IDNumber"
                                                    )}
                                                    value={values.IdNumber}
                                                    readonly={true}
                                                    error={
                                                        touched.IdNumber &&
                                                        errors.IdNumber
                                                    }
                                                />
                                            </div>
                                        </div>
                                        <TextField
                                            name={"Address"}
                                            onBlur={handleBlur}
                                            onChange={handleChange}
                                            label={t("israilContract.Address")}
                                            value={values.Address}
                                            readonly={true}
                                            error={
                                                touched.Address && errors.Address
                                            }
                                        />
                                    </li>
                                    <li>
                                        <DateField
                                            name={"startDate"}
                                            onBlur={handleBlur}
                                            onChange={handleChange}
                                            label={t(
                                                "israilContract.StartDateOfJob"
                                            )}
                                            value={values.startDate}
                                            error={
                                                touched.startDate &&
                                                errors.startDate
                                            }
                                            readOnly={values.startDate === null ? false : true}
                                        />
                                        <div className="row">
                                            <div
                                                className={
                                                    "col-md-6 " +
                                                    (isGeneratingPDF ? "col-6" : "")
                                                }
                                            >
                                                <TextField
                                                    name={"MobileNo"}
                                                    onBlur={handleBlur}
                                                    type="number"
                                                    onChange={handleChange}
                                                    label={t(
                                                        "israilContract.HomePhone"
                                                    )}
                                                    value={values.MobileNo}

                                                    // required={true}
                                                    readonly={true}
                                                    error={
                                                        touched.MobileNo &&
                                                        errors.MobileNo
                                                    }
                                                />
                                            </div>
                                            <div
                                                className={
                                                    "col-md-6 " +
                                                    (isGeneratingPDF ? "col-6" : "")
                                                }
                                            >
                                                <TextField
                                                    name={"PhoneNo"}
                                                    type="number"
                                                    onBlur={handleBlur}
                                                    onChange={handleChange}
                                                    label={t(
                                                        "israilContract.mobileNumber"
                                                    )}
                                                    value={values.PhoneNo}

                                                    required={true}
                                                    error={
                                                        touched.PhoneNo &&
                                                        errors.PhoneNo && "Mobile is required"
                                                    }
                                                />
                                            </div>
                                        </div>

                                        <p className="mb-2">
                                            <strong>
                                                {t("israilContract.is1NOte")}
                                            </strong>
                                        </p>
                                    </li>
                                    <li>
                                        <div className="mb-2">
                                            {t("israilContract.is3")}
                                            <TextField
                                                name={"role"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={t("israilContract.role")}
                                                value={values.role}
                                                required={true}
                                                error={touched.role && errors.role}
                                                readonly={true}
                                            />
                                        </div>
                                    </li>
                                    <li>
                                        <p className="mb-2">
                                            {t("israilContract.is4")}
                                        </p>
                                    </li>
                                    <li>
                                        {workerDetail?.is_existing_worker ? (
                                            <p className="mb-2">
                                                {t("israilContract.is5-3", {
                                                    payment_per_hour: workerDetail.payment_per_hour,
                                                })}
                                            </p>
                                        ) : (
                                            <>
                                                <p>
                                                    {t("israilContract.is5-1", {
                                                        payment_per_hour: workerDetail.payment_per_hour,
                                                    })}
                                                </p>
                                                <p
                                                    style={{
                                                        marginBottom: isGeneratingPDF
                                                            ? "16px"
                                                            : "16px",
                                                    }}
                                                >
                                                    {t("israilContract.is5-2")}
                                                </p>
                                            </>
                                        )}
                                    </li>

                                </ol>
                            </section>
                            <section className="col pl-0 pr-0">
                                <ol>
                                    <li>
                                        <p>{t("israilContract.is6")}</p>
                                        <Table
                                            bordered
                                            className="mt-3 mb-2"
                                            size="sm"
                                            responsive
                                        >
                                            <thead className="text-center">
                                                <tr>
                                                    <th colSpan={2}>
                                                        {t(
                                                            "israilContract.is6Table.paymentNotFixed"
                                                        )}
                                                    </th>
                                                    <th colSpan={2}>
                                                        {t(
                                                            "israilContract.is6Table.RegularPayments"
                                                        )}
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th>
                                                        {t(
                                                            "israilContract.is6Table.PaymentDate"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "israilContract.is6Table.PaymentType"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "israilContract.is6Table.PaymentDate"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "israilContract.is6Table.PaymentType"
                                                        )}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="text-left">
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr1.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "israilContract.is6Table.tr1.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "israilContract.is6Table.tr1.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr1.td4"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr2.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr2.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr2.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr2.td4"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr3.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr3.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr3.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr3.td4"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr4.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr4.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "israilContract.is6Table.tr4.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr4.td4"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr5.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr5.td2"
                                                        )}
                                                    </td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr6.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is6Table.tr6.td2"
                                                        )}
                                                    </td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                            </tbody>
                                        </Table>
                                    </li>
                                    <li>
                                        <p className="mb-2">
                                            {t("israilContract.is7")}
                                        </p>
                                    </li>
                                </ol>
                            </section>
                        </div>
                    )
                }
                {
                    nextStep === 6 && (
                        <div className="row">
                            <section className="col pl-0 pr-0">
                                <ol>
                                    <li>
                                        <p className="mb-2">
                                            {t("israilContract.is8")}
                                        </p>
                                    </li>
                                    <li>
                                        <p className="mb-2">
                                            {t("israilContract.is9")}
                                        </p>
                                    </li>
                                    <li>
                                        <p className="mb-2">
                                            {t("israilContract.is10-1")}
                                        </p>
                                        <p className="mb-2">
                                            {t("israilContract.is10-2")}
                                        </p>
                                    </li>
                                    <li>
                                        <p
                                            style={{
                                                marginBottom: isGeneratingPDF
                                                    ? "20px"
                                                    : "16px",
                                            }}
                                        >
                                            {t("israilContract.is11")}
                                        </p>
                                    </li>
                                    <li>
                                        <p className="mb-2">
                                            {t("israilContract.is12")}
                                        </p>
                                    </li>
                                    <li>
                                        <p>{t("israilContract.is13-1")}</p>
                                        <p>{t("israilContract.is13-2")}</p>
                                        <Table
                                            responsive
                                            bordered
                                            size="sm"
                                            className="mt-3"
                                        >
                                            <thead className="text-center">
                                                <tr>
                                                    <th>
                                                        {t(
                                                            "israilContract.is13Table.th.td1"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "israilContract.is13Table.th.td2"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "israilContract.is13Table.th.td3"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "israilContract.is13Table.th.td4"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "israilContract.is13Table.th.td5"
                                                        )}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="text-left">
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr1.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr1.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "israilContract.is13Table.tr1.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr1.td4"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "israilContract.is13Table.tr1.td5"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr2.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr2.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "israilContract.is13Table.tr2.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr2.td4"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr2.td5"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr3.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr3.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "israilContract.is13Table.tr3.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr3.td4"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr3.td5"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr4.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr4.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "israilContract.is13Table.tr4.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr4.td4"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "israilContract.is13Table.tr4.td5"
                                                        )}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </Table>
                                        <p
                                            style={{
                                                marginBottom: isGeneratingPDF
                                                    ? "16px"
                                                    : "16px",
                                            }}
                                        >
                                            {t("israilContract.is13-3")}
                                        </p>
                                    </li>
                                    <li>{t("israilContract.is14")}</li>
                                </ol>
                            </section>
                            <section className="col pl-0 pr-0">
                                <ol>
                                    <li>{t("israilContract.is14")}</li>
                                </ol>
                                <div className="d-flex mt-3 align-items-center" style={{ marginLeft: "40px", gap: "20px" }}>
                                    <div className="">
                                        <DateField
                                            name={"signatureDate"}
                                            onBlur={handleBlur}
                                            onChange={handleChange}
                                            label={t("israilContract.Date")}
                                            value={values.signatureDate}
                                            required={true}
                                            readOnly
                                            error={
                                                touched.signatureDate &&
                                                errors.signatureDate
                                            }
                                        />
                                    </div>
                                    <div className="d-flex align-items-center">
                                        <p className="mr-2">
                                            <strong>
                                                {t("israilContract.sign")}
                                            </strong>

                                        </p>
                                        {formValues && formValues.signature ? (
                                            <img src={formValues.signature} />
                                        ) : (
                                            <div className="d-flex flex-column">
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
                                                <span className="text-danger">
                                                    {touched.signature &&
                                                        errors.signature}
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                    {!isGeneratingPDF && (
                                        <div className="d-block">
                                            <button
                                                className="btn navyblue px-4 py-1 mt-5"
                                                type="button"
                                                onClick={clearSignature}
                                            >
                                                {t(
                                                    "israilContract.Clear"
                                                )}
                                            </button>
                                        </div>
                                    )}
                                </div>
                                <p className="text-center mt-4">
                                    (What is stated in the singular and/or masculine in this document is also feminine in meaning)
                                </p>
                                <div className="text-center mt-5">
                                    <p>{t("israilContract.BestRegards")}</p>
                                    <strong>{t("israilContract.Broom")}</strong>
                                </div>
                            </section>
                        </div>
                    )
                }

                {[5, 6].includes(nextStep) && (
                    <div className="d-flex justify-content-end">
                        {nextStep > 1 && (
                            <button
                                type="button"
                                onClick={() => setNextStep(prev => prev - 1)}
                                className="navyblue py-2 px-4 mr-2"
                                style={{ borderRadius: "5px" }}
                            >
                                <GrFormPreviousLink /> Prev
                            </button>
                        )}

                        <button
                            type="submit"
                            className="navyblue py-2 px-4"
                            style={{ borderRadius: "5px" }}
                        >
                            {nextStep === 6 && !isSubmitted ? "Submit" : "Next"} <GrFormNextLink />
                        </button>
                    </div>
                )}
            </form>
        </div>
    );
}
