import axios from "axios";
import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import logo from "../Assets/image/sample.svg";
import { Link } from "react-router-dom";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";
import { useAlert } from "react-alert";
import { Base64 } from "js-base64";

export default function WorkerForm() {
    const param = useParams();
    const alert = useAlert();
    const { t } = useTranslation();
    const encodedWorkerId = param.id;
    const formArr = {
        form101Form: {
            name: "formTxt.form101Form",
            isFilled: false,
            url: `/form101/${encodedWorkerId}`,
        },
        contractForm: {
            name: "formTxt.contractForm",
            isFilled: false,
            url: `/worker-contract/${encodedWorkerId}`,
        },
        saftyAndGearForm: {
            name: "formTxt.saftyAndGear",
            isFilled: false,
            url: `/worker-safe-gear/${encodedWorkerId}`,
        },
        insuranceForm: {
            name: "formTxt.insuranceForm",
            isFilled: false,
            url: `/insurance-form/${encodedWorkerId}`,
        },
    };
    const [worker, setWorker] = useState({});
    const [forms, setForms] = useState(formArr);
    const [isFetched, setIsFetched] = useState(false);

    const getWorker = () => {
        axios
            .get(`/api/worker/${param.id}`)
            .then((res) => {
                const { worker: workData, forms: formData } = res.data;
                setWorker(workData);
                const updatedFormData = {};
                for (const key in { ...forms }) {
                    if (formData.hasOwnProperty(key)) {
                        let _url = forms[key]["url"];
                        if (key == "contractForm") {
                            _url = `/worker-contract/${Base64.encode(
                                workData.worker_id
                            )}`;
                        } else if (key == "form101Form" && formData[key]) {
                            _url = `/form101/${Base64.encode(
                                workData.id.toString()
                            )}/${Base64.encode(
                                formData[key]["id"].toString()
                            )}`;
                        }

                        updatedFormData[key] = {
                            ...forms[key],
                            isFilled:
                                formData[key] && formData[key]["submitted_at"]
                                    ? true
                                    : false,
                            url: _url,
                        };
                    }
                }
                setForms(updatedFormData);
                const lng = workData.lng;
                i18next.changeLanguage(lng);
                if (lng == "heb") {
                    import("../Assets/css/rtl.css");
                    document.querySelector("html").setAttribute("dir", "rtl");
                } else document.querySelector("html").removeAttribute("dir");
                setIsFetched(true);
            })
            .catch((err) => {
                if (err?.response?.data?.message) {
                    alert.error(err.response.data.message);
                }
            });
    };
    useEffect(() => {
        getWorker();
    }, []);

    return (
        <div className="container">
            <div className="thankyou meet-status dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                {!isFetched ? (
                    <Skeleton height={200} />
                ) : (
                    <>
                        <h1>{t("formTxt.workerFormPlural")}</h1>
                        <ul className="list-unstyled">
                            <li>
                                {t("formTxt.hii")},{" "}
                                <span>
                                    {worker.firstname} {worker.lastname}
                                </span>
                            </li>
                            <li>{t("formTxt.greetingTxt")}</li>
                            <li>{t("formTxt.formContent")}</li>
                        </ul>
                    </>
                )}
                {isFetched && (
                    <div className="cta">
                        <div id="content">
                            <div className="row">
                                {Object.keys(forms).map((f) => (
                                    <div className="col" key={f}>
                                        <Link
                                            target="_blank"
                                            className={`btn ${
                                                forms[f].isFilled
                                                    ? "btn-success"
                                                    : "btn-danger"
                                            }`}
                                            to={forms[f].url}
                                        >
                                            {t(`${forms[f].name}`)}
                                        </Link>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
