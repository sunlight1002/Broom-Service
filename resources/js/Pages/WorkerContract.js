//WorkerContract

import html2pdf from "html2pdf.js";
import i18next from "i18next";
import { Base64 } from "js-base64";
import React, { useEffect, useRef, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import swal from "sweetalert";

import { IsrailContact } from "../Admin/Pages/Contract/IsrailContact";
import { NonIsraeliContract } from "../Admin/Pages/Contract/NonIsraeliContract";
import { objectToFormData } from "../Utils/common.utils";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../Components/common/FullPageLoader";

export default function WorkerContract({
    nextStep,
    setNextStep,
    worker,
    handleBubbleToggle,
    activeBubble,
    type
}) {
    const param = useParams();
    const navigate = useNavigate();
    const [workerDetail, setWorkerDetail] = useState({});
    const [workerFormDetail, setWorkerFormDetail] = useState(null);
    const [isSubmitted, setIsSubmitted] = useState(false);
    const [isGeneratingPDF, setIsGeneratingPDF] = useState(false);
    const [loading, setLoading] = useState(false)
    const [savingType, setSavingType] = useState("draft");
    const { t } = useTranslation();

    const contentRef = useRef(null);
    useEffect(() => {
        window.scroll(0, 0);
    }, [nextStep])

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = async (values) => {
        if (!isSubmitted) {
            setIsGeneratingPDF(true);
            
            // Ensure contentRef is set
            if (!contentRef.current) {
                console.error("contentRef is not set properly.");
                setIsGeneratingPDF(false);
                return;
            }
    
            const options = {
                filename: "my-document.pdf",
                margin: [2, 5, 0, 5],  // [TOP, LEFT, BOTTOM, RIGHT]
                image: { type: "jpeg", quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: {
                    unit: "mm",
                    format: "a4",
                    orientation: "portrait",
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
    
            try {
                const content = contentRef.current;
    
                // Debugging: Check if contentRef exists
                console.log("Generating PDF from:", content);
    
                const _pdf = await html2pdf().set(options).from(content).outputPdf("blob");
    
                const pdfFile = new File([_pdf], "Contract.pdf", { type: "application/pdf" });
    
                // Convert JSON object to FormData
                let formData = objectToFormData(values);
                formData.append("pdf_file", pdfFile);
                formData.append("step", nextStep);
                formData.append("savingType", savingType);
                formData.append("type", type === "lead" ? "lead" : "worker");
    
                setIsGeneratingPDF(false);
                setLoading(true);
                
                axios
                    .post(`/api/${Base64.decode(param.id)}/work-contract`, formData, {
                        headers: {
                            Accept: "application/json, text/plain, */*",
                            "Content-Type": "multipart/form-data",
                        },
                    })
                    .then((res) => {
                        if (worker.country === "Israel" && savingType === "submit") {
                            setIsSubmitted(true);
                            swal(t('swal.forms_submitted'), "", "success");
                            if (type === "lead" && res?.data?.id) {
                                navigate(`/worker-forms/${Base64.encode(res?.data?.id.toString())}`);
                            }
                            setTimeout(() => {
                                window.location.reload(true);
                            }, 2000);
                        } else if (worker.country !== "Israel" && savingType === "submit") {
                            setIsSubmitted(true);
                            setNextStep(prev => prev + 1);
                        } else if (savingType === "draft") {
                            setNextStep(prev => prev + 1);
                        }
                        setLoading(false);
                    })
                    .catch((e) => {
                        console.error("Error submitting form:", e);
                        if (worker.country === "Israel") {
                            swal(t('swal.forms_submitted'), "", "success");
                        }
                        if (e.response?.data?.message === "Contract already signed") {
                            setNextStep(prev => prev + 1);
                        }
                        setLoading(false);
                    });
            } catch (error) {
                console.error("PDF Generation Error:", error);
                setIsGeneratingPDF(false);
            }
        } else {
            setNextStep(prev => prev + 1);
        }
    };
    

    const getWorker = () => {
        axios
            .post(`/api/worker-detail`,
                { worker_id: Base64.decode(param.id), type: type }, 
                { headers: {
                    Accept: "application/json, text/plain, */*",
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${localStorage.getItem("admin-token")}`,
                } }
            ).then((res) => {
                if (res.data.worker) {
                    let w = res.data.worker;

                    setWorkerDetail(w);
                    i18next.changeLanguage(w.lng);
                    if (w.lng == "heb") {
                        import("../Assets/css/rtl.css");
                        document
                            .querySelector("html")
                            .setAttribute("dir", "rtl");
                    } else {
                        document.querySelector("html").removeAttribute("dir");
                        const rtlLink = document.querySelector('link[href*="rtl.css"]');
                        if (rtlLink) {
                            rtlLink.remove();
                        }
                    }
                }
                if (res.data.form) {
                    let formData = res.data.form.data;
                    setWorkerFormDetail(formData);

                    if (res.data.form.submitted_at) {
                        setIsSubmitted(true);
                    }
                }
            });
    };

    useEffect(() => {
        getWorker();
    }, []);

    return (
        <>
            {workerDetail ? (
                workerDetail.country === "Israel" ? (
                    <IsrailContact
                        handleFormSubmit={handleSubmit}
                        workerDetail={workerDetail}
                        workerFormDetails={workerFormDetail}
                        isSubmitted={isSubmitted}
                        isGeneratingPDF={isGeneratingPDF}
                        contentRef={contentRef}
                        nextStep={nextStep}
                        setNextStep={setNextStep}
                        savingType={savingType}
                        setSavingType={setSavingType}
                    />
                ) : (
                    <NonIsraeliContract
                        handleFormSubmit={handleSubmit}
                        workerDetail={workerDetail}
                        workerFormDetails={workerFormDetail}
                        isSubmitted={isSubmitted}
                        isGeneratingPDF={isGeneratingPDF}
                        contentRef={contentRef}
                        nextStep={nextStep}
                        setNextStep={setNextStep}
                        savingType={savingType}
                        setSavingType={setSavingType}
                    />
                )
            ) : (
                <h1>Loading</h1>
            )}
            {
                worker.country === "Israel" && (
                    <FullPageLoader visible={loading} />
                )
            }
        </>
    );
}
