import React, { useEffect, useRef, useState } from "react";
import { useParams } from "react-router-dom";
import { Base64 } from "js-base64";
import swal from "sweetalert";
import i18next from "i18next";
import html2pdf from "html2pdf.js";

import { IsrailContact } from "../Admin/Pages/Contract/IsrailContact";
import { NonIsraeliContract } from "../Admin/Pages/Contract/NonIsraeliContract";
import { objectToFormData } from "../Utils/common.utils";

export default function WorkerContract() {
    const param = useParams();
    const [workerDetail, setWorkerDetail] = useState({});
    const [workerFormDetail, setWorkerFormDetail] = useState(null);
    const [isSubmitted, setIsSubmitted] = useState(false);
    const [isGeneratingPDF, setIsGeneratingPDF] = useState(false);

    const contentRef = useRef(null);

    const handleSubmit = async (values) => {
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
            .outputPdf("blob", "Contract.pdf");

        setIsGeneratingPDF(false);

        // Convert JSON object to FormData
        let formData = objectToFormData(values);
        formData.append("pdf_file", _pdf);

        axios
            .post(`/api/${Base64.decode(param.id)}/work-contract`, formData, {
                headers: {
                    Accept: "application/json, text/plain, */*",
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                swal(res.data.message, "", "success");
                setTimeout(() => {
                    window.location.href = "/worker/login";
                }, 1000);
            })
            .catch((e) => {
                swal("Error!", e.response.data.message, "error");
            });
    };

    const getWorker = () => {
        axios
            .post(`/api/worker-detail`, { worker_id: Base64.decode(param.id) })
            .then((res) => {
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
                    />
                ) : (
                    <NonIsraeliContract
                        handleFormSubmit={handleSubmit}
                        workerDetail={workerDetail}
                        workerFormDetails={workerFormDetail}
                        isSubmitted={isSubmitted}
                        isGeneratingPDF={isGeneratingPDF}
                        contentRef={contentRef}
                    />
                )
            ) : (
                <h1>Loading</h1>
            )}
        </>
    );
}
