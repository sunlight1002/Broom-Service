//nonIsraeliContract

import React, { useEffect, useRef, useState } from "react";
import { Table } from "react-bootstrap";
import * as yup from "yup";
import { useFormik } from "formik";
import SignatureCanvas from "react-signature-canvas";
import moment from "moment";
import companySign from '../../../Assets/image/company-sign.png'
import TextField from "../../../Pages/Form101/inputElements/TextField";
import DateField from "../../../Pages/Form101/inputElements/DateField";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { GrFormNextLink, GrFormPreviousLink } from "react-icons/gr";
import useWindowWidth from "../../../Hooks/useWindowWidth";

export function NonIsraeliContract({
    handleFormSubmit,
    workerDetail,
    workerFormDetails,
    isSubmitted,
    isGeneratingPDF,
    contentRef,
    nextStep,
    setNextStep,
    savingType,
    setSavingType
}) {

    const sigRef1 = useRef();
    const sigRef2 = useRef();
    const sigRef3 = useRef();
    const sigRef4 = useRef();
    const companySigRef1 = useRef();
    const companySigRef2 = useRef();
    const { t } = useTranslation();
    const [formValues, setFormValues] = useState(null);
    const windowWidth = useWindowWidth();
    const [mobileView, setMobileView] = useState(false);
    const currentDate = moment().format("YYYY-MM-DD");
    const [tempSig1, setTempSig1] = useState(null);

    const initialValues = {
        fullName: "",
        passport: "",
        Address: "",
        startDate: "",
        signatureDate1: currentDate,
        signatureDate2: currentDate,
        signatureDate3: currentDate,
        signatureDate4: currentDate,
        signature1: "",
        signature2: "",
        signature3: "",
        signature4: "",
        companySignature1: companySign,
        companySignature2: companySign,
        role: "",
    };


    useEffect(() => {
        if (windowWidth < 767) {
            setMobileView(true)
        } else {
            setMobileView(false)
        }
    }, [windowWidth])

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

    const formSchema = {
        step5: yup.object({
            fullName: yup
                .string()
                .trim()
                .required(t("nonIsrailContract.errorMsg.FullName")),
            passport: yup
                .string()
                .trim()
                .required(t("nonIsrailContract.errorMsg.passportNumReq")),
            startDate: yup
                .date()
                .required(t("nonIsrailContract.errorMsg.startDate")),
            signatureDate1: yup
                .date()
                .required(t("nonIsrailContract.errorMsg.Date")),
            signature1: yup.mixed().required(t("nonIsrailContract.errorMsg.sign")),

        }),
        step6: yup.object({
            Address: yup
                .string()
                .trim()
                .required(t("nonIsrailContract.errorMsg.address")),
            signatureDate2: yup
                .date()
                .required(t("nonIsrailContract.errorMsg.Date")),
            signatureDate3: yup
                .date()
                .required(t("nonIsrailContract.errorMsg.Date")),
            signatureDate4: yup
                .date()
                .required(t("nonIsrailContract.errorMsg.Date")),
            signature2: yup.mixed().required(t("nonIsrailContract.errorMsg.sign")),
            signature3: yup.mixed().required(t("nonIsrailContract.errorMsg.sign")),
            signature4: yup.mixed().required(t("nonIsrailContract.errorMsg.sign")),
        })
    }

    const {
        errors,
        touched,
        handleBlur,
        handleChange,
        handleSubmit,
        values,
        setFieldValue,
        isSubmitting,
        validateForm
    } = useFormik({
        initialValues: formValues ?? initialValues,
        enableReinitialize: true,
        validationSchema: formSchema[`step${nextStep}`], // Use dynamic schema based on current step
        onSubmit: (values) => {
            handleFormSubmit(values);
        },
    });


    useEffect(() => {
        if (isSubmitted) {
            setFormValues(workerFormDetails);
            disableInputs();
        } else {
            setFieldValue(
                "fullName",
                workerDetail.firstname + " " + workerDetail.lastname
            );
            setFieldValue("Address", workerDetail.address);
            setFieldValue("role", workerDetail.role);
            // setFieldValue("IdNumber", workerDetail.worker_id);
            setFieldValue("passport", workerDetail.passport);
            setFieldValue("startDate", workerDetail.first_date);
            setFieldValue("signature1", sigRef1?.current?.toDataURL());

        }
    }, [isSubmitted, workerFormDetails, workerDetail]);

    const disableInputs = () => {
        const inputs = document.querySelectorAll(".contracttargetDiv input");
        inputs.forEach((input) => {
            input.disabled = true;
        });
    };

    const handleSignatureEnd1 = () => {
        setFieldValue("signature1", sigRef1.current.toDataURL());
        setTempSig1(sigRef1?.current?.toDataURL());
    };
    // Clear the signature canvas
    const clearSignature1 = () => {
        // if (sigRef1.current) {
        setTempSig1(null);
        sigRef1?.current?.clear();
        // }
    };
    const handleSignatureEnd2 = () => {
        setFieldValue("signature2", sigRef2.current.toDataURL());
    };
    const clearSignature2 = () => {
        sigRef2.current.clear();
        setFieldValue("signature2", "");
    };
    const handleSignatureEnd3 = () => {
        setFieldValue("signature3", sigRef3.current.toDataURL());
    };
    const clearSignature3 = () => {
        sigRef3.current.clear();
        setFieldValue("signature3", "");
    };
    const handleSignatureEnd4 = () => {
        setFieldValue("signature4", sigRef4.current.toDataURL());
    };
    const clearSignature4 = () => {
        sigRef4.current.clear();
        setFieldValue("signature4", "");
    };
    const handleCompanySignatureEnd1 = () => {
        setFieldValue("companySignature1", companySigRef1.current.toDataURL());
    };
    const clearCompanySignature1 = () => {
        companySigRef1.current.clear();
        setFieldValue("companySignature1", "");
    };
    const handleCompanySignatureEnd2 = () => {
        setFieldValue("companySignature2", companySigRef2.current.toDataURL());
    };
    const clearCompanySignature2 = () => {
        companySigRef2.current.clear();
        setFieldValue("companySignature2", "");
    };

    const handleNextPrev = async (e) => {
        e.preventDefault();

        // Validate the current step
        const validationErrors = await validateForm(); // This will populate the `errors` object

        const pageSevenExists = (workerDetail.country !== "Israel" && workerDetail.is_existing_worker !== 1);

        // If there are no validation errors
        if (!Object.keys(validationErrors).length) {
            if (nextStep === 5) {
                setSavingType("draft");
                setNextStep(6);
                // handleSubmit();
            } else if (nextStep === 6) {
                setSavingType("submit");
                handleSubmit();
            }
        } else {
            // Scroll to error if validation fails
            scrollToError(validationErrors);
            handleSubmit();
        }
    };


    return (
        <div className="mt-5 contracttargetDiv pdf-wrapper" ref={contentRef}>
            <div className="">
                <p className="navyblueColor font-30 mt-4 font-w-500">{t("nonIsrailContract.title1")}</p>
                <p className="mt-2">{t("nonIsrailContract.title2")}</p>
            </div>
            <form onSubmit={handleNextPrev}>
                {
                    ((isGeneratingPDF ? nextStep === 6 : nextStep === 5) || !nextStep) && (
                        <div className="row">
                            <section className={`${isGeneratingPDF ? "col-12" : "col"} px-3`}>
                                <ol
                                    className="mt-5 lh-lg text-justify"
                                    style={{ fontSize: "16px" }}
                                >
                                    <li>
                                        <strong>
                                            {t("nonIsrailContract.nic1")}
                                        </strong>
                                        <div className="row gap-3">
                                            <div className="col-sm">
                                                <TextField
                                                    name={"fullName"}
                                                    onBlur={handleBlur}
                                                    onChange={handleChange}
                                                    label={t(
                                                        "nonIsrailContract.empName"
                                                    )}
                                                    value={values.fullName}
                                                    required={true}
                                                    readonly={true}
                                                    error={
                                                        touched.fullName &&
                                                        errors.fullName
                                                    }
                                                />
                                            </div>
                                            <div className="col-sm">
                                                <TextField
                                                    name={"passport"}
                                                    onBlur={handleBlur}
                                                    onChange={handleChange}
                                                    label={t(
                                                        "nonIsrailContract.passport"
                                                    )}
                                                    value={values.passport}
                                                    required={true}
                                                    error={
                                                        touched.IdNumber &&
                                                        errors.passport
                                                    }
                                                    readonly={workerDetail.passport === null ? false : true}
                                                />
                                            </div>
                                        </div>
                                        <TextField
                                            name={"Address"}
                                            onBlur={handleBlur}
                                            onChange={handleChange}
                                            label={t("nonIsrailContract.Address")}
                                            value={values.Address}
                                            readonly={true}
                                            required={true}
                                            error={
                                                touched.Address && errors.Address
                                            }
                                        />
                                    </li>
                                    <li>
                                        {
                                            isGeneratingPDF ? (
                                                <div className="form-group mb-4">
                                                    <label className="control-label">
                                                        {t(
                                                            "nonIsrailContract.dateStart"
                                                        )}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        name="startDate"
                                                        value={values.startDate}
                                                    />
                                                </div>
                                            ) : (
                                                <DateField
                                                    name={"startDate"}
                                                    onBlur={handleBlur}
                                                    onChange={handleChange}
                                                    label={t("nonIsrailContract.dateStart")}
                                                    value={values.startDate}
                                                    required={true}
                                                    error={
                                                        touched.startDate &&
                                                        errors.startDate
                                                    }
                                                />
                                            )
                                        }
                                        <p className="mb-2">
                                            {t("nonIsrailContract.nic2")}
                                        </p>
                                    </li>
                                    <li>
                                        {t("nonIsrailContract.nic3-1")}
                                        <TextField
                                            name={"role"}
                                            onBlur={handleBlur}
                                            onChange={handleChange}
                                            label={t("nonIsrailContract.role")}
                                            value={values.role}
                                            required={true}
                                            error={touched.role && errors.role}
                                            readonly={true}
                                        />
                                        <p className="mb-2">
                                            {t("nonIsrailContract.nic3-2")}
                                        </p>
                                    </li>
                                    <li>
                                        <p className="mb-2">
                                            {t("nonIsrailContract.nic4")}
                                        </p>
                                    </li>

                                </ol>

                            </section>
                            <section className={`${isGeneratingPDF ? "col-12" : "col"} px-3`}>
                                <ol start="5" className="lh-lg text-justify" style={{ fontSize: "16px" }}>
                                    <li>
                                        {workerDetail?.is_existing_worker ? (
                                            <p className="mb-2">
                                                {t("nonIsrailContract.nic5_new", {
                                                    payment_per_hour:
                                                        workerDetail.payment_per_hour,
                                                })}
                                            </p>
                                        ) : (
                                            <p className="mb-2">
                                                {t("nonIsrailContract.nic5", {
                                                    payment_per_hour:
                                                        workerDetail.payment_per_hour,
                                                })}
                                            </p>
                                        )}
                                        <>
                                            {workerDetail?.is_existing_worker ? "" : (
                                                <p className="mb-2">
                                                    {"* "}{t(
                                                        "nonIsrailContract.nic5_sub.nic5_sub1"
                                                    )}
                                                </p>
                                            )}
                                            <div className={`d-flex ${mobileView ? "flex-column" : "align-items-center"}`}>
                                                <div
                                                    className={
                                                        " " +
                                                        (isGeneratingPDF
                                                            ? "col-3"
                                                            : "")
                                                    }
                                                >
                                                    {
                                                        isGeneratingPDF ? (
                                                            <div className="form-group mb-4">
                                                                <label className="control-label">
                                                                    {t(
                                                                        "nonIsrailContract.nic5_sub.Date"
                                                                    )}
                                                                </label>
                                                                <input
                                                                    type="text"
                                                                    className="form-control"
                                                                    name="signatureDate1"
                                                                    value={values.signatureDate1}
                                                                />
                                                            </div>
                                                        ) : (
                                                            <DateField
                                                                name={"signatureDate1"}
                                                                onBlur={handleBlur}
                                                                onChange={handleChange}
                                                                label={t(
                                                                    "nonIsrailContract.nic5_sub.Date"
                                                                )}
                                                                value={
                                                                    values.signatureDate1
                                                                }
                                                                required={true}
                                                                readOnly
                                                                error={
                                                                    touched.signatureDate1 &&
                                                                    errors.signatureDate1
                                                                }
                                                            />
                                                        )
                                                    }
                                                </div>
                                                <div
                                                    className={
                                                        "" +
                                                        (isGeneratingPDF
                                                            ? "col-4"
                                                            : "")
                                                    }
                                                    style={{
                                                        marginLeft: mobileView ? "0px" : "180px"
                                                    }}
                                                >
                                                    {workerDetail?.is_existing_worker ? "" : (
                                                        <p>
                                                            <strong>
                                                                {t(
                                                                    "nonIsrailContract.nic5_sub.nic5_sub2"
                                                                )}
                                                            </strong>
                                                        </p>
                                                    )}
                                                    {(formValues || tempSig1) &&
                                                        (formValues?.signature1 || tempSig1) ? (
                                                        <img
                                                            src={
                                                                formValues?.signature1 || tempSig1
                                                            }
                                                        />
                                                    ) : (
                                                        <div className="d-flex">
                                                            <div>
                                                                <SignatureCanvas
                                                                    penColor="black"
                                                                    canvasProps={{
                                                                        width: 250,
                                                                        height: 100,
                                                                        className:
                                                                            "sign101 border mt-1",
                                                                    }}
                                                                    ref={sigRef1}
                                                                    onEnd={
                                                                        handleSignatureEnd1
                                                                    }
                                                                />
                                                                {touched.signature1 &&
                                                                    errors.signature1 && (
                                                                        <p className="text-danger">
                                                                            {touched.signature1 &&
                                                                                errors.signature1}
                                                                        </p>
                                                                    )}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {tempSig1 && !isGeneratingPDF && (
                                                        <div className="d-block align-content-end">
                                                            <button
                                                                type="button"
                                                                className="btn navyblue px-3 py-1 ml-2 mb-2"
                                                                onClick={
                                                                    clearSignature1
                                                                }
                                                            >
                                                                {t(
                                                                    "nonIsrailContract.nic5_sub.clear"
                                                                )}
                                                            </button>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </>
                                        <Table
                                            bordered
                                            className="mt-5"
                                            size="sm"
                                            responsive
                                        >
                                            <thead className="text-center">
                                                <tr>
                                                    <th colSpan={2}>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.th.th1"
                                                        )}
                                                    </th>
                                                    <th colSpan={2}>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.th.th2"
                                                        )}
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.th.th3"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.th.th4"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.th.th3"
                                                        )}
                                                    </th>
                                                    <th>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.th.th4"
                                                        )}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="text-left">
                                                <tr>
                                                    <td>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr1.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr1.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr1.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr1.td4"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr2.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr2.td2"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr2.td3"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr2.td4"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td></td>
                                                    <td>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr3.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr3.td2"
                                                        )}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td></td>
                                                    <td>
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr4.td1"
                                                        )}
                                                    </td>
                                                    <td>
                                                        {" "}
                                                        {t(
                                                            "nonIsrailContract.nic5_sub.table.tr4.td2"
                                                        )}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </Table>
                                    </li>
                                </ol>
                            </section>
                        </div>
                    )
                }
                {
                    (nextStep === 6 || !nextStep) && (
                        <div className="row mt-4">
                            <section className="col">
                                <p className="">
                                    {t(
                                        "nonIsrailContract.nic5_sub.nic5_sub3"
                                    )}
                                </p>
                                <p className="mt-2">
                                    {t(
                                        "nonIsrailContract.nic5_sub.nic5_sub4"
                                    )}
                                </p>
                                <p className="mt-2">
                                    {t(
                                        "nonIsrailContract.nic5_sub.nic5_sub5"
                                    )}
                                </p>
                                <div className="d-flex justify-content-between mt-3">
                                    <div
                                        className={
                                            "" +
                                            (isGeneratingPDF ? "col-3" : "")
                                        }
                                    >
                                        {
                                            isGeneratingPDF ? (
                                                <div className="form-group mb-4">
                                                    <label className="control-label">
                                                        {t(
                                                            "nonIsrailContract.date"
                                                        )}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        name="signatureDate2"
                                                        value={values.signatureDate2}
                                                    />
                                                </div>
                                            ) : (
                                                <DateField
                                                    name={"signatureDate2"}
                                                    onBlur={handleBlur}
                                                    onChange={handleChange}
                                                    label={t(
                                                        "nonIsrailContract.date"
                                                    )}
                                                    value={values.signatureDate2}
                                                    required={true}
                                                    readOnly
                                                    error={
                                                        touched.signatureDate2 &&
                                                        errors.signatureDate2
                                                    }
                                                />
                                            )
                                        }
                                    </div>
                                    <div
                                        className={
                                            "d-flex align-items-end flex-column" +
                                            (isGeneratingPDF ? "col-4" : "")
                                        }
                                    >
                                        <p>
                                            <strong>
                                                {t(
                                                    "nonIsrailContract.workerSign"
                                                )}
                                            </strong>
                                        </p>
                                        {/* {formValues &&
                                            formValues.signature2 ? (
                                            <img
                                                src={formValues.signature2}
                                            />
                                        ) : (
                                            <div id="signature2">
                                                <SignatureCanvas
                                                    penColor="black"
                                                    canvasProps={{
                                                        width: 250,
                                                        height: 100,
                                                        className:
                                                            "sign101 border mt-1",
                                                    }}
                                                    ref={sigRef2}
                                                    onEnd={
                                                        handleSignatureEnd2
                                                    }
                                                />
                                                {touched.signature2 &&
                                                    errors.signature2 && (
                                                        <p className="text-danger">
                                                            {touched.signature2 &&
                                                                errors.signature2}
                                                        </p>
                                                    )}

                                                {!isGeneratingPDF && (
                                                    <div className="d-block">
                                                        <button
                                                            type="button"
                                                            className="btn navyblue px-3 py-1 my-2"
                                                            onClick={
                                                                clearSignature2
                                                            }
                                                        >
                                                            {t(
                                                                "nonIsrailContract.clear"
                                                            )}
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        )} */}

                                        {
                                            isGeneratingPDF ? (
                                                sigRef2 ? (
                                                    <img src={sigRef2?.current?.toDataURL()} alt="Signature" />
                                                ) : null
                                            ) : (
                                                formValues?.signature2 ? (
                                                    <img
                                                        src={formValues.signature2}
                                                    />
                                                ) : (
                                                    <div id="signature2">
                                                        <SignatureCanvas
                                                            penColor="black"
                                                            canvasProps={{
                                                                width: 250,
                                                                height: 100,
                                                                className:
                                                                    "sign101 border mt-1",
                                                            }}
                                                            ref={sigRef2}
                                                            onEnd={
                                                                handleSignatureEnd2
                                                            }
                                                        />
                                                        {touched.signature2 &&
                                                            errors.signature2 && (
                                                                <p className="text-danger">
                                                                    {touched.signature2 &&
                                                                        errors.signature2}
                                                                </p>
                                                            )}

                                                        {!isGeneratingPDF && (
                                                            <div className="d-block">
                                                                <button
                                                                    type="button"
                                                                    className="btn navyblue px-3 py-1 my-2"
                                                                    onClick={
                                                                        clearSignature2
                                                                    }
                                                                >
                                                                    {t(
                                                                        "nonIsrailContract.clear"
                                                                    )}
                                                                </button>
                                                            </div>
                                                        )}
                                                    </div>
                                                )
                                            )
                                        }
                                    </div>
                                </div>
                                <ol start={6} className="lh-lg text-justify pl-0" style={{ fontSize: "16px" }}>
                                    <li>
                                        <p className="mb-2">
                                            {t("nonIsrailContract.nic6")}
                                        </p>
                                    </li>
                                    <li>
                                        <p className="mb-2">
                                            {t("nonIsrailContract.nic7")}
                                        </p>
                                    </li>
                                    <li>
                                        <p className="mb-2">
                                            {t("nonIsrailContract.nic8")}
                                        </p>
                                    </li>
                                </ol>


                                <Table
                                    bordered
                                    size="sm"
                                    className="mt-3"
                                    responsive
                                >
                                    <thead className="text-center">
                                        <tr>
                                            <th>
                                                {" "}
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.th.th1"
                                                )}
                                            </th>
                                            <th>
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.th.th2"
                                                )}
                                            </th>
                                            <th>
                                                {" "}
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.th.th3"
                                                )}
                                            </th>
                                            <th>
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.th.th4"
                                                )}
                                            </th>
                                            <th>
                                                {" "}
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.th.th5"
                                                )}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="text-left">
                                        <tr>
                                            <td>
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr1.td1"
                                                )}
                                            </td>
                                            <td>
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr1.td2"
                                                )}
                                            </td>
                                            <td>
                                                {" "}
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr1.td3"
                                                )}
                                            </td>
                                            <td>
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr1.td4"
                                                )}
                                            </td>
                                            <td>
                                                {" "}
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr1.td5"
                                                )}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr2.td1"
                                                )}
                                            </td>
                                            <td>
                                                {" "}
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr2.td2"
                                                )}
                                            </td>
                                            <td>
                                                {" "}
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr2.td3"
                                                )}
                                            </td>
                                            <td>
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr2.td4"
                                                )}
                                            </td>
                                            <td>
                                                {" "}
                                                {t(
                                                    "nonIsrailContract.nic8Sub.table.tr2.td5"
                                                )}
                                            </td>
                                        </tr>
                                    </tbody>
                                </Table>
                                <p>
                                    {t(
                                        "nonIsrailContract.nic8Sub.nic8Sub_1"
                                    )}
                                </p>
                                <p>
                                    {t(
                                        "nonIsrailContract.nic8Sub.nic8Sub_2"
                                    )}
                                </p>
                                <p>
                                    {t(
                                        "nonIsrailContract.nic8Sub.nic8Sub_3"
                                    )}
                                </p>
                                <div className={`d-flex flex-wrap ${mobileView ? "justify-content-center" : "justify-content-between"}  mt-3 gap-3`}>
                                    <div
                                        className={
                                            " " +
                                            (isGeneratingPDF ? "col-6" : "")
                                        }
                                    >
                                        <p>
                                            <strong>
                                                {t(
                                                    "nonIsrailContract.nic8Sub.nic8Sub_6"
                                                )}
                                            </strong>
                                        </p>
                                        {/* {initialValues.companySignature2 &&
                                            initialValues.companySignature2 ? (
                                            <img
                                                src={initialValues.companySignature1}
                                            />
                                        ) : (
                                            <>
                                                <SignatureCanvas
                                                    id="signature3"

                                                    penColor="black"
                                                    canvasProps={{
                                                        width: 250,
                                                        height: 100,
                                                        className:
                                                            "sign101 border mt-1",
                                                    }}
                                                    ref={companySigRef1}
                                                    onEnd={
                                                        handleCompanySignatureEnd1
                                                    }
                                                />
                                                {touched.companySignature1 &&
                                                    errors.companySignature1 && (
                                                        <p className="text-danger">
                                                            {touched.companySignature1 &&
                                                                errors.companySignature1}
                                                        </p>
                                                    )}

                                                {!isGeneratingPDF && (
                                                    <div className="d-block">
                                                        <button
                                                            type="button"
                                                            className="btn navyblue px-3 py-1 mb-2"
                                                            onClick={
                                                                clearCompanySignature1
                                                            }
                                                        >
                                                            {t(
                                                                "nonIsrailContract.clear"
                                                            )}
                                                        </button>
                                                    </div>
                                                )}
                                            </>
                                        )} */}

                                        {
                                            isGeneratingPDF ? (
                                                initialValues.companySignature2 &&
                                                    initialValues.companySignature2 ? (
                                                    <img className="company-sign" src={initialValues.companySignature1} alt="Signature" />
                                                ) : null
                                            ) : (
                                                initialValues.companySignature2 &&
                                                    initialValues.companySignature2 ? (
                                                    <img
                                                        src={initialValues.companySignature1}
                                                    />
                                                ) : (
                                                    <>
                                                        <SignatureCanvas
                                                            id="signature3"

                                                            penColor="black"
                                                            canvasProps={{
                                                                width: 250,
                                                                height: 100,
                                                                className:
                                                                    "sign101 border mt-1",
                                                            }}
                                                            ref={companySigRef1}
                                                            onEnd={
                                                                handleCompanySignatureEnd1
                                                            }
                                                        />
                                                        {touched.companySignature1 &&
                                                            errors.companySignature1 && (
                                                                <p className="text-danger">
                                                                    {touched.companySignature1 &&
                                                                        errors.companySignature1}
                                                                </p>
                                                            )}

                                                        {!isGeneratingPDF && (
                                                            <div className="d-block">
                                                                <button
                                                                    type="button"
                                                                    className="btn navyblue px-3 py-1 mb-2"
                                                                    onClick={
                                                                        clearCompanySignature1
                                                                    }
                                                                >
                                                                    {t(
                                                                        "nonIsrailContract.clear"
                                                                    )}
                                                                </button>
                                                            </div>
                                                        )}
                                                    </>
                                                )
                                            )
                                        }
                                    </div>
                                    <div
                                        className={
                                            "d-flex flex-column align-items-end " +
                                            (isGeneratingPDF ? "col-6" : "")
                                        }
                                    >
                                        <p>
                                            <strong>
                                                {t(
                                                    "nonIsrailContract.nic8Sub.nic8Sub_4"
                                                )}
                                            </strong>
                                        </p>
                                        {/* {formValues &&
                                            formValues.signature3 ? (
                                            <img
                                                src={formValues.signature3}
                                            />
                                        ) : (
                                            <>
                                                <SignatureCanvas
                                                    id="signature3"

                                                    penColor="black"
                                                    canvasProps={{
                                                        width: 250,
                                                        height: 100,
                                                        className:
                                                            "sign101 border mt-1",
                                                    }}
                                                    ref={sigRef3}
                                                    onEnd={
                                                        handleSignatureEnd3
                                                    }
                                                />
                                                {touched.signature3 &&
                                                    errors.signature3 && (
                                                        <p className="text-danger">
                                                            {touched.signature3 &&
                                                                errors.signature3}
                                                        </p>
                                                    )}

                                                {!isGeneratingPDF && (
                                                    <div className="d-flex justify-content-end">
                                                        <button
                                                            type="button"
                                                            className="btn navyblue px-3 py-1 my-2"
                                                            onClick={
                                                                clearSignature3
                                                            }
                                                        >
                                                            {t(
                                                                "nonIsrailContract.nic8Sub.nic8Sub_5"
                                                            )}
                                                        </button>
                                                    </div>
                                                )}
                                            </>
                                        )} */}

                                        {
                                            isGeneratingPDF ? (
                                                sigRef3 ? (
                                                    <img src={sigRef3?.current?.toDataURL()} alt="Signature" />
                                                ) : null
                                            ) : (
                                                formValues &&
                                                    formValues.signature3 ? (
                                                    <img
                                                        src={formValues.signature3}
                                                    />
                                                ) : (
                                                    <>
                                                        <SignatureCanvas
                                                            id="signature3"

                                                            penColor="black"
                                                            canvasProps={{
                                                                width: 250,
                                                                height: 100,
                                                                className:
                                                                    "sign101 border mt-1",
                                                            }}
                                                            ref={sigRef3}
                                                            onEnd={
                                                                handleSignatureEnd3
                                                            }
                                                        />
                                                        {touched.signature3 &&
                                                            errors.signature3 && (
                                                                <p className="text-danger">
                                                                    {touched.signature3 &&
                                                                        errors.signature3}
                                                                </p>
                                                            )}

                                                        {!isGeneratingPDF && (
                                                            <div className="d-flex justify-content-end">
                                                                <button
                                                                    type="button"
                                                                    className="btn navyblue px-3 py-1 my-2"
                                                                    onClick={
                                                                        clearSignature3
                                                                    }
                                                                >
                                                                    {t(
                                                                        "nonIsrailContract.nic8Sub.nic8Sub_5"
                                                                    )}
                                                                </button>
                                                            </div>
                                                        )}
                                                    </>
                                                )
                                            )
                                        }
                                    </div>
                                </div>
                                <div
                                    className="row"
                                    style={{
                                        marginBottom: isGeneratingPDF
                                            ? "110px"
                                            : "16px",
                                    }}
                                >
                                    <div className="col-12">
                                        {
                                            isGeneratingPDF ? (
                                                <div className="form-group mb-4">
                                                    <label className="control-label">
                                                        {t(
                                                            "nonIsrailContract.date"
                                                        )}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        className="form-control"
                                                        name="signatureDate3"
                                                        value={values.signatureDate3}
                                                    />
                                                </div>
                                            ) : (
                                                <DateField
                                                    name={"signatureDate3"}
                                                    onBlur={handleBlur}
                                                    onChange={handleChange}
                                                    label={t(
                                                        "nonIsrailContract.date"
                                                    )}
                                                    value={values.signatureDate3}
                                                    required={true}
                                                    readOnly
                                                    error={
                                                        touched.signatureDate3 &&
                                                        errors.signatureDate3
                                                    }
                                                />
                                            )
                                        }
                                    </div>
                                </div>
                            </section>
                            <section className="col">
                                <ol className="mt-3" style={
                                    workerDetail?.lng === "heb"
                                        ? { direction: "rtl", textAlign: "right" }
                                        : {}
                                }>
                                    <li>
                                        <u>
                                            {t("nonIsrailContract.deduction.title")}
                                        </u>
                                        <ol>
                                            <li>
                                                {t("nonIsrailContract.deduction.deduction1")}
                                            </li>
                                            <li>
                                                {t("nonIsrailContract.deduction.deduction2")}
                                            </li>
                                            <li>
                                                {t("nonIsrailContract.deduction.deduction3")}
                                            </li>
                                        </ol>
                                    </li>
                                    <li className="mt-3">
                                        <u>
                                            {t("nonIsrailContract.obligations.title")}
                                        </u>
                                        <p>
                                            {t("nonIsrailContract.obligations.obligation1")}
                                        </p>
                                    </li>
                                    <li className="mt-3">
                                        <u>
                                            {t("nonIsrailContract.supervisor.title")}
                                        </u>
                                        <p>
                                            {t("nonIsrailContract.supervisor.supervisor1")}
                                        </p>
                                        <p>
                                            {t("nonIsrailContract.supervisor.supervisor2")}
                                        </p>
                                        <div className={`d-flex  ${mobileView ? "flex-wrap justify-content-center" : "justify-content-between"}  mt-3 gap-3`}>
                                            <div
                                                className={
                                                    "" +
                                                    (isGeneratingPDF ? "col-6" : "")
                                                }
                                            >
                                                <p>
                                                    <strong>
                                                        {t("nonIsrailContract.companySign")}
                                                    </strong>
                                                </p>
                                                {/* {initialValues.companySignature2 &&
                                                    initialValues.companySignature2 ? (
                                                    <img
                                                        src={initialValues.companySignature2}
                                                    />
                                                ) : (
                                                    <>
                                                        <SignatureCanvas
                                                            penColor="black"
                                                            canvasProps={{
                                                                width: 250,
                                                                height: 100,
                                                                className:
                                                                    "sign101 border mt-1",
                                                            }}
                                                            ref={companySigRef2}
                                                            onEnd={
                                                                handleCompanySignatureEnd2
                                                            }
                                                        />
                                                        {touched.companySignature2 &&
                                                            errors.companySignature2 && (
                                                                <p className="text-danger">
                                                                    {touched.companySignature2 &&
                                                                        errors.companySignature2}
                                                                </p>
                                                            )}

                                                        {!isGeneratingPDF && (
                                                            <div className="d-block">
                                                                <button
                                                                    className="btn navyblue px-3 py-1 mt-2 mb-2"
                                                                    type="button"
                                                                    onClick={
                                                                        clearCompanySignature2
                                                                    }
                                                                >
                                                                    {t(
                                                                        "nonIsrailContract.clear"
                                                                    )}
                                                                </button>
                                                            </div>
                                                        )}
                                                    </>
                                                )} */}

                                                {
                                                    isGeneratingPDF ? (
                                                        initialValues.companySignature2 &&
                                                            initialValues.companySignature2 ? (
                                                            <img className="company-sign" src={initialValues.companySignature2} alt="Signature" />
                                                        ) : null
                                                    ) : (
                                                        initialValues.companySignature2 &&
                                                            initialValues.companySignature2 ? (
                                                            <img
                                                                src={initialValues.companySignature2}
                                                            />
                                                        ) : (
                                                            <>
                                                                <SignatureCanvas
                                                                    penColor="black"
                                                                    canvasProps={{
                                                                        width: 250,
                                                                        height: 100,
                                                                        className:
                                                                            "sign101 border mt-1",
                                                                    }}
                                                                    ref={companySigRef2}
                                                                    onEnd={
                                                                        handleCompanySignatureEnd2
                                                                    }
                                                                />
                                                                {touched.companySignature2 &&
                                                                    errors.companySignature2 && (
                                                                        <p className="text-danger">
                                                                            {touched.companySignature2 &&
                                                                                errors.companySignature2}
                                                                        </p>
                                                                    )}

                                                                {!isGeneratingPDF && (
                                                                    <div className="d-block">
                                                                        <button
                                                                            className="btn navyblue px-3 py-1 mt-2 mb-2"
                                                                            type="button"
                                                                            onClick={
                                                                                clearCompanySignature2
                                                                            }
                                                                        >
                                                                            {t(
                                                                                "nonIsrailContract.clear"
                                                                            )}
                                                                        </button>
                                                                    </div>
                                                                )}
                                                            </>
                                                        )
                                                    )
                                                }
                                            </div>
                                            <div
                                                className={
                                                    "d-flex align-items-end flex-column" +
                                                    (isGeneratingPDF ? "col-6" : "")
                                                }
                                            >
                                                <p>
                                                    <strong>
                                                        {t("nonIsrailContract.workerSign")}
                                                    </strong>
                                                </p>
                                                {/* {formValues && formValues.signature4 ? (
                                                    <img src={formValues.signature4} />
                                                ) : (
                                                    <>
                                                        <SignatureCanvas
                                                            penColor="black"
                                                            canvasProps={{
                                                                width: 250,
                                                                height: 100,
                                                                className:
                                                                    "sign101 border mt-1",
                                                            }}
                                                            ref={sigRef4}
                                                            onEnd={handleSignatureEnd4}
                                                        />
                                                        {touched.signature4 &&
                                                            errors.signature4 && (
                                                                <p className="text-danger">
                                                                    {touched.signature4 &&
                                                                        errors.signature4}
                                                                </p>
                                                            )}

                                                        {!isGeneratingPDF && (
                                                            <div className="d-block">
                                                                <button
                                                                    type="button"
                                                                    className="btn navyblue px-3 py-1 mt-2 mb-2"
                                                                    onClick={
                                                                        clearSignature4
                                                                    }
                                                                >
                                                                    {t(
                                                                        "nonIsrailContract.clear"
                                                                    )}
                                                                </button>
                                                            </div>
                                                        )}
                                                    </>
                                                )} */}

                                                {
                                                    isGeneratingPDF ? (
                                                        sigRef4 ? (
                                                            <img src={sigRef4?.current?.toDataURL()} alt="Signature" />
                                                        ) : null
                                                    ) : (
                                                        formValues && formValues.signature4 ? (
                                                            <img src={formValues.signature4} />
                                                        ) : (
                                                            <>
                                                                <SignatureCanvas
                                                                    penColor="black"
                                                                    canvasProps={{
                                                                        width: 250,
                                                                        height: 100,
                                                                        className:
                                                                            "sign101 border mt-1",
                                                                    }}
                                                                    ref={sigRef4}
                                                                    onEnd={handleSignatureEnd4}
                                                                />
                                                                {touched.signature4 &&
                                                                    errors.signature4 && (
                                                                        <p className="text-danger">
                                                                            {touched.signature4 &&
                                                                                errors.signature4}
                                                                        </p>
                                                                    )}

                                                                {!isGeneratingPDF && (
                                                                    <div className="d-block">
                                                                        <button
                                                                            type="button"
                                                                            className="btn navyblue px-3 py-1 mt-2 mb-2"
                                                                            onClick={
                                                                                clearSignature4
                                                                            }
                                                                        >
                                                                            {t(
                                                                                "nonIsrailContract.clear"
                                                                            )}
                                                                        </button>
                                                                    </div>
                                                                )}
                                                            </>
                                                        )
                                                    )
                                                }
                                            </div>
                                        </div>

                                        <div className="">

                                            {
                                                isGeneratingPDF ? (
                                                    <div className="form-group mb-4">
                                                        <label className="control-label">
                                                            {t(
                                                                "nonIsrailContract.date"
                                                            )}
                                                        </label>
                                                        <input
                                                            type="text"
                                                            className="form-control"
                                                            name="signatureDate4"
                                                            value={values.signatureDate4}
                                                        />
                                                    </div>
                                                ) : (
                                                    <DateField
                                                        name={"signatureDate4"}
                                                        onBlur={handleBlur}
                                                        onChange={handleChange}
                                                        label={t("nonIsrailContract.date")}
                                                        value={values.signatureDate4}
                                                        required={true}
                                                        readOnly
                                                        error={
                                                            touched.signatureDate4 &&
                                                            errors.signatureDate4
                                                        }
                                                    />
                                                )
                                            }
                                        </div>
                                    </li>
                                </ol>
                            </section>
                        </div>
                    )
                }
                {[5, 6].includes(nextStep) && (
                    <div className={`d-flex justify-content-end ${isGeneratingPDF ? "hide-in-pdf" : ""}`}>
                        <button
                            type="button"
                            onClick={() => setNextStep(prev => prev - 1)}
                            className="navyblue py-2 px-4 mr-2"
                            style={{ borderRadius: "5px" }}
                        >
                            <GrFormPreviousLink /> {t("common.prev")}
                        </button>

                        <button
                            type="submit"
                            className="navyblue py-2 px-4"
                            style={{ borderRadius: "5px" }}
                        >
                            {t("common.next")} <GrFormNextLink />
                        </button>
                    </div>
                )}
            </form>
        </div>
    );
}
