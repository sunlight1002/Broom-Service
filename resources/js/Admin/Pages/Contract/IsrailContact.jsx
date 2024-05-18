import React, { useEffect, useRef, useState } from "react";
import { Table } from "react-bootstrap";
import * as yup from "yup";
import { useFormik } from "formik";
import SignatureCanvas from "react-signature-canvas";
import moment from "moment";

import TextField from "../../../Pages/Form101/inputElements/TextField";
import DateField from "../../../Pages/Form101/inputElements/DateField";
import { useTranslation } from "react-i18next";

export function IsrailContact({
    handleFormSubmit,
    workerDetail,
    workerFormDetails,
    isSubmitted,
    isGeneratingPDF,
    contentRef,
}) {
    const sigRef = useRef();
    const {
        t,
        i18n: { language },
    } = useTranslation();
    const [formValues, setFormValues] = useState(null);
    const currentDate = moment().format("YYYY-MM-DD");
    console.log("language", language);
    const initialValues = {
        fullName: "",
        IdNumber: "",
        Address: "",
        startDate: "",
        signatureDate: currentDate,
        PhoneNo: "",
        MobileNo: "",
        signature: "",
        role: "",
    };
    const formSchema = yup.object({
        fullName: yup
            .string()
            .trim()
            .required(t("israilContract.errorMsg.FullName")),
        role: yup.string().trim().required(t("israilContract.errorMsg.Role")),
        IdNumber: yup
            .number()
            .typeError(t("israilContract.errorMsg.invalidNumber"))
            .required(t("israilContract.errorMsg.idRequired")),
        Address: yup
            .string()
            .trim()
            .required(t("israilContract.errorMsg.Address")),
        startDate: yup
            .date()
            .required(t("israilContract.errorMsg.StartDateOfJob")),
        signatureDate: yup.date().required(t("israilContract.errorMsg.Date")),
        PhoneNo: yup
            .string()
            .trim()
            .required(t("israilContract.errorMsg.Phone")),
        MobileNo: yup
            .string()
            .trim()
            .matches(/^\d{10}$/, t("israilContract.errorMsg.mobile"))
            .required(t("israilContract.errorMsg.mobileRequired")),
        signature: yup
            .mixed()
            .required(t("israilContract.errorMsg.signRequired")),
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
            setFieldValue("IdNumber", workerDetail.worker_id);
            setFieldValue("Address", workerDetail.address);
            setFieldValue("PhoneNo", workerDetail.phone);
            setFieldValue("role", workerDetail.role);
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

    return (
        <div className="container targetDiv">
            <div id="content">
                <div className="mx-5 mt-5" ref={contentRef}>
                    <form onSubmit={handleSubmit}>
                        <div className="text-center">
                            <h5>
                                <strong>
                                    <u>{t("israilContract.title1")}</u>
                                </strong>
                            </h5>

                            <p className="mt-2">{t("israilContract.title2")}</p>
                        </div>

                        <div>
                            <ol
                                className="mt-5 lh-lg "
                                style={{ fontSize: "16px" }}
                            >
                                <li>
                                    <strong>{t("israilContract.is1")}</strong>
                                    <div className="row gap-3">
                                        <div className="col-6">
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
                                        <div className="col-6">
                                            <TextField
                                                name={"IdNumber"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={t(
                                                    "israilContract.IDNumber"
                                                )}
                                                value={values.IdNumber}
                                                required={true}
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
                                        label={t(
                                            "israilContract.StartDateOfJob"
                                        )}
                                        value={values.startDate}
                                        required={true}
                                        error={
                                            touched.startDate &&
                                            errors.startDate
                                        }
                                    />
                                    <div className="row">
                                        <div className="col-6">
                                            <TextField
                                                name={"PhoneNo"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={t(
                                                    "israilContract.HomePhone"
                                                )}
                                                value={values.PhoneNo}
                                                required={true}
                                                readonly={true}
                                                error={
                                                    touched.PhoneNo &&
                                                    errors.PhoneNo
                                                }
                                            />
                                        </div>
                                        <div className="col-6">
                                            <TextField
                                                name={"MobileNo"}
                                                onBlur={handleBlur}
                                                onChange={handleChange}
                                                label={t(
                                                    "israilContract.mobileNumber"
                                                )}
                                                value={values.MobileNo}
                                                required={true}
                                                error={
                                                    touched.MobileNo &&
                                                    errors.MobileNo
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
                                        />
                                    </div>
                                </li>
                                <li>
                                    <p className="mb-2">
                                        {t("israilContract.is4")}
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        {t("israilContract.is5-1", {
                                            payment_per_hour:
                                                workerDetail.payment_per_hour,
                                        })}
                                    </p>
                                    <p style={{ marginBottom: "145px" }}>
                                        {t("israilContract.is5-2")}
                                    </p>
                                </li>
                                <li>
                                    <p>{t("israilContract.is6")}</p>
                                    <Table
                                        bordered
                                        className="mt-3 mb-2"
                                        size="sm"
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
                                        <tbody>
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
                                                <td>
                                                    {" "}
                                                    {t(
                                                        "israilContract.is6Table.tr5.td3"
                                                    )}
                                                </td>
                                                <td>
                                                    {t(
                                                        "israilContract.is6Table.tr5.td4"
                                                    )}
                                                </td>
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
                                    <p style={{ marginBottom: "30px" }}>
                                        {t("israilContract.is10-1")}
                                    </p>
                                    <p className="mb-2">
                                        {t("israilContract.is10-2")}
                                    </p>
                                </li>
                                <li>
                                    <p className="mb-2">
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
                                        bordered
                                        size="sm"
                                        className="mt-3"
                                        style={{ marginBottom: "120px" }}
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
                                        <tbody>
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
                                    <p className="mb-2">
                                        {t("israilContract.is13-3")}
                                    </p>
                                </li>
                                <li>{t("israilContract.is14")}</li>
                            </ol>
                            <div className="row mt-3">
                                <div className="col-4">
                                    <p>
                                        <strong>
                                            {t("israilContract.sign")}
                                        </strong>
                                        <span className="text-danger">
                                            {touched.signature &&
                                                errors.signature}
                                        </span>
                                    </p>
                                    {formValues && formValues.signature ? (
                                        <img src={formValues.signature} />
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
                                                ref={sigRef}
                                                onEnd={handleSignatureEnd}
                                            />

                                            {!isGeneratingPDF && (
                                                <div className="d-block">
                                                    <button
                                                        className="btn btn-warning mb-2"
                                                        type="button"
                                                        onClick={clearSignature}
                                                    >
                                                        {t(
                                                            "israilContract.Clear"
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
                                        name={"signatureDate"}
                                        onBlur={handleBlur}
                                        onChange={handleChange}
                                        label={"Date"}
                                        value={values.signatureDate}
                                        required={true}
                                        readOnly
                                        error={
                                            touched.signatureDate &&
                                            errors.signatureDate
                                        }
                                    />
                                </div>
                            </div>
                            <p className="text-right">
                                {t("israilContract.signNote")}
                            </p>
                            <div className="text-center mt-5">
                                <p>{t("israilContract.BestRegards")}</p>
                                <strong>{t("israilContract.Broom")}</strong>
                            </div>
                        </div>
                        {!isSubmitted && !isGeneratingPDF && (
                            <button
                                className="btn btn-success mt-3"
                                type="submit"
                                disabled={isSubmitting}
                            >
                                {t("israilContract.Accept")}
                            </button>
                        )}
                    </form>
                </div>
            </div>
        </div>
    );
}
