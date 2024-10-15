import React, { useState, useEffect } from "react";
import CurrentJob from "./CurrentJob";
import PastJob from "./PastJob";
import WorkerAvailabilty from "./WorkerAvailabilty";
import WorkerNotAvailabilty from "./WorkerNotAvailabilty";
import WorkerAdvance from "./WorkerAdvance";
import Document from "../Documents/Document";
import WorkerForms from "./WorkerForms";
import WorkerTermination from "./WorkerTermination";
import { useTranslation } from "react-i18next";

export default function WorkerHistory({ worker, getWorkerDetails }) {

    const {t} = useTranslation()
    const [days, setDays] = useState([]);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getTime = () => {
        axios.get(`/api/admin/get-time`, { headers }).then((res) => {
            if (res.data.data) {
                let ar = JSON.parse(res.data.data.days);
                setDays(ar);
            }
        });
    };

    useEffect(() => {
        getTime();
    }, []);

    return (
        <div className="ClientHistory">
            <ul className="nav nav-tabs" role="tablist">
                <li className="nav-item" role="presentation">
                    <a
                        id="worker-availability"
                        className="nav-link active"
                        data-toggle="tab"
                        href="#tab-worker-availability"
                        aria-selected="true"
                        role="tab"
                    >
                        {t("client.jobs.change.worker_availability")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="current-job"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-current-job"
                        aria-selected="true"
                        role="tab"
                    >
                       {t("worker.jobs.current_jobs")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="past-job"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-past-job"
                        aria-selected="false"
                        role="tab"
                    >
                        {t("worker.dashboard.past_jobs")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="worker-forms"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-worker-forms"
                        aria-selected="false"
                        role="tab"
                    >
                        {t("worker.settings.forms")}
                    </a>
                </li>
                {/* <li className="nav-item" role="presentation">
                    <a
                        id="worker-not-availability"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-worker-not-availability"
                        aria-selected="false"
                        role="tab"
                    >
                        Not Available Date
                    </a>
                </li> */}
                <li className="nav-item" role="presentation">
                    <a
                        id="worker-documents"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-worker-documents"
                        aria-selected="false"
                        role="tab"
                    >
                        {t("worker.settings.manage_form")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="worker-loans"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-worker-loans"
                        aria-selected="false"
                        role="tab"
                    >
                        {t("worker.settings.advance")}
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a
                        id="worker-termination"
                        className="nav-link"
                        data-toggle="tab"
                        href="#tab-worker-termination"
                        aria-selected="false"
                        role="tab"
                    >
                        {t("worker.settings.termination")}
                    </a>
                </li>
            </ul>
            <div className="tab-content" style={{ background: "#fff" }}>
                <div
                    id="tab-worker-availability"
                    className="tab-pane active show"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    <WorkerAvailabilty days={days} />
                </div>
                <div
                    id="tab-current-job"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="current-job"
                >
                    <CurrentJob />
                </div>
                <div
                    id="tab-past-job"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="past-job"
                >
                    <PastJob />
                </div>
                <div
                    id="tab-worker-forms"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="worker-forms"
                >
                    <WorkerForms
                        worker={worker}
                        getWorkerDetails={getWorkerDetails}
                    />
                </div>
                {/* <div
                    id="tab-worker-not-availability"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="doucments"
                >
                    <WorkerNotAvailabilty />
                </div> */}
                <div
                    id="tab-worker-documents"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="forms"
                >
                    <Document worker={worker}  getWorkerDetails={getWorkerDetails}/>
                </div>
                <div
                    id="tab-worker-loans"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="worker-loans"
                >
                    <WorkerAdvance worker={worker} />
                </div>
                <div
                    id="tab-worker-termination"
                    className="tab-pane"
                    role="tab-panel"
                    aria-labelledby="worker-termination"
                >
                    <WorkerTermination worker={worker} getWorkerDetails={getWorkerDetails}/>
                </div>
            </div>
        </div>
    );
}
