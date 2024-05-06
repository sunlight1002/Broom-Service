import React, { useState, useEffect } from "react";
import CurrentJob from "./CurrentJob";
import PastJob from "./PastJob";
import WorkerAvailabilty from "./WorkerAvailabilty";
import WorkerNotAvailabilty from "./WorkerNotAvailabilty";
import Document from "../Documents/Document";
import WorkerForms from "./WorkerForms";

export default function WorkerHistory({ worker }) {
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
                        Worker Availability
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
                        Current Job
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
                        Past Job
                    </a>
                </li>
                {worker.country === "Israel" &&
                    worker.company_type === "my-company" && (
                        <li className="nav-item" role="presentation">
                            <a
                                id="worker-forms"
                                className="nav-link"
                                data-toggle="tab"
                                href="#tab-worker-forms"
                                aria-selected="false"
                                role="tab"
                            >
                                Forms
                            </a>
                        </li>
                    )}
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
                        Documents
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
                {worker.country === "Israel" &&
                    worker.company_type === "my-company" && (
                        <div
                            id="tab-worker-forms"
                            className="tab-pane"
                            role="tab-panel"
                            aria-labelledby="worker-forms"
                        >
                            <WorkerForms worker={worker} />
                        </div>
                    )}
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
                    <Document worker={worker} />
                </div>
            </div>
        </div>
    );
}
