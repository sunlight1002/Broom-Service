import axios from "axios";
import React, { useEffect, useState, useMemo } from "react";
import { useParams } from "react-router-dom";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { Base64 } from "js-base64";
import Swal from "sweetalert2";
import { useAlert } from "react-alert";

import logo from "../Assets/image/sample.svg";
import { getShiftsDetails } from "../Utils/common.utils";

export default function WorkerJobDetails() {
    const { t } = useTranslation();
    const [job, setJob] = useState(null);
    const param = useParams();

    const alert = useAlert();

    const approveWorkerJob = () => {
        axios
            .post(
                `/api/guest/${Base64.decode(param.wid)}/jobs/${Base64.decode(
                    param.jid
                )}/approve`
            )
            .then((response) => {
                alert.success(t("job_approval.success_msg"));
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });
    };

    const getWorkerJob = () => {
        axios
            .post(
                `/api/worker/${Base64.decode(param.wid)}/jobs/${Base64.decode(
                    param.jid
                )}`
            )
            .then((response) => {
                i18next.changeLanguage(response.data.data.worker.lng);

                if (response.data.data.worker.lng == "heb") {
                    import("../Assets/css/rtl.css");
                    document.querySelector("html").setAttribute("dir", "rtl");
                } else {
                    document.querySelector("html").removeAttribute("dir");
                    const rtlLink = document.querySelector('link[href*="rtl.css"]');
                    if (rtlLink) {
                        rtlLink.remove();
                    }
                }

                if (!response.data.data.worker_approved_at) {
                    approveWorkerJob();
                }

                setJob(response.data.data);
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });
    };

    useEffect(() => {
        getWorkerJob();
    }, []);

    const dt = useMemo(() => {
        if (job) {
            return Moment(job.start_date).format("DD-MM-Y");
        }

        return "-";
    }, [job]);

    const {durationInHours, startTime, endTime} = getShiftsDetails(job)

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
                {job && (
                    <>
                        <h1>{t("worker.jobs.job_details")}</h1>
                        <ul className="list-unstyled">
                            <li>
                                {t("worker.jobs.date")}: <span>{dt}</span>
                            </li>
                            <li>
                                {t("worker.jobs.client")}:{" "}
                                <span>
                                    {job.client.firstname} {job.client.lastname}
                                </span>
                            </li>
                            <li>
                                {t("worker.jobs.service")}:{" "}
                                <span>{job.jobservice.name}</span>
                            </li>
                            <li>
                                {t("worker.jobs.property")}:{" "}
                                <span>{job.property_address.geo_address}</span>
                            </li>
                            <li>
                                {t("worker.jobs.shift")}:{" "}
                                <span>{startTime} - {endTime}</span>
                            </li>
                        </ul>
                    </>
                )}
            </div>
        </div>
    );
}
