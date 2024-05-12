import { useFormik } from "formik";
import React, { useEffect, useRef, useState } from "react";
import * as yup from "yup";
import { Base64 } from "js-base64";
import { useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import i18next from "i18next";
import SignatureCanvas from "react-signature-canvas";
import html2pdf from "html2pdf.js";
import { objectToFormData } from "../../../Utils/common.utils";
import { useTranslation } from "react-i18next";

const SafeAndGear = () => {
    const sigRef = useRef();
    const param = useParams();
    const { t } = useTranslation();

    const id = Base64.decode(param.id);
    const alert = useAlert();
    const [formValues, setFormValues] = useState("");
    const [workerName, setWorkerName] = useState("");
    const [workerName2, setWorkerName2] = useState("");
    const [signature, setSignature] = useState("");
    const [isSubmitted, setIsSubmitted] = useState(false);
    const [isGeneratingPDF, setIsGeneratingPDF] = useState(false);

    const contentRef = useRef(null);

    const initialValues = {
        workerName: workerName,
        workerName2: workerName2,
        signature: signature,
    };

    const formSchema = yup.object({
        signature: yup.mixed().required(t("safeAndGear.errorMsg")),
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
        initialValues,
        validationSchema: formSchema,
        onSubmit: async (values) => {
            setIsGeneratingPDF(true);
            const options = {
                filename: "my-document.pdf",
                margin: 1,
                image: { type: "jpeg", quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: {
                    unit: "mm",
                    format: "a4",
                    orientation: "portrait",
                },
            };

            const content = contentRef.current;

            const _pdf = await html2pdf()
                .set(options)
                .from(content)
                .outputPdf("blob", "Safety-And-Gear.pdf");

            setIsGeneratingPDF(false);

            // Convert JSON object to FormData
            let formData = objectToFormData(values);
            formData.append("pdf_file", _pdf);

            axios
                .post(`/api/${id}/safegear`, formData, {
                    headers: {
                        Accept: "application/json, text/plain, */*",
                        "Content-Type": "multipart/form-data",
                    },
                })
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
        axios.get(`/api/getSafegear/${id}`).then((res) => {
            i18next.changeLanguage(res.data.lng);
            if (res.data.lng == "heb") {
                import("../../../Assets/css/rtl.css");
                document.querySelector("html").setAttribute("dir", "rtl");
            } else {
                document.querySelector("html").removeAttribute("dir");
            }

            if (res.data.worker) {
                setFieldValue("workerName", res.data.worker.firstname);
                setFieldValue("workerName2", res.data.worker.lastname);
            }

            if (res.data.form) {
                setFormValues(res.data.form.data);
                setFieldValue("workerName", res.data.form.data.workerName);
                setFieldValue("workerName2", res.data.form.data.workerName2);
                setFieldValue("signature", res.data.form.data.signature);

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

    const workerStyle = {
        workerName: {
            width: "100%",
            padding: "8px",
            margin: "0px 0px 15px",
            textAlign: "left",
            fontSize: "18px",
        },
        workerName2: {
            width: "100%",
            padding: "8px",
            margin: "10px 0px 0px 0px",
            fontSize: "18px",
        },
    };
    return (
        <div id="container" className="targetDiv">
            <div id="content">
                <div className="mx-5 mt-5" ref={contentRef}>
                    <div className="text-center">
                        <h5>
                            <strong>{t("safeAndGear.welcomeToBroom")}</strong>
                        </h5>
                    </div>
                    <p className="mt-4" style={{ fontSize: "16px" }}>
                        {t("safeAndGear.sfg1")}
                    </p>
                    <ol className="mt-3 lh-lg " style={{ fontSize: "16px" }}>
                        <li>{t("safeAndGear.sfg2")}</li>
                        <li>{t("safeAndGear.sfg3")}</li>
                        <li>{t("safeAndGear.sfg4")}</li>
                        <li>{t("safeAndGear.sfg5")}</li>
                        <li>{t("safeAndGear.sfg6")}</li>
                        <li>{t("safeAndGear.sfg7")}</li>
                        <li>{t("safeAndGear.sfg8")}</li>
                        <li>{t("safeAndGear.sfg9")}</li>
                    </ol>

                    <div className="mt-5">
                        <div className="text-center">
                            <h5>
                                <strong>
                                    {t("safeAndGear.safeAndGearProcedure")}
                                </strong>
                            </h5>
                        </div>
                        <ol
                            className="mt-4 lh-lg "
                            style={{ fontSize: "16px" }}
                        >
                            <li>{t("safeAndGear.sp1")}</li>
                            <li>{t("safeAndGear.sp2")}</li>
                            <li>{t("safeAndGear.sp3")}</li>
                            <li>{t("safeAndGear.sp4")}</li>
                            <li>{t("safeAndGear.sp5")}</li>
                            <li>{t("safeAndGear.sp6")}</li>
                            <li>{t("safeAndGear.sp7")}</li>
                            <li>{t("safeAndGear.sp8")}</li>
                            <li>{t("safeAndGear.sp9")}</li>
                        </ol>
                    </div>
                    <div className="mt-5">
                        <div className="text-center">
                            <h5>
                                <strong>{t("safeAndGear.eqList")}:</strong>
                            </h5>
                        </div>
                        <div></div>
                        <form onSubmit={handleSubmit}>
                            <div className="mt-4" style={{ fontSize: "16px" }}>
                                <span
                                    className="badge badge-primary"
                                    style={workerStyle.workerName}
                                >
                                    {values.workerName +
                                        " " +
                                        values.workerName2}
                                </span>
                                <p>
                                    I{" "}
                                    {values.workerName +
                                        " " +
                                        values.workerName2}{" "}
                                    {t("safeAndGear.eq1")}
                                </p>
                                <p>{t("safeAndGear.eq1")}</p>
                                <p>{t("safeAndGear.eq2")}</p>
                                <p>{t("safeAndGear.eq3")}</p>
                                <p>{t("safeAndGear.eq4")}</p>
                                <p>{t("safeAndGear.eq5")}</p>
                                <p> {t("safeAndGear.eq6")}</p>
                                <div className="row gap-5">
                                    <div className="col-6">
                                        <span
                                            className="badge badge-primary"
                                            style={workerStyle.workerName2}
                                        >
                                            {values.workerName +
                                                " " +
                                                values.workerName2}
                                        </span>
                                    </div>
                                    <div className="col-6">
                                        <p>
                                            <strong>
                                                {t("safeAndGear.sign")}
                                            </strong>
                                            <span className="text-danger">
                                                {touched.signature &&
                                                    errors.signature}
                                            </span>
                                        </p>
                                        {formValues &&
                                        formValues.signature != null ? (
                                            <img src={formValues.signature} />
                                        ) : (
                                            <div>
                                                <SignatureCanvas
                                                    penColor="black"
                                                    canvasProps={{
                                                        className:
                                                            "sign101 border mt-1",
                                                    }}
                                                    ref={sigRef}
                                                    onEnd={handleSignatureEnd}
                                                />

                                                {!isGeneratingPDF && (
                                                    <div className="d-block">
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
                                    </div>
                                </div>
                            </div>
                            {!isSubmitted && !isGeneratingPDF && (
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="btn btn-success"
                                >
                                    {t("safeAndGear.Submit")}
                                </button>
                            )}
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SafeAndGear;
