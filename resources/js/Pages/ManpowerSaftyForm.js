import axios from "axios";
import { useFormik } from "formik";
import html2pdf from "html2pdf.js";
import i18next from "i18next";
import { Base64 } from "js-base64";
import moment from "moment";
import React, { useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import { useParams } from "react-router-dom";
import SignatureCanvas from "react-signature-canvas";
import * as yup from "yup";
import companySign from '../Assets/image/company-sign.png';
import { objectToFormData } from "../Utils/common.utils";

const ManpowerSaftyForm = () => {
    const sigRef = useRef();
    const param = useParams();
    const { t } = useTranslation();

    const id = Base64.decode(param.id);

    const alert = useAlert();
    const [formValues, setFormValues] = useState("");
    const [isSubmitted, setIsSubmitted] = useState(false);
    const [isGeneratingPDF, setIsGeneratingPDF] = useState(false);
    const contentRef = useRef(null);
    const [date, setDate] = useState(moment().format("DD-MM-YYYY"));

    const initialValues = {
        workerName: "",
        workerName2: "",
        signature: "",
        passport: "",
        idNumber: "",
        address: "",
        manpower_company_name: "",
        date: date,
    };

    const formSchema = yup.object({
        signature: yup.mixed().required(t("safeAndGear.errorMsg")),
    });

    const clearSignature = () => {
        sigRef.current.clear();
        setFieldValue("signature", "");
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
    } = useFormik({
        initialValues,
        validationSchema: formSchema,
        onSubmit: async (values) => {
            setIsGeneratingPDF(true);
            const options = {
                filename: "my-document.pdf",
                margin: [5, 5, 0, 5],
                image: { type: "jpeg", quality: 0.98 },
                html2canvas: { scale: 1.5 },
                jsPDF: {
                    unit: "mm",
                    format: "a4",
                    orientation: "portrait",
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };

            const content = contentRef.current;

            const _pdf = await html2pdf()
                .set(options)
                .from(content)
                .outputPdf("blob", "Manpower_safty.pdf");

            setIsGeneratingPDF(false);

            // Convert JSON object to FormData
            let formData = objectToFormData(values);
            formData.append("pdf_file", _pdf);

            axios
                .post(`/api/${id}/manpower-form`, formData, {
                    headers: {
                        Accept: "application/json, text/plain, */*",
                        "Content-Type": "multipart/form-data",
                    },
                })
                .then((res) => {
                    alert.success("Form submitted successfully");
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

    useEffect(() => {
        axios.get(`/api/getManpowerSafteyForm/${id}`).then((res) => {

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

            if (res.data.worker) {
                setFieldValue("workerName", res.data.worker.firstname);
                setFieldValue("workerName2", res.data.worker.lastname);
                setFieldValue("address", res.data.worker.address);
                setFieldValue("manpower_company_name", res.data.manpower_company_name);
                if (res.data.worker.country == "israel") {
                    setFieldValue("idNumber", res.data.worker.worker_id);
                } else {
                    setFieldValue("passport", res.data.worker.passport);
                }

            }

            if (res.data.form) {
                setFormValues(res.data.form.data);
                setFieldValue("workerName", res.data.form.data.workerName);
                setFieldValue("workerName2", res.data.form.data.workerName2);
                setFieldValue("signature", res.data.form.data.signature);
                setFieldValue("date", res.data.form.data.date);
                setFieldValue("address", res.data.form.data.address);
                setFieldValue("manpower_company_name", res.data.form.data.manpower_company_name);

                if (res.data.form.submitted_at) {
                    disableInputs();
                    setIsSubmitted(true);
                }
            }
        });
    }, []);


    const disableInputs = () => {
        // Disable inputs within the div with the id "targetDiv"
        const inputs = document.querySelectorAll(".targetDiv input ");
        inputs.forEach((input) => {
            input.disabled = true;
        });
    };


    return (
        <div id="container" className="targetDiv rtlcon pdf-container" ref={contentRef}>
            <div id="content" style={{ paddingLeft: "25px" }}>
                <div className="mx-5 mt-4">
                    <div className="text-center no-break">
                        <p className="mb-4 badge badge-primary" style={{ fontSize: "25px" }}>
                            <strong>{t("safeAndGear.welcomeToBroom")}</strong>
                        </p>
                    </div>

                    <div className="text-left no-break">
                        <p className="mb-2" style={{ fontSize: "17px" }}>
                            <strong>{t("manpower_safty_form.title")}</strong><br />
                            <strong>{t("manpower_safty_form.date", { date: values.date })}</strong><br />
                        </p>
                    </div>


                    <div className="text-left no-break">
                        <p className="mb-4" style={{ fontSize: "17px" }}>
                            <strong>{t("manpower_safty_form.to")}</strong><br />
                            {t("manpower_safty_form.salutation")}<br />
                        </p>
                    </div>

                    <div className="text-left no-break">
                        <p className="mb-4" style={{ fontSize: "17px" }}>
                            <strong>{t("manpower_safty_form.subject")}</strong>{t("manpower_safty_form.subject_title")}<br />
                            {t("manpower_safty_form.sub_title", {
                                full_name: values.workerName + " " + values.workerName2,
                                number: values.passport ? values.passport : values.idNumber,
                                address: values.address ?? "",
                            })}<br />
                        </p>
                    </div>

                    <ol className="mt-3 lh-lg no-break" style={{ fontSize: "16px" }}>
                        <li>{t("manpower_safty_form.ms1", { company_name: values.manpower_company_name })}</li>
                        <li>{t("manpower_safty_form.ms2")}</li>
                        <li>{t("manpower_safty_form.ms3")}</li>
                        <li>{t("manpower_safty_form.ms4")}</li>
                        <li>{t("manpower_safty_form.ms5")}</li>
                        <li>{t("manpower_safty_form.ms6")}</li>
                        <li>{t("manpower_safty_form.ms7")}</li>
                    </ol>

                    <ol start={8} className="lh-lg " style={{ fontSize: "16px" }}>
                        <li>{t("manpower_safty_form.ms8")}</li>
                        <li>{t("manpower_safty_form.ms9")}</li>
                    </ol>


                    <div className="mt-5">
                        <form className="mb-5" onSubmit={handleSubmit}>
                            <div className="mt-3" style={{ fontSize: "16px" }}>
                                <div className="gap-5 d-flex justify-content-between">
                                    <div className="d-flex flex-column">
                                        <strong className="mb-2">{t("manpower_safty_form.sincerely")}</strong>
                                        <strong>{t("manpower_safty_form.the_worker")} {t("manpower_safty_form.signature")}</strong>
                                    </div>
                                    {/* <div className="col-md-6 col-12 mt-3 mt-md-0"> */}
                                    {formValues.signature &&
                                        formValues.signature != null ? (
                                        <img src={formValues.signature} />
                                    ) : (
                                        <div>
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
                                                    {touched.signature && errors.signature}
                                                </span>
                                            </div>

                                            {!isGeneratingPDF && (
                                                <div className="d-block mt-1">
                                                    <button
                                                        type="button"
                                                        className="btn btn-warning mb-2"
                                                        onClick={
                                                            clearSignature
                                                        }
                                                    >
                                                        {t(
                                                            "safeAndGear.Clear"
                                                        )}
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                    {/* </div> */}
                                </div>
                                <hr />

                                <div className="d-flex flex-column">
                                    <strong className="mb-2">{t("manpower_safty_form.employee")}</strong>
                                    <strong>{t("manpower_safty_form.confirmation")}</strong>
                                </div>
                                <div className="text-left">
                                    <p className="mb-4" style={{ fontSize: "17px" }}>
                                        {t("manpower_safty_form.confirm_subject", {
                                            worker_name: values.workerName + " " + values.workerName2, number: values.passport ? values.passport : values.idNumber,
                                            number: values.passport ? values.passport : values.idNumber
                                        })}<br />
                                    </p>
                                </div>
                                <div className="gap-5 d-flex justify-content-between">
                                    <div className="d-flex flex-column">
                                        <strong className="mb-2">{t("manpower_safty_form.name")}</strong>
                                        <strong>{t("manpower_safty_form.signature")}</strong>
                                    </div>
                                    <img src={companySign} />
                                </div>
                            </div>
                            {!isSubmitted && !isGeneratingPDF && (
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="btn btn-success"
                                >
                                    {t("safeAndGear.Accept")}
                                </button>
                            )}
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ManpowerSaftyForm;
