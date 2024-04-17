import React, { useState, useEffect } from "react";
import { useParams } from "react-router-dom";
import { Base64 } from "js-base64";
import swal from "sweetalert";
import i18next from "i18next";
import { IsrailContact } from "../Admin/Pages/Contract/IsrailContact";
import { NonIsraeliContract } from "../Admin/Pages/Contract/NonIsraeliContract";

export default function WorkerContract() {
    const param = useParams();
    const [workerDetail, setWorkerDetail] = useState({});

    const handleAccept = (values) => {
        const data = {
            worker_id: Base64.decode(param.id),
            worker_contract_json: values,
        };

        axios
            .post(`/api/work-contract`, data)
            .then((res) => {
                if (res.data.error) {
                    swal("", res.data.error, "error");
                } else {
                    swal(res.data.message, "", "success");
                    setTimeout(() => {
                        window.location.href = "/worker/login";
                    }, 1000);
                }
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
                        handleFormSubmit={handleAccept}
                        workerDetail={workerDetail}
                    />
                ) : (
                    <NonIsraeliContract
                        handleFormSubmit={handleAccept}
                        workerDetail={workerDetail}
                    />
                )
            ) : (
                <h1>Loading</h1>
            )}
        </>
    );
}
