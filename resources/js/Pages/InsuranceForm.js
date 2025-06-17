import React, { useState, useEffect, useRef } from "react";
import { PDFDocument } from 'pdf-lib'
import fontkit from "@pdf-lib/fontkit";
import { pdfjs } from "react-pdf";
import { Document, Page } from "react-pdf";
import i18next from "i18next";
import { useNavigate, useParams } from "react-router-dom";
import { Base64 } from "js-base64";
import * as yup from "yup";
import { useFormik } from "formik";
import Modal from "react-bootstrap/Modal";
import Button from "react-bootstrap/Button";
import Form from "react-bootstrap/Form";
import "react-pdf/dist/Page/AnnotationLayer.css";
import "react-pdf/dist/Page/TextLayer.css";
import { objectToFormData } from "../Utils/common.utils";
import { useTranslation } from "react-i18next";
import SignatureCanvas from "react-signature-canvas";
import companySign from '../Assets/image/company-sign.png'
import Font from '../../../public/fonts/OSAran400FFC.ttf'
import { GrFormPreviousLink } from "react-icons/gr";
import { GrFormNextLink } from "react-icons/gr";
import {useAlert} from "react-alert";

import moment from "moment";
import FullPageLoader from "../Components/common/FullPageLoader";

pdfjs.GlobalWorkerOptions.workerSrc = new URL(
    "pdfjs-dist/build/pdf.worker.min.js",
    import.meta.url
).toString();


