import React, { useEffect, useRef, useState } from "react";
import { Table } from "react-bootstrap";
import * as yup from "yup";
import { useFormik } from "formik";
import SignatureCanvas from "react-signature-canvas";
import moment from "moment";

import TextField from "../../../Pages/Form101/inputElements/TextField";
import DateField from "../../../Pages/Form101/inputElements/DateField";
import { useTranslation } from "react-i18next";
import i18next from "i18next";

export function NonIsraeliContract({
    handleFormSubmit,
    workerDetail,
    workerFormDetails,
    isSubmitted,
    isGeneratingPDF,
    contentRef,
}) {
    const sigRef1 = useRef();
    const sigRef2 = useRef();
    const sigRef3 = useRef();
    const sigRef4 = useRef();
    const companySigRef1 = useRef();
    const companySigRef2 = useRef();
    const { t } = useTranslation();
    const [formValues, setFormValues] = useState(null);

    const currentDate = moment().format("YYYY-MM-DD");

    const initialValues = {
        fullName: "",
        IdNumber: "",
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
        companySignature1: "",
        companySignature2: "",
        role: "",
    };

    const formSchema = yup.object({
        fullName: yup
            .string()
            .trim()
            .required(t("nonIsrailContract.errorMsg.FullName")),
        role: yup
            .string()
            .trim()
            .required(t("nonIsrailContract.errorMsg.Role")),
        IdNumber: yup
            .number()
            .typeError(t("nonIsrailContract.errorMsg.invalidId"))
            .required(t("nonIsrailContract.errorMsg.idRequired")),
        Address: yup
            .string()
            .trim()
            .required(t("nonIsrailContract.errorMsg.address")),
        startDate: yup
            .date()
            .required(t("nonIsrailContract.errorMsg.startDate")),
        signatureDate1: yup
            .date()
            .required(t("nonIsrailContract.errorMsg.Date")),
        signatureDate2: yup
            .date()
            .required(t("nonIsrailContract.errorMsg.Date")),
        signatureDate3: yup
            .date()
            .required(t("nonIsrailContract.errorMsg.Date")),
        signatureDate4: yup
            .date()
            .required(t("nonIsrailContract.errorMsg.Date")),
        signature1: yup.mixed().required(t("nonIsrailContract.errorMsg.sign")),
        signature2: yup.mixed().required(t("nonIsrailContract.errorMsg.sign")),
        signature3: yup.mixed().required(t("nonIsrailContract.errorMsg.sign")),
        signature4: yup.mixed().required(t("nonIsrailContract.errorMsg.sign")),
    });

    const {
        errors,
        touched,
        handleBlur,
        handleChange,
        handleSubmit,
        values,
        setFieldValue,
        isSubmitting,
    } = useFormik({
        initialValues: formValues ?? initialValues,
        enableReinitialize: true,
        validationSchema: formSchema,
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
            setFieldValue("PhoneNo", workerDetail.phone);
        }
    }, [isSubmitted, workerFormDetails, workerDetail]);

    const disableInputs = () => {
        const inputs = document.querySelectorAll(".targetDiv input");
        inputs.forEach((input) => {
            input.disabled = true;
        });
    };

    const handleSignatureEnd1 = () => {
        setFieldValue("signature1", sigRef1.current.toDataURL());
    };
    const clearSignature1 = () => {
        sigRef1.current.clear();
        setFieldValue("signature1", "");
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

    return (
        <div className="container targetDiv">
            <div id="content">
                <div className="mx-5 mt-5" ref={contentRef}>
                    <form onSubmit={handleSubmit}>
                        <div className="text-center">
                            <h5>
                                <strong>
                                    <u>{t("nonIsrailContract.title1")}</u>
                                </strong>
                            </h5>

                            <p className="mt-2">
                                {t("nonIsrailContract.title2")}
                            </p>
                        </div>
                        <div>
                            <ol
                                className="mt-5 lh-lg"
                                style={{ fontSize: "16px" }}
                            >
                                <li>
                                    <strong>
                                        {t("nonIsrailContract.nic1")}
                                    </strong>
                                    <div className="row gap-3">
                                        <div className="col-6">
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
                                        <div className="col-6">
                                            <TextField
                                                name={"IdNumber"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={t(
                                                    "nonIsrailContract.passport"
                                                )}
                                                value={values.IdNumber}
                                                required={true}
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
                                <li>
                                    <p className="mb-4">
                                        {t("nonIsrailContract.nic5", {
                                            payment_per_hour:
                                                workerDetail.payment_per_hour,
                                        })}
                                    </p>
                                    <ol>
                                        <li>
                                            <p style={{ marginBottom: "90px" }}>
                                                {t(
                                                    "nonIsrailContract.nic5_sub.nic5_sub1"
                                                )}
                                            </p>
                                            <div className="row mt-5">
                                                <div className="col-4">
                                                    <p>
                                                        <strong>
                                                            {t(
                                                                "nonIsrailContract.nic5_sub.nic5_sub2"
                                                            )}
                                                        </strong>
                                                    </p>
                                                    {formValues &&
                                                    formValues.signature1 ? (
                                                        <img
                                                            src={
                                                                formValues.signature1
                                                            }
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

                                                            {!isGeneratingPDF && (
                                                                <div className="d-block">
                                                                    <button
                                                                        type="button"
                                                                        className="btn btn-warning mb-2"
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
                                                        </>
                                                    )}
                                                </div>
                                                <div className="col-5"></div>
                                                <div className="col-3">
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
                                                </div>
                                            </div>
                                        </li>
                                    </ol>
                                    <Table bordered className="mt-3" size="sm">
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
                                        <tbody>
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
                                    <p>
                                        {t(
                                            "nonIsrailContract.nic5_sub.nic5_sub3"
                                        )}
                                    </p>
                                    <p>
                                        {t(
                                            "nonIsrailContract.nic5_sub.nic5_sub4"
                                        )}
                                    </p>
                                    <p>
                                        {t(
                                            "nonIsrailContract.nic5_sub.nic5_sub5"
                                        )}
                                    </p>
                                    <div className="row mt-3">
                                        <div className="col-4">
                                            <p>
                                                <strong>
                                                    {t(
                                                        "nonIsrailContract.workerSign"
                                                    )}
                                                </strong>
                                            </p>
                                            {formValues &&
                                            formValues.signature2 ? (
                                                <img
                                                    src={formValues.signature2}
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
                                                                className="btn btn-warning mb-2"
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
                                                </>
                                            )}
                                        </div>
                                        <div className="col-5"></div>
                                        <div className="col-3">
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
                                        </div>
                                    </div>
                                </li>
                                <li>
                                    <p className="mb-2">
                                        {t("nonIsrailContract.nic6")}
                                    </p>
                                </li>
                                <li>
                                    <p style={{ marginBottom: "90px" }}>
                                        {t("nonIsrailContract.nic7")}
                                    </p>
                                </li>
                                <li>
                                    <p className="mb-2">
                                        {t("nonIsrailContract.nic8")}
                                    </p>
                                    <Table bordered size="sm" className=" mt-3">
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
                                        <tbody>
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
                                    <div
                                        className="row gap-3"
                                        style={{ marginBottom: "90px" }}
                                    >
                                        <div className="col-6">
                                            <p>
                                                <strong>
                                                    {t(
                                                        "nonIsrailContract.nic8Sub.nic8Sub_4"
                                                    )}
                                                </strong>
                                            </p>
                                            {formValues &&
                                            formValues.signature3 ? (
                                                <img
                                                    src={formValues.signature3}
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
                                                        <div className="d-block">
                                                            <button
                                                                type="button"
                                                                className="btn btn-warning mb-2"
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
                                            )}
                                        </div>
                                        <div className="col-6">
                                            <p>
                                                <strong>
                                                    {t(
                                                        "nonIsrailContract.nic8Sub.nic8Sub_6"
                                                    )}
                                                </strong>
                                            </p>
                                            {formValues &&
                                            formValues.companySignature1 ? (
                                                <img
                                                    src={
                                                        formValues.companySignature1
                                                    }
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
                                                                className="btn btn-warning mb-2"
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
                                            )}
                                        </div>
                                    </div>
                                    <div className="row">
                                        <div className="col-12">
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
                                        </div>
                                    </div>
                                </li>
                            </ol>
                            <ol className="mt-3">
                                <li>
                                    <u>
                                        {t("nonIsrailContract.deduction.title")}
                                    </u>
                                    <ol>
                                        <li>
                                            {t(
                                                "nonIsrailContract.deduction.deduction1"
                                            )}
                                        </li>
                                        <li>
                                            {t(
                                                "nonIsrailContract.deduction.deduction2"
                                            )}
                                        </li>
                                        <li>
                                            {t(
                                                "nonIsrailContract.deduction.deduction3"
                                            )}
                                        </li>
                                    </ol>
                                </li>
                                <li className="mt-3">
                                    <u>
                                        {" "}
                                        {t(
                                            "nonIsrailContract.obligations.title"
                                        )}
                                    </u>
                                    <p>
                                        {t(
                                            "nonIsrailContract.obligations.obligation1"
                                        )}
                                    </p>
                                </li>
                                <li className="mt-3">
                                    <u>
                                        {" "}
                                        {t(
                                            "nonIsrailContract.supervisor.title"
                                        )}
                                    </u>
                                    <p>
                                        {t(
                                            "nonIsrailContract.supervisor.supervisor1"
                                        )}
                                    </p>
                                    <p>
                                        {t(
                                            "nonIsrailContract.supervisor.supervisor2"
                                        )}
                                    </p>
                                </li>
                            </ol>
                            <div className="row mt-5 gap-3">
                                <div className="col-6">
                                    <p>
                                        <strong>
                                            {t("nonIsrailContract.workerSign")}
                                        </strong>
                                    </p>
                                    {formValues && formValues.signature4 ? (
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
                                                        className="btn btn-warning mb-2"
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
                                    )}
                                </div>
                                <div className="col-6">
                                    <p>
                                        <strong>
                                            {t("nonIsrailContract.companySign")}
                                        </strong>
                                    </p>
                                    {formValues &&
                                    formValues.companySignature2 ? (
                                        <img
                                            src={formValues.companySignature2}
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
                                                        className="btn btn-warning mb-2"
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
                                    )}
                                </div>
                                <div className="col-12">
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
                                </div>
                            </div>
                        </div>
                        {!isSubmitted && !isGeneratingPDF && (
                            <button
                                className="btn btn-success mt-3"
                                type="submit"
                                disabled={isSubmitting}
                            >
                                {t("nonIsrailContract.Accept")}
                            </button>
                        )}
                    </form>
                </div>
            </div>
        </div>
    );
}
