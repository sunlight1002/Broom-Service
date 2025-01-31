// safeAndGear

import { useFormik } from "formik";
import html2pdf from "html2pdf.js";
import i18next from "i18next";
import { Base64 } from "js-base64";
import React, { useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import { GrFormNextLink, GrFormPreviousLink } from "react-icons/gr";
import { useNavigate, useParams } from "react-router-dom";
import SignatureCanvas from "react-signature-canvas";
import * as yup from "yup";
import { objectToFormData } from "../../../Utils/common.utils";
import swal from "sweetalert";
import FullPageLoader from "../../../Components/common/FullPageLoader";


const SafeAndGear = ({
    nextStep,
    setNextStep,
    handleBubbleToggle,
    activeBubble,
    isManpower,
    type
}) => {
    const sigRef = useRef();
    const param = useParams();
    const { t } = useTranslation();
    const navigate = useNavigate();

    const id = Base64.decode(param.id);
    const alert = useAlert();
    const [formValues, setFormValues] = useState("");
    const [workerName, setWorkerName] = useState("");
    const [workerName2, setWorkerName2] = useState("");
    const [signature, setSignature] = useState("");
    const [isSubmitted, setIsSubmitted] = useState(false);
    const [isGeneratingPDF, setIsGeneratingPDF] = useState(false);
    const [savingType, setSavingType] = useState("submit");
    const [is_existing_worker, setIs_existing_worker] = useState(0)
    const [country, setCountry] = useState("")
    const [loading, setLoading] = useState(false)

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
            if (!isSubmitted) {
                setIsGeneratingPDF(true);
                const options = {
                    filename: "my-document.pdf",
                    margin: [5, 5, 0, 5],
                    image: { type: "jpeg", quality: 0.98 },
                    html2canvas: { scale: 2 },
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
                    .outputPdf("blob", "Safety-And-Gear.pdf");

                setIsGeneratingPDF(false); // Reset PDF mode

                // Convert JSON object to FormData
                let formData = objectToFormData(values);
                formData.append("pdf_file", _pdf);
                formData.append("savingType", savingType);
                formData.append("step", nextStep);
                formData.append("type", type == "lead" ? "lead" : "worker");

                setLoading(true);

                axios
                    .post(`/api/${id}/safegear`, formData, {
                        headers: {
                            Accept: "application/json, text/plain, */*",
                            "Content-Type": "multipart/form-data",
                        },
                    })
                    .then((res) => {
                        setIsSubmitted(true); // Mark as submitted
                        if (!isManpower) {
                            setNextStep(prev => prev + 1);
                        } else {
                            swal(t("swal.forms_submitted"), "", "success");
                            if (type === "lead" && res?.data?.id) {
                                navigate(`/worker-forms/${Base64.encode(res?.data?.id.toString())}`);
                            }
                            setTimeout(() => {
                                window.location.reload(true);
                            }, 2000);
                        }

                        setLoading(false);
                    })
                    .catch((e) => {
                        setLoading(false);
                        if (e?.response?.data?.message === 'Safety and gear already signed.' && !isManpower) {
                            setNextStep(prev => prev + 1);
                        }

                    });
            } else if (!isManpower) {
                setNextStep(prev => prev + 1);
            }
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
        axios.get(`/api/getSafegear/${id}/${type}`).then((res) => {
            i18next.changeLanguage(res.data.lng);
            if (res.data.lng == "heb") {
                import("../../../Assets/css/rtl.css");
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
                setIs_existing_worker(res.data.worker.is_existing_worker)
                setCountry(res.data.worker.country)
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


    const handleSaveAsDraft = async (e) => {
        e.preventDefault();
        handleSubmit();
    };


    return (
        <div id="container" className={`pdf-wrapper targetDiv rtlcon ${isGeneratingPDF ? "pdf-layout" : ""}`} ref={contentRef}>
            <p className="navyblueColor font-34 mt-4 font-w-500">{t("safeAndGear.title")}</p>
            <form onSubmit={handleSaveAsDraft}>
                <div className={`${isGeneratingPDF ? "" : "row"}`}>
                    <section className="col-xl">
                        <div className="">
                            <p className="mb-4 mt-2" style={{ fontSize: "17px" }}>
                                {t("safeAndGear.broomIntro")}
                            </p>
                        </div>
                        <div className="mt-3 lh-lg " style={{ fontSize: "16px" }}>
                            <div className="d-flex mt-2 navublueColor"><span>1.&nbsp;</span><p>{t("safeAndGear.sfg1")}</p></div>
                            <div className="d-flex mt-2 navublueColor"><span>2.&nbsp;</span><p>{t("safeAndGear.sfg2")}</p></div>
                            <div className="d-flex mt-2 navublueColor"><span>3.&nbsp;</span><p>{t("safeAndGear.sfg3")}</p></div>
                            <div className="d-flex mt-2 navublueColor"><span>4.&nbsp;</span><p>{t("safeAndGear.sfg4")}</p></div>
                            <div className="d-flex mt-2 navublueColor"><span>5.&nbsp;</span><p>{t("safeAndGear.sfg5")}</p></div>
                            <div className="d-flex mt-2 navublueColor"><span>6.&nbsp;</span><p>{t("safeAndGear.sfg6")}</p></div>
                            <div className="d-flex mt-2 navublueColor"><span>7.&nbsp;</span><p>{t("safeAndGear.sfg7")}</p></div>
                            <div className="d-flex mt-2 navublueColor"><span>8.&nbsp;</span><p>{t("safeAndGear.sfg8")}</p></div>

                        </div>
                        <div className="mt-5" style={{ marginBottom: "130px" }}>
                            <div className="">
                                <h5>
                                    <strong>
                                        {t("safeAndGear.safeAndGearProcedure")}
                                    </strong>
                                </h5>
                            </div>
                            <div
                                className="mt-4 lh-lg "
                                style={{ fontSize: "16px" }}
                            >
                                <div className="d-flex mt-2 navublueColor"><span>1.&nbsp;</span><p>{t("safeAndGear.sp1")}</p></div>
                                <div className="d-flex mt-2 navublueColor"><span>2.&nbsp;</span><p>{t("safeAndGear.sp2")}</p></div>
                                <div className="d-flex mt-2 navublueColor"><span>3.&nbsp;</span><p>{t("safeAndGear.sp3")}</p></div>
                                <div className="d-flex mt-2 navublueColor"><span>4.&nbsp;</span><p>{t("safeAndGear.sp4")}</p></div>
                                <div className="d-flex mt-2 navublueColor"><span>5.&nbsp;</span><p>{t("safeAndGear.sp5")}</p></div>
                                <div className="d-flex mt-2 navublueColor"><span>6.&nbsp;</span><p>{t("safeAndGear.sp6")}</p></div>
                                <div className="d-flex mt-2 navublueColor"><span>7.&nbsp;</span><p>{t("safeAndGear.sp7")}</p></div>
                                <div className="d-flex mt-2 navublueColor"><span>8.&nbsp;</span><p>{t("safeAndGear.sp8")}</p></div>
                                <div className="d-flex mt-2 navublueColor"><span>9.&nbsp;</span><p>{t("safeAndGear.sp9")}</p></div>

                            </div>
                        </div>
                    </section>
                    <section className="col-xl">
                        <div className="" style={{ border: "1px solid #d2d7dd", background: "#eaecef", padding: "35px 25px", borderRadius: "10px" }}>
                            <div className="d-flex">
                                <p className="navyblueColor font-24  font-w-500">{t("safeAndGear.eqList")}</p>
                                <span
                                    className="navyblueColor font-24  font-w-500 ml-3"
                                >
                                    {values.workerName +
                                        " " +
                                        values.workerName2}
                                </span>
                            </div>
                            <div className="mt-4" style={{ fontSize: "16px" }}>
                                <div className="d-flex mt-2 navublueColor">
                                    <span>1.&nbsp;</span>
                                    <p>
                                        {t("safeAndGear.eq1", {
                                            fullname:
                                                values.workerName +
                                                " " +
                                                values.workerName2,
                                        })}
                                    </p>
                                </div>
                                <div className="d-flex mt-2 navublueColor">
                                    <span>2.&nbsp;</span>
                                    <p>{t("safeAndGear.eq2")}</p>
                                </div>
                                <div className="d-flex mt-2 navublueColor">
                                    <span>3.&nbsp;</span>
                                    <p>{t("safeAndGear.eq3")}</p>
                                </div>
                                <div className="d-flex mt-2 navublueColor">
                                    <span>4.&nbsp;</span>
                                    <p>{t("safeAndGear.eq4")}</p>
                                </div>
                                <div className="d-flex mt-2 navublueColor">
                                    <span>5.&nbsp;</span>
                                    <p>{t("safeAndGear.eq5")}</p>
                                </div>
                                <div className="d-flex mt-2 navublueColor">
                                    <span>6.&nbsp;</span>
                                    <p>{t("safeAndGear.eq6")}</p>
                                </div>
                                <div className="row">
                                    {/* Worker Name Section */}
                                    <div className="col-12 col-md-4 d-flex justify-content-center align-items-center mb-3 mb-md-0">
                                        <span className="navyblueColor font-w-500 text-center">
                                            {values.workerName + " " + values.workerName2}
                                        </span>
                                    </div>

                                    {/* Signature Section */}
                                    <div className="col-12 col-md-4 mt-3 d-flex flex-column align-items-center">
                                        <p className="text-center mb-2">
                                            <strong>{t("safeAndGear.sign")}</strong>
                                        </p>
                                        {formValues && formValues.signature != null ? (
                                            <img
                                                src={formValues.signature}
                                                alt="Signature"
                                                style={{ maxWidth: "100%", height: "auto" }}
                                            />
                                        ) : (
                                            <div className="w-100 d-flex justify-content-center">
                                                <SignatureCanvas
                                                    penColor="black"
                                                    canvasProps={{
                                                        width: 300,
                                                        height: 150,
                                                        className: `sign101 mt-1 form-control ${touched.signature && errors.signature && 'is-invalid'}`,
                                                        style: { background: "#f1f1f1", width: "310px" }
                                                    }}
                                                    ref={sigRef}
                                                    onEnd={handleSignatureEnd}
                                                />
                                            </div>
                                        )}
                                        <span className="text-danger mt-2">
                                            {touched.signature && errors.signature}
                                        </span>
                                    </div>

                                    {/* Clear Signature Button */}
                                    <div className="col-12 col-md-4 d-flex justify-content-center align-items-center mt-3 mt-md-0">
                                        {!isGeneratingPDF && !isSubmitted && (
                                            <button
                                                type="button"
                                                className="btn navyblue px-4"
                                                onClick={clearSignature}
                                            >
                                                {t("safeAndGear.Clear")}
                                            </button>
                                        )}
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div className={`d-flex justify-content-end ${isGeneratingPDF ? "hide-in-pdf" : ""}`} style={{ margin: "20px 10px" }}>
                            {nextStep !== 1 && (
                                <button
                                    type="button"
                                    onClick={(e) => setNextStep(prev => prev - 1)}
                                    className="navyblue py-2 px-4 mr-2"
                                    name="prev"
                                    style={{ borderRadius: "5px" }}
                                >
                                    <GrFormPreviousLink /> {t("common.prev")}
                                </button>
                            )}
                            {nextStep < 6 && (!isManpower) ? (
                                <button
                                    type="submit"
                                    name="next"
                                    disabled={isManpower ? isSubmitted : false}
                                    className="navyblue py-2 px-4"
                                    style={{ borderRadius: "5px" }}
                                >
                                    {t("common.next")} <GrFormNextLink />
                                </button>
                            ) : isManpower && !isSubmitted ? (
                                <button
                                    type="submit"
                                    name="next"
                                    className="navyblue py-2 px-4"
                                    style={{ borderRadius: "5px" }}
                                >
                                    {t("common.submit")}
                                </button>
                            ) : null}
                        </div>
                    </section>
                </div>
            </form>
            {isManpower && (
                <FullPageLoader visible={loading} />
            )}
        </div>
    );
};

export default SafeAndGear;