const InsuranceForm = ({
    nextStep,
    setNextStep,
    isManpower,
    type
}) => {
    const alert = useAlert();
    const [show, setShow] = useState(false);
    const sigRef = useRef();
    const currentDate = moment().format("YYYY-MM-DD");
    const [pdfData, setPdfData] = useState(null);
    const { t } = useTranslation();
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();
    const initialValues = {
        // page 1
        type: "New",
        // section-2
        canFirstName: "",
        canLastName: "",
        canPassport: "",
        canOrigin: "",
        canDOB: "",
        canFirstDateOfIns: "",
        canZipcode: "",
        canTown: "",
        canHouseNo: "",
        canStreet: "",
        canTelephone: "",
        canCellPhone: "",
        canEmail: "",
        gender: "male",
        // page 3
        GCandidatename: "",
        GDate: "",
        g1: "",
        g1Height: "",
        g1Weight: "",
        g2: "",
        g3: "",
        g4: "",
        g4Today: "",
        g4Past: "",
        g4WhenStop: "",
        g5: "",
        g6: "",
        g7: "",
        g7Reason: "",
        g8: "",
        g9: "",
        g10: "",
        g11: "",
        g12: "",
        g13: "",
        g14: "",
        g15: "",
        g16: "",
        g17: "",
        g18: "",
        g18Treatment: "",
        g19: "",
        g20: "",
        g21: "",
        g22: "",
        g23: "",
        g24: "",
        g24Treatment: "",
        // page 4
        canDate: currentDate,
        details: "",
        signature: "",
        // stamp: companySign
    };

    useEffect(() => {
        window.scroll(0, 0);
    }, [nextStep])


    const scrollToError = (errors) => {
        const errorFields = Object.keys(errors);
        if (errorFields.length > 0) {
            const firstErrorField = errorFields[0];
            const errorElement = document.getElementById(firstErrorField);
            console.log(firstErrorField, errorElement);

            if (errorElement) {
                errorElement.scrollIntoView({ behavior: "smooth" });
                errorElement.focus();
            }
        }
    };



    const commonFields = yup.object({
        canFirstName: yup
            .string()
            .trim()
            .min(2, t("insurance.fname2CharLong"))
            .required(t("insurance.fnameReq")),
        canLastName: yup
            .string()
            .trim()
            .min(2, t("insurance.lname2CharLong"))
            .required(t("insurance.lnameReq")),
        canPassport: yup
            .string()
            .trim()
            .min(2, t("insurance.passport2CharLong"))
            .required(t("insurance.passportReq")),
        canOrigin: yup.string().trim().required(t("insurance.originReq")),
        canDOB: yup.date().required(t("insurance.dobReq")),
        canFirstDateOfIns: yup.date().required(t("insurance.FDIReq")),
        // canZipcode: yup.string().trim().required(t("insurance.zipReq")),
        canTown: yup.string().trim().required(t("insurance.townReq")),
        canHouseNo: yup.string().trim().required(t("insurance.houseNumReq")),
        canStreet: yup.string().trim().required(t("insurance.streetReq")),
        // canTelephone: yup.number().required(t("insurance.telReq")),
        canCellPhone: yup.number().required(t("insurance.phoneReq")),
        canEmail: yup.string().trim().email().required(t("insurance.emailReq")),
        gender: yup.string().trim().required(t("insurance.genderReq")),
        g1Height: yup.string().trim().required(t("insurance.heightReq")),
        g1Weight: yup.string().trim().required(t("insurance.weightReq")),
        g2: yup.string().trim().required(t("insurance.yes_no_req")),
        g3: yup.string().trim().required(t("insurance.yes_no_req")),
        g4: yup.string().trim().required(t("insurance.yes_no_req")),
        g5: yup.string().trim().required(t("insurance.yes_no_req")),
        g6: yup.string().trim().required(t("insurance.yes_no_req")),
        g7: yup.string().trim().required(t("insurance.yes_no_req")),
        g8: yup.string().trim().required(t("insurance.yes_no_req")),
        g9: yup.string().trim().required(t("insurance.yes_no_req")),
        g10: yup.string().trim().required(t("insurance.yes_no_req")),
        g11: yup.string().trim().required(t("insurance.yes_no_req")),
        g12: yup.string().trim().required(t("insurance.yes_no_req")),
        g13: yup.string().trim().required(t("insurance.yes_no_req")),
        g14: yup.string().trim().required(t("insurance.yes_no_req")),
        g15: yup.string().trim().required(t("insurance.yes_no_req")),
        g16: yup.string().trim().required(t("insurance.yes_no_req")),
        g17: yup.string().trim().required(t("insurance.yes_no_req")),
        g18: yup.string().trim().required(t("insurance.yes_no_req")),
        g19: yup.string().trim().required(t("insurance.yes_no_req")),
        g20: yup.string().trim().required(t("insurance.yes_no_req")),
        g21: yup.string().trim().required(t("insurance.yes_no_req")),
        g22: yup.string().trim().required(t("insurance.yes_no_req")),
        g23: yup.string().trim().required(t("insurance.yes_no_req")),
        g24: yup.string().trim().required(t("insurance.yes_no_req")),

        details: yup
            .string()
            .test(
                "is-required-or-nullable",
                "Details are required because a field is marked 'yes'",
                function (value) {
                    const {
                        g2, g3, g4, g5, g6, g7, g8, g9, g10,
                        g11, g12, g13, g14, g15, g16, g17, g18, g19, g20, g21, g22,
                        g23, g24
                    } = this.parent;

                    const requiresDetails = [
                        g2, g3, g4, g5, g6, g7, g8, g9, g10,
                        g11, g12, g13, g14, g15, g16, g17, g18, g19, g20, g21, g22,
                        g23, g24
                    ].includes("yes");

                    if (!requiresDetails) {
                        return true; // Allow null or undefined if not required
                    }

                    if (!value) {
                        return this.createError({ message: t("insurance.positive_finding_error") });
                    }

                    return true;
                }
            )
            .nullable(),
        signature: yup.mixed().required(t("form101.errorMsg.sign")),
    });

    // Define the formSchema using commonFields
    const formSchema = yup.object({
        step7: commonFields,
        step2: commonFields
    });

    const [formValues, setFormValues] = useState(null);
    const [isSubmitted, setIsSubmitted] = useState(false);

    const params = useParams();
    const id = Base64.decode(params.id);

    const {
        errors,
        touched,
        handleBlur,
        handleChange,
        values,
        setFieldValue,
        handleSubmit,
        isSubmitting,
        validateForm,
        setTouched,
        isValid,
    } = useFormik({
        initialValues: formValues ?? initialValues,
        enableReinitialize: true,
        validationSchema: formSchema[`step${nextStep}`] || commonFields,
        onSubmit: async (values) => {

            await saveFormData(true);
        },
        validateOnChange: true,  // Validate on every field change
        validateOnBlur: true,
    });


    const handleNextPrev = async (e) => {
        e.preventDefault();
        handleSubmit();
        // Validate the current step
        const validationErrors = await validateForm(); // This will populate the `errors` object
        scrollToError(validationErrors);
    };


    const saveFormData = async (isSubmit) => {
         const formPdfBytes = await fetch("/pdfs/health-insurance.pdf").then(res => res.arrayBuffer());
        const pdfDoc = await PDFDocument.load(formPdfBytes);
        pdfDoc.registerFontkit(fontkit);

        // Fetch Hebrew font from public directory
        const fontBytes = await fetch(Font).then(res => res.arrayBuffer());
        const hebrewFont = await pdfDoc.embedFont(fontBytes);

        const pdfForm = pdfDoc.getForm();

        const containsHebrew = (text) => /[\u0590-\u05FF]/.test(text);

        // Get all the form fields before modifying them
        const hebrewCheckFields = ["canStreet", "canTown"];
        hebrewCheckFields.forEach((fieldName) => {
            const fieldText = values[fieldName] || "";
            const field = pdfForm.getTextField(fieldName);

            // Instead of using setFont directly, use updateAppearances with the font
            try {
                // Set the text first
                field.setText(fieldText);
                
                // Only attempt to update appearances with Hebrew font if Hebrew characters detected
                if (containsHebrew(fieldText)) {
                    // Use the lower-level API to update appearances with the Hebrew font
                    field.updateAppearances(hebrewFont);
                }
            } catch (error) {
                console.warn(`Could not set font for field ${fieldName}:`, error);
                // Fallback - just set the text without custom font
                field.setText(fieldText);
            }
        });




        pdfForm.getTextField("canFirstName").setText(values.canFirstName || "");
        pdfForm.getTextField("canLastName").setText(values.canLastName || "");
        pdfForm.getTextField("canPassport").setText(values.canPassport || "");
        pdfForm.getTextField("canOrigin").setText(values.canOrigin || "");
        pdfForm.getTextField("canDOB").setText(values.canDOB || "");
        pdfForm
            .getTextField("canFirstDateOfIns")
            .setText(values.canFirstDateOfIns || "");
        pdfForm.getTextField("canZipcode").setText(values.canZipcode || "");
        // pdfForm.getTextField("canTown").setText(values.canTown || "");
        pdfForm.getTextField("canHouseNo").setText(values.canHouseNo || "");
        // pdfForm.getTextField("canStreet").setText(values.canStreet || "");
        pdfForm.getTextField("canTelephone").setText(values.canTelephone || "");
        pdfForm.getTextField("canCellPhone").setText(values.canCellPhone || "");
        pdfForm.getTextField("canEmail").setFontSize(9);
        pdfForm.getTextField("canEmail").setText(values.canEmail || "");
        const genderRadioGroup = pdfForm.getRadioGroup("gender");
        genderRadioGroup.select(values.gender);
        genderRadioGroup.defaultUpdateAppearances();

        pdfForm.getTextField("G-firstname").setText(values.canFirstName || "");
        pdfForm.getTextField("G-lastname").setText(values.canLastName || "");
        pdfForm.getTextField("G-passportno").setText(values.canPassport || "");
        pdfForm.getTextField("G-candidatename").setText(values.GCandidatename || "");
        pdfForm.getTextField("G-date").setText(values.canDate);
        pdfForm
            .getTextField("candidate-passport-no")
            .setText(values.canPassport || "");
        pdfForm.getTextField("candidate-name").setText(values.GCandidatename || "");
        pdfForm.getTextField("candidate-date").setText(values.canDate || "");
        pdfForm.getTextField("G-height-en").setFontSize(9);
        pdfForm.getTextField("G-height-en").setText(values.g1Height || "");
        pdfForm.getTextField("G-height-heb").setFontSize(9);
        pdfForm.getTextField("G-height-heb").setText(values.g1Height || "");
        pdfForm.getTextField("G-weight-en").setFontSize(9);
        pdfForm.getTextField("G-weight-en").setText(values.g1Weight || "");
        pdfForm.getTextField("G-weight-heb").setFontSize(9);
        pdfForm.getTextField("G-weight-heb").setText(values.g1Weight || "");
        pdfForm.getTextField("G7-reason-en").setFontSize(9);
        pdfForm.getTextField("G7-reason-en").setText(values.g7Reason || "");
        pdfForm.getTextField("G7-reason-heb").setFontSize(9);
        pdfForm.getTextField("G7-reason-heb").setText(values.g7Reason || "");
        pdfForm.getTextField("G18-treatment-en").setFontSize(9);
        pdfForm.getTextField("G18-treatment-en").setText(values.g18Treatment || "");
        pdfForm.getTextField("G18-treatment-heb").setFontSize(9);
        pdfForm.getTextField("G18-treatment-heb").setText(values.g18Treatment || "");
        pdfForm.getTextField("G24-answer-en").setFontSize(9);
        pdfForm.getTextField("G24-answer-en").setText(values.g24Treatment || "");
        pdfForm.getTextField("G24-answer-heb").setFontSize(9);
        pdfForm.getTextField("G24-answer-heb").setText(values.g24Treatment || "");
        pdfForm.getTextField("G-stopped-smoking-on-en").setFontSize(9);
        pdfForm
            .getTextField("G-stopped-smoking-on-en")
            .setText(values.g4WhenStop || "");
        pdfForm.getTextField("G-stopped-smoking-on-heb").setFontSize(9);
        pdfForm
            .getTextField("G-stopped-smoking-on-heb")
            .setText(values.g4WhenStop || "");
        // pdfForm.getTextField("details").setText(values.details || "");

        for (let i = 1; i <= 24; i++) {
            const key = "g" + i;
            const toUpperCase = "G" + i;
            if (values[key] === "yes") {
                pdfForm.getCheckBox(`${toUpperCase}-Yes`).check();
            } else if (values[key] === "no") {
                pdfForm.getCheckBox(`${toUpperCase}-No`).check();
            }
        }

        if (values.signature) {
            const pngImageBytes = await fetch(values.signature).then((res) =>
                res.arrayBuffer()
            );

            const companyStamp = await fetch(companySign).then((res) =>
                res.arrayBuffer()
            );

            const stamp = await pdfDoc.embedPng(companyStamp);

            const pngImage = await pdfDoc.embedPng(pngImageBytes);

            const pngDims1 = pngImage.scale(0.28);
            const pngDims2 = pngImage.scale(0.24);
            const stamp1 = stamp.scale(0.35);
            const stamp2 = stamp.scale(0.20);

            const page2 = pdfDoc.getPage(1);
            const page3 = pdfDoc.getPage(2);
            const page4 = pdfDoc.getPage(3);

            const fontBytes = await fetch(Font).then((res) => res.arrayBuffer());
            const hebrewFont = await pdfDoc.embedFont(fontBytes);

            page2.drawText(values.canDate, {
                x: 480,
                y: page2.getHeight() / 2 - pngDims1.height / 2 - 190,
                size: 14,
            });

            page2.drawText("ברום סרוויס ל.מ בע\'מ", {
                x: 320,
                y: page2.getHeight() / 2 - pngDims1.height / 2 - 190,
                size: 18,
                font: hebrewFont
            });

            page2.drawImage(stamp, {
                x: page3.getWidth() / 2 - pngDims1.width / 2 - 150,
                y: page3.getHeight() / 2 - pngDims1.height / 2 - 190,
                width: stamp1.width,
                height: stamp2.height,
            });

            page3.drawImage(pngImage, {
                x: page3.getWidth() / 2 - pngDims1.width / 2 - 90,
                y: page3.getHeight() / 2 - pngDims1.height / 2 - 390,
                width: pngDims1.width,
                height: pngDims1.height,
            });

            page3.drawText(values.details || "", { // Provide an empty string as fallback
                x: page3.getWidth() / 2 - pngDims1.width / 2 - 220,
                y: page3.getHeight() / 2 - pngDims1.height / 2 - 355,
                size: 10,
            });


            page4.drawImage(pngImage, {
                x: page4.getWidth() / 2 - pngDims2.width / 2 - 80,
                y: page4.getHeight() / 2 - pngDims2.height / 2 + 290,
                width: pngDims2.width,
                height: pngDims2.height,
            });

            page4.drawImage(pngImage, {
                x: page4.getWidth() / 2 - pngDims2.width / 2 + 130,
                y: page4.getHeight() / 2 - pngDims2.height / 2 + 295,
                width: pngDims2.width,
                height: pngDims2.height,
            });

            page4.drawImage(pngImage, {
                x: page4.getWidth() / 2 - pngDims1.width / 2 - 145,
                y: page4.getHeight() / 2 - pngDims1.height / 2 - 300,
                width: pngDims1.width,
                height: pngDims1.height,
            });
        }

        // it makes form readonly
        if (isSubmit) {
            pdfForm.flatten();
        }

        const pdfBytes = await pdfDoc.save();
        const blob = new Blob([pdfBytes], { type: "application/pdf" });
        const url = URL.createObjectURL(blob);

        if (!isSubmit) {
            setPdfData(url);
            setShow(true);
        } else {
            setLoading(true);
            // Convert JSON object to FormData
            let formData = objectToFormData(values);
            formData.append("pdf_file", blob);
            formData.append("step", nextStep);
            formData.append("type", type == "lead" ? "lead" : "worker");

            axios
                .post(`/api/worker/${id}/insurance-form`, formData, {
                    headers: {
                        Accept: "application/json, text/plain, */*",
                        "Content-Type": "multipart/form-data",
                    },
                })
                .then((res) => {
                    alert.success(t("insurance.signedSuccess"));
                    // Swal.fire({
                    //     text: t("insurance.signedSuccess"),
                    //     icon: "success",
                    // });
                    if (type === "lead" && res?.data?.id) {
                        navigate(`/worker-forms/${Base64.encode(res?.data?.id.toString())}`);
                    }
                    setNextStep(8);
                    // setTimeout(() => {
                    //     window.location.reload(true);
                    // }, 2000);
                    setLoading(false);
                })
                .catch((e) => {
                    setNextStep(8);
                    setLoading(false);
                    // Swal.fire({
                    //     title: "Error!",
                    //     text: e.response?.data?.message,
                    //     icon: "error",
                    // });
                });
        }
    };

    const handleShow = async () => {
        await saveFormData(false);
    };

    const handleClose = () => setShow(false);

    const getForm = async () => {
        await axios.get(`/api/worker/${id}/insurance-form/${type}`, {
            headers: {
                Accept: "application/json, text/plain, */*",
                "Content-Type": "multipart/form-data",
                Authorization: `Bearer ` + localStorage.getItem("admin-token"),
            }
        }).then((res) => {
            i18next.changeLanguage(res.data.lng);
            if (res.data.lng == "heb") {
                import("../Assets/css/rtl.css");
                document.querySelector("html").setAttribute("dir", "rtl");
            } else {
                document.querySelector("html").removeAttribute("dir");
                const rtlLink = document.querySelector('link[href*="rtl.css"]');
                if (rtlLink) {
                    rtlLink.remove();
                }
            }

            if (res.data.form) {
                setFormValues(res.data.form.data);
                if (res.data.form.submitted_at) {
                    setTimeout(() => {
                        disableInputs();
                    }, 2000);
                    setIsSubmitted(true);
                }
            } else if (res.data.worker) {
                const _worker = res.data.worker;
                setFieldValue("IDNumber", _worker.worker_id);
                setFieldValue("FirstName", _worker.firstname);
                setFieldValue("LastName", _worker.lastname);
                setFieldValue("Email", _worker.email);
                setFieldValue("canOrigin", _worker.country);
                setFieldValue("CellphoneNo", _worker.phone);
                setFieldValue("canFirstName", _worker.firstname);
                setFieldValue("canLastName", _worker.lastname);
                setFieldValue("canEmail", _worker.email);
                setFieldValue("canCellPhone", _worker.phone);
                setFieldValue("canPassport", _worker.passport);
                setFieldValue("canFirstDateOfIns", _worker.first_date);

                const _gender = _worker.gender;
                setFieldValue(
                    "gender",
                    _gender.charAt(0).toUpperCase() + _gender.slice(1)
                );
                setFieldValue("FFirstName", _worker.firstname);
                setFieldValue("FLastName", _worker.lastname);
                setFieldValue("GFirstname", _worker.firstname);
                setFieldValue("GLastname", _worker.lastname);
            }
        });
    };

    const disableInputs = () => {
        // Disable inputs within the div with the id "targetDiv"
        const inputs = document.querySelectorAll("input ");
        inputs.forEach((input) => {
            input.disabled = true;
        });
        const selects = document.querySelectorAll("select");
        selects.forEach((select) => {
            select.disabled = true;
        });
    };

    useEffect(() => {
        getForm();
    }, []);

    useEffect(() => {
        if (values.Months == "6Months") {
            setFieldValue("twelveMonthsPayment", "");
        } else {
            setFieldValue("sixMonthPayment", "");
        }
    }, [values.Months]);

    const handleSignatureEnd = () => {
        setFieldValue("signature", sigRef.current.toDataURL("image/png"));
    };
    const clearSignature = () => {
        sigRef.current.clear();
        setFieldValue("signature", "");
    };

    return (
        <div>
            <div className="mb-4">
                <p className="navyblueColor font-30 font-w-500 mt-4"> {t("insurance.insuraceDetailCandidate")}</p>
            </div>
            <form className="row" onSubmit={handleNextPrev}>
                <section className="col-xl">
                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.fN")}
                                </label>
                                <input
                                    type="text"
                                    name={"canFirstName"}
                                    id="canFirstName"
                                    className="form-control"
                                    value={values.canFirstName}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    readOnly
                                />
                                <span className="text-danger">
                                    {touched.canFirstName && errors.canFirstName}
                                </span>
                            </div>
                        </div>
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.LN")}
                                </label>
                                <input
                                    type="text"
                                    name={"canLastName"}
                                    id="canLastName"
                                    className="form-control"
                                    value={values.canLastName}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    readOnly
                                />
                                <span className="text-danger">
                                    {touched.canLastName && errors.canLastName}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.Passport")}
                                </label>
                                <input
                                    type="text"
                                    id="canPassport"
                                    name={"canPassport"}
                                    className="form-control"
                                    value={values.canPassport}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                />
                                <span className="text-danger">
                                    {touched.canPassport && errors.canPassport}
                                </span>
                            </div>
                        </div>
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.Origin")}
                                </label>
                                <input
                                    type="text"
                                    name={"canOrigin"}
                                    id={"canOrigin"}
                                    className="form-control"
                                    value={values.canOrigin}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                />
                                <span className="text-danger">
                                    {touched.canOrigin && errors.canOrigin}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.DOB")}
                                </label>
                                <input
                                    type="date"
                                    name={"canDOB"}
                                    id="canDOB"
                                    className="form-control"
                                    value={values.canDOB}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                />
                                <span className="text-danger">
                                    {touched.canDOB && errors.canDOB}
                                </span>
                            </div>
                        </div>
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.FirstDateIns")}
                                </label>
                                <input
                                    type="date"
                                    name={"canFirstDateOfIns"}
                                    id="canFirstDateOfIns"
                                    className="form-control"
                                    value={values.canFirstDateOfIns}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                />
                                <span className="text-danger">
                                    {touched.canFirstDateOfIns &&
                                        errors.canFirstDateOfIns}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.zipCode")}
                                </label>
                                <input
                                    type="text"
                                    name={"canZipcode"}
                                    id="canZipcode"
                                    className="form-control"
                                    value={values.canZipcode}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                />
                                {/* <span className="text-danger">
                                    {touched.canZipcode && errors.canZipcode}
                                </span> */}
                            </div>
                        </div>
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.Town")}
                                </label>
                                <input
                                    type="text"
                                    name={"canTown"}
                                    id={"canTown"}
                                    className="form-control"
                                    value={values.canTown}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                />
                                <span className="text-danger">
                                    {touched.canTown && errors.canTown}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.HouseNumber")}
                                </label>
                                <input
                                    type="text"
                                    name={"canHouseNo"}
                                    id="canHouseNo"
                                    className="form-control"
                                    value={values.canHouseNo}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                />
                                <span className="text-danger">
                                    {touched.canHouseNo && errors.canHouseNo}
                                </span>
                            </div>
                        </div>
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.Street")}
                                </label>
                                <input
                                    type="text"
                                    name={"canStreet"}
                                    id="canStreet"
                                    className="form-control"
                                    value={values.canStreet}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                />
                                <span className="text-danger">
                                    {touched.canStreet && errors.canStreet}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.Telephone")}
                                </label>
                                <input
                                    type="text"
                                    name={"canTelephone"}
                                    id="canTelephone"
                                    className="form-control"
                                    value={values.canTelephone}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                />
                                <span className="text-danger">
                                    {touched.canTelephone && errors.canTelephone}
                                </span>
                            </div>
                        </div>
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.Cellphone")}
                                </label>
                                <input
                                    type="text"
                                    name={"canCellPhone"}
                                    className="form-control"
                                    value={values.canCellPhone}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    readOnly
                                />
                                <span className="text-danger">
                                    {touched.canCellPhone && errors.canCellPhone}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.Email")}
                                </label>
                                <input
                                    type="text"
                                    name={"canEmail"}
                                    className="form-control"
                                    value={values.canEmail}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    readOnly
                                />
                                <span className="text-danger">
                                    {touched.canEmail && errors.canEmail}
                                </span>
                            </div>
                        </div>
                        <div className="col-sm">
                            <label className="control-label">
                                {t("insurance.Gender")}
                            </label>
                            <div className="d-flex">
                                <Form.Check
                                    label={t("insurance.Male")}
                                    className="mr-2"
                                    name="gender"
                                    value="Male"
                                    checked={values.gender === "Male"}
                                    type="radio"
                                    id={`gender-1`}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    disabled
                                />
                                <Form.Check
                                    label={t("insurance.Female")}
                                    name="gender"
                                    value="Female"
                                    checked={values.gender === "Female"}
                                    type="radio"
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    id={`gender-2`}
                                    disabled
                                />
                            </div>
                            <span className="text-danger">
                                {touched.gender && errors.gender}
                            </span>
                        </div>
                    </div>

                    {/* Section G */}

                    <div
                        className="row justify-content-center my-2"
                        style={{ fontSize: "18px", fontWeight: "bold" }}
                    >
                        {t("insurance.HealthDeclaration")}
                    </div>

                    <div className="row justify-content-center">
                        <div className="col">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.CandidateName")}
                                </label>
                                <input
                                    type="text"
                                    name="GCandidatename"
                                    className="form-control"
                                    value={values.GCandidatename}
                                    onChange={handleChange}
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        className="row justify-content-center my-2"
                        style={{ fontSize: "18px", fontWeight: "bold" }}
                    >
                        {t("insurance.generalQesMwdical")}
                    </div>
                    <div>
                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="d-flex align-items-center control-label">
                                        <div className="mr-2">
                                            {t("insurance.Height")}
                                        </div>
                                        <div className="d-flex flex-column">
                                            <input
                                                type="text"
                                                name="height"
                                                className="form-control"
                                                value={values.g1Height}
                                                onChange={(e) =>
                                                    setFieldValue(
                                                        "g1Height",
                                                        e.target.value
                                                    )
                                                }
                                            />{" "}
                                            <span className="text-danger">
                                                {touched.g1Height && errors.g1Height}
                                            </span>
                                        </div>
                                        <div
                                            style={{
                                                whiteSpace: "nowrap",
                                                margin: "0 5px",
                                            }}
                                        >
                                            {t("insurance.andWidth")}
                                        </div>
                                        <div className="d-flex flex-column">
                                            <input
                                                type="text"
                                                name="width"
                                                className="form-control"
                                                value={values.g1Weight}
                                                onChange={(e) =>
                                                    setFieldValue(
                                                        "g1Weight",
                                                        e.target.value
                                                    )
                                                }
                                            />
                                            <span className="text-danger">
                                                {touched.g1Weight && errors.g1Weight}
                                            </span>
                                        </div>
                                    </label>
                                </div>
                                <hr />
                            </div>
                        </div>
                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.isthereChangeInWeight")}
                                    </label>
                                    <div id="g2">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                checked={values.g2 === "yes"}
                                                name="g2yn"
                                                id="g2yes"
                                                onChange={(e) =>
                                                    setFieldValue("g2", e.target.value)
                                                }
                                                value="yes"
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g2yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g2yn"
                                                checked={values.g2 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g2", e.target.value)
                                                }
                                                id="g2no"
                                                value="no"
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g2no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g2} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.alcohol")}
                                    </label>
                                    <div id="g3">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                checked={values.g3 === "yes"}
                                                name="g3yn"
                                                id="g3es"
                                                onChange={(e) =>
                                                    setFieldValue("g3", e.target.value)
                                                }
                                                value="yes"
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g3yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g3yn"
                                                checked={values.g3 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g3", e.target.value)
                                                }
                                                id="g3no"
                                                value="no"
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g3no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g3} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.smoke")}
                                    </label>
                                    <div id="g4">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g4y2"
                                                id="g4t"
                                                checked={values.g4Today === "yes"}
                                                onChange={(e) => {
                                                    setFieldValue("g4Today", "yes");
                                                    setFieldValue("g4Past", "no");
                                                }}
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g4t"
                                            >
                                                {t("insurance.Today")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g4yn2"
                                                id="g4p"
                                                checked={values.g4Today === "no"}
                                                onChange={(e) => {
                                                    setFieldValue("g4Today", "no");
                                                    setFieldValue("g4Past", "yes");
                                                }}
                                                value="yes"
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g4p"
                                            >
                                                {t("insurance.InThePast")}
                                            </label>
                                        </div>
                                        <div className="d-flex flex-column">
                                            <div
                                                style={{
                                                    whiteSpace: "nowrap",
                                                }}
                                                className="mr-4"
                                            >
                                                {t("insurance.whenStop")}
                                            </div>
                                            <input
                                                type="text"
                                                name="g4stop"
                                                className="form-control"
                                                value={values.g4WhenStop}
                                                onChange={(e) =>
                                                    setFieldValue(
                                                        "g4WhenStop",
                                                        e.target.value
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                checked={values.g4 === "yes"}
                                                name="g4yn"
                                                id="g4yes"
                                                onChange={(e) =>
                                                    setFieldValue("g4", e.target.value)
                                                }
                                                value="yes"
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g4yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g4yn"
                                                checked={values.g4 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g4", e.target.value)
                                                }
                                                id="g4no"
                                                value="no"
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g4no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g4} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.drugs")}
                                    </label>
                                    <div id="g5">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g5"
                                                id="g5yes"
                                                value="yes"
                                                checked={values.g5 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g5", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g5yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g5"
                                                id="g5no"
                                                value="no"
                                                checked={values.g5 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g5", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g5no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g5} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.surgery")}
                                    </label>
                                    <div id="g6">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g6"
                                                id="g6yes"
                                                value="yes"
                                                checked={values.g6 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g6", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g6yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g6"
                                                id="g6no"
                                                value="no"
                                                checked={values.g6 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g6", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g6no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g6} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.hospitalized")}
                                    </label>
                                    <div> {t("insurance.hospitalizedReason")}</div>
                                    <div className="d-flex align-items-center">
                                        <input
                                            type="text"
                                            name="g7text"
                                            className="form-control"
                                            value={values.g7Reason}
                                            onChange={(e) =>
                                                setFieldValue(
                                                    "g7Reason",
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </div>
                                    <div id="g7">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g7"
                                                id="g7yes"
                                                value="yes"
                                                checked={values.g7 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g7", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g7yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g7"
                                                id="g7no"
                                                value="no"
                                                checked={values.g7 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g7", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g7no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g7} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.chronicCondition")}
                                    </label>
                                    <div id="g8">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g8"
                                                id="g8yes"
                                                value="yes"
                                                checked={values.g8 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g8", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g8yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g8"
                                                id="g8no"
                                                value="no"
                                                checked={values.g8 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g8", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g8no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g8} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.DidAnyTest")}
                                    </label>
                                    <div id="g9">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g9"
                                                id="g9yes"
                                                value="yes"
                                                checked={values.g9 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g9", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g9yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g9"
                                                id="g9no"
                                                value="no"
                                                checked={values.g9 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g9", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g9no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g9} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>
                    </div>
                </section>
                <section className="col-xl">
                    <div
                        className="col mb-4 pl-0"
                        style={{ fontSize: "18px", fontWeight: "bold" }}
                    >
                        {t("insurance.Qestiondiseases")}
                    </div>

                    <div id="g10">
                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.heartAndBlood")}
                                    </label>
                                    <div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g10"
                                                id="g10yes"
                                                value="yes"
                                                checked={values.g10 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g10", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g10yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g10"
                                                id="g10no"
                                                value="no"
                                                checked={values.g10 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g10", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g10no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g10} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.nervousSys")}
                                    </label>
                                    <div id="g11">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g11"
                                                id="g11yes"
                                                value="yes"
                                                checked={values.g11 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g11", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g11yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g11"
                                                id="g11no"
                                                value="no"
                                                checked={values.g11 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g11", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g11no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g11} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Respiratory")}
                                    </label>
                                    <div id="g12">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g12"
                                                id="g12yes"
                                                value="yes"
                                                checked={values.g12 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g12", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g12yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g12"
                                                id="g12no"
                                                value="no"
                                                checked={values.g12 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g12", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g12no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g12} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Respiratory")}
                                    </label>
                                    <div id="g13">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g13"
                                                id="g13yes"
                                                value="yes"
                                                checked={values.g13 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g13", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g13yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g13"
                                                id="g13no"
                                                value="no"
                                                checked={values.g13 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g13", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g13no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g13} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Gastrointestinal")}
                                    </label>
                                    <div id="g14">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g14"
                                                id="g14yes"
                                                value="yes"
                                                checked={values.g14 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g14", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g14yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g14"
                                                id="g14no"
                                                value="no"
                                                checked={values.g14 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g14", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g14no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g14} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Kidneys")}
                                    </label>
                                    <div id="g15">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g15"
                                                id="g15yes"
                                                value="yes"
                                                checked={values.g15 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g15", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g15yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g15"
                                                id="g15no"
                                                value="no"
                                                checked={values.g15 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g15", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g15no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g15} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Metabolic")}
                                    </label>
                                    <div id="g16">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g16"
                                                id="g16yes"
                                                value="yes"
                                                checked={values.g16 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g16", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g16yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g16"
                                                id="g16no"
                                                value="no"
                                                checked={values.g16 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g16", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g16no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g16} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Dermatology")}
                                    </label>
                                    <div id="g17">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g17"
                                                id="g17yes"
                                                value="yes"
                                                checked={values.g17 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g17", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g17yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g17"
                                                id="g17no"
                                                value="no"
                                                checked={values.g17 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g17", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g17no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g17} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Malignant")}
                                    </label>
                                    <div className="d-flex align-items-center">
                                        <input
                                            type="text"
                                            name="g7text"
                                            className="form-control"
                                            value={values.g18Treatment}
                                            onChange={(e) =>
                                                setFieldValue(
                                                    "g18Treatment",
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="">{t("insurance.pathology")}</div>
                                    <div id="g18">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g18"
                                                id="g18yes"
                                                value="yes"
                                                checked={values.g18 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g18", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g18yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g18"
                                                id="g18no"
                                                value="no"
                                                checked={values.g18 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g18", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g18no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g18} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Infectious")}
                                    </label>
                                    <div id="g19">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g19"
                                                id="g19yes"
                                                value="yes"
                                                checked={values.g19 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g19", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g19yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g19"
                                                id="g19no"
                                                value="no"
                                                checked={values.g19 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g19", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g19no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g19} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.JointsAndfBone")}
                                    </label>
                                    <div id="g20">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g20"
                                                id="g20yes"
                                                value="yes"
                                                checked={values.g20 == "yes"}
                                                onChange={(e) => {
                                                    setFieldValue(
                                                        "g20",
                                                        e.target.value
                                                    );
                                                }}
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g20yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g20"
                                                id="g20no"
                                                value="no"
                                                checked={values.g20 == "no"}
                                                onChange={(e) => {
                                                    setFieldValue(
                                                        "g20",
                                                        e.target.value
                                                    );
                                                }}
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g20no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g20} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.eyesCataract")}
                                    </label>
                                    <div id="g21">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g21"
                                                id="g21-yes"
                                                value="yes"
                                                checked={values.g21 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g21", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g21-yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g21"
                                                id="g21-no"
                                                value="no"
                                                checked={values.g21 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g21", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g21-no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g21} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Otolaryngology")}
                                    </label>
                                    <div id="g22">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g22"
                                                id="g22-yes"
                                                value="yes"
                                                checked={values.g22 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g22", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g22-yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g22"
                                                id="g22-no"
                                                value="no"
                                                checked={values.g22 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g22", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g22-no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g22} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.Hernia")}
                                    </label>
                                    <div id="g23">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g23"
                                                id="g23-yes"
                                                value="yes"
                                                checked={values.g23 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g23", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g23-yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g23"
                                                id="g23-no"
                                                value="no"
                                                checked={values.g23 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g23", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g23-no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g23} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                            </div>
                        </div>

                        <div className="row justify-content-center">
                            <div className="col">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("insurance.womenOnly")}
                                    </label>
                                    <div className="d-flex align-items-center">
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={values.g24Treatment}
                                            onChange={(e) =>
                                                setFieldValue(
                                                    "g24Treatment",
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </div>
                                    <div>{t("insurance.Caesarean")}</div>
                                    <div id="g24">
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g24"
                                                id="g24-yes"
                                                value="yes"
                                                checked={values.g24 === "yes"}
                                                onChange={(e) =>
                                                    setFieldValue("g24", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g24-yes"
                                            >
                                                {t("insurance.Yes")}
                                            </label>
                                        </div>
                                        <div className="form-check form-check-inline">
                                            <input
                                                className="form-check-input"
                                                type="radio"
                                                name="g24"
                                                id="g24-no"
                                                value="no"
                                                checked={values.g24 === "no"}
                                                onChange={(e) =>
                                                    setFieldValue("g24", e.target.value)
                                                }
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="g24-no"
                                            >
                                                {t("insurance.No")}
                                            </label>
                                        </div>
                                    </div>
                                    <span className="text-danger">
                                        {touched && errors.g24} {/* Display error if touched and there's an error */}
                                    </span>
                                </div>
                                <hr />
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("insurance.positive_finding")}
                                        </label>
                                        <div>
                                            <textarea
                                                type="text"
                                                name="details"
                                                id="details"
                                                value={values.details}
                                                onChange={handleChange}
                                                className="form-control"
                                                onBlur={handleBlur}
                                                placeholder={t("insurance.enter_required")}
                                            />
                                            <span className="text-danger">
                                                {touched && errors.details} {/* Display error if touched and there's an error */}
                                            </span>
                                        </div>

                                    </div>
                                </div>
                                <hr />
                            </div>
                        </div>
                    </div>

                    {/* LAST section */}
                    <div
                        className="row justify-content-center my-2"
                        style={{ fontSize: "18px", fontWeight: "bold" }}
                    >
                        {t("insurance.signCanidate")}
                    </div>

                    <div className="row justify-content-center">
                        <div className="col-sm">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("insurance.Date")}
                                </label>
                                <input
                                    type="date"
                                    name="canDate"
                                    className="form-control"
                                    value={values.canDate}
                                    onChange={handleChange}
                                    readOnly
                                />
                            </div>
                        </div>
                        <div className="col-sm">
                            <label className="control-label">
                                {t("insurance.Signature")}
                            </label>
                            <div className="d-lg-flex align-items-center">
                                {formValues && formValues.signature ? (
                                    <img src={formValues.signature} />
                                ) : (
                                    <>
                                        <div className="d-flex flex-column" id="signature">
                                            <SignatureCanvas
                                                penColor="black"
                                                canvasProps={{
                                                    width: 250,
                                                    height: 100,
                                                    className:
                                                        "sign101 border mt-1 bg-white",
                                                }}
                                                ref={sigRef}
                                                onEnd={handleSignatureEnd}
                                            />
                                            <span className="text-danger">
                                                {touched.signature && errors.signature}
                                            </span>
                                        </div>
                                        <p className="mt-5 mx-2">
                                            <button
                                                className="btn navyblue mb-2 px-3 py-1"
                                                onClick={clearSignature}
                                            >
                                                {t("form101.button_clear")}
                                            </button>
                                        </p>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Buttons */}
                    <div>
                        <div className="row justify-content-center mt-4">
                            <div className="col d-flex justify-content-end">
                                <button
                                    type="button"
                                    className="btn navyblue align-content-center mx-2"
                                    onClick={() => setNextStep(prev => prev - 1)}
                                >
                                    <GrFormPreviousLink /> {t("common.prev")}
                                </button>
                                {!isSubmitted && (
                                    <>
                                        <button
                                            type="button"
                                            className="btn navyblue"
                                            onClick={handleShow}
                                            disabled={isSubmitting}
                                        >
                                            {t("insurance.Preview")}
                                        </button>
                                    </>
                                )}
                                <div className="mx-2"></div>
                                <button
                                    type="submit"
                                    className="btn navyblue"
                                // disabled={isSubmitting}
                                >
                                    {t("common.next")} <GrFormNextLink />
                                </button>
                            </div>
                        </div>

                        <Modal
                            dialogClassName="pdf-dialog"
                            style={{
                                width: "auto",
                                maxWidth: "max-content !important",
                            }}
                            show={show}
                            onHide={handleClose}
                        >
                            <Modal.Header closeButton>
                                <Modal.Title>{t("insurance.Preview")}</Modal.Title>
                            </Modal.Header>
                            <Modal.Body>
                                {!!pdfData && <PdfViewer url={pdfData} />}
                            </Modal.Body>
                            <Modal.Footer>
                                <Button variant="secondary" onClick={handleClose}>
                                    {t("insurance.Close")}
                                </Button>
                            </Modal.Footer>
                        </Modal>
                    </div>
                </section>
            </form>
            {
                <FullPageLoader visible={loading} />
            }
        </div>
    );
};

function PdfViewer({ url }) {
    const [numPages, setNumPages] = useState();
    const [pageNumber, setPageNumber] = useState(1);
    const { t } = useTranslation();

    function onDocumentLoadSuccess({ numPages }) {
        setNumPages(numPages);
    }

    return (
        <div>
            <Document file={url} onLoadSuccess={onDocumentLoadSuccess}>
                <Page pageNumber={pageNumber} />
            </Document>
            <div className="d-flex align-items-center justify-content-center my-2">
                <button
                    className="btn btn-primary"
                    type="button"
                    disabled={pageNumber <= 1}
                    onClick={() => setPageNumber(pageNumber - 1)}
                >
                    {t("insurance.Previous")}
                </button>
                <div className="mx-2">
                    {t("insurance.Page")} {pageNumber} of {numPages}
                </div>
                <button
                    className="btn btn-primary"
                    type="button"
                    disabled={pageNumber >= numPages}
                    onClick={() => setPageNumber(pageNumber + 1)}
                >
                    {t("insurance.Next")}
                </button>
            </div>
        </div>
    );
}

export default InsuranceForm;