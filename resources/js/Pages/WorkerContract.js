import React, { useEffect, useRef, useState } from "react";
import { useParams } from "react-router-dom";
import { Base64 } from "js-base64";
import swal from "sweetalert";
import i18next from "i18next";
import { IsrailContact } from "../Admin/Pages/Contract/IsrailContact";
import { NonIsraeliContract } from "../Admin/Pages/Contract/NonIsraeliContract";

export default function WorkerContract() {
    const param = useParams();
    const [workerDetail, setWorkerDetail] = useState({});
    const [workerFormDetail, setWorkerFormDetail] = useState({});
    const [checkFormDetails, setCheckFormDetails] = useState(false);

    const handleSubmit = (values) => {
        const data = {
            worker_id: Base64.decode(param.id),
            worker_contract_json: values,
        };

        axios
            .post(`/api/work-contract`, data)
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
                    let formData = res.data.form;
                    setCheckFormDetails(true);
                    setWorkerFormDetail(formData);
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
                        checkFormDetails={checkFormDetails}
                    />
                ) : (
                    <NonIsraeliContract
                        handleFormSubmit={handleSubmit}
                        workerDetail={workerDetail}
                        workerFormDetails={workerFormDetail}
                        checkFormDetails={checkFormDetails}
                    />
                )
            ) : (
                <h1>Loading</h1>
            )}
        </>
    );
}
