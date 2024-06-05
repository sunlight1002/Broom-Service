import React from "react";
import { useTranslation } from "react-i18next";

import WorkerMyAccount from "../../Auth/WorkerMyAccount";
import Documents from "../../Auth/Documents";
import WorkerSidebar from "../../Layouts/WorkerSidebar";
import Forms from "../../Auth/Forms";

export default function MyAccount() {
    const { t } = useTranslation();
    return (
        <div id="container">
            <WorkerSidebar />
            <div id="content">
                <div className="view-applicant mt-3">
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
                                <h5 className="" style={{ fontSize: "14px" }}>
                                    {t("worker.settings.edit_account")}
                                </h5>
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
                                <h5 style={{ fontSize: "14px" }}>
                                    {t("worker.settings.manage_form")}
                                </h5>
                            </a>
                        </li>
                        <li className="nav-item" role="presentation">
                            <a
                                id="forms"
                                className="nav-link"
                                data-toggle="tab"
                                href="#tab-forms"
                                aria-selected="true"
                                role="tab"
                            >
                                <h5 style={{ fontSize: "14px" }}>
                                    {t("worker.settings.forms")}
                                </h5>
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
                            <WorkerMyAccount />
                        </div>
                        <div
                            id="tab-current-job"
                            className="tab-pane"
                            role="tab-panel"
                            aria-labelledby="current-job"
                        >
                            <Documents />
                        </div>
                        <div
                            id="tab-forms"
                            className="tab-pane"
                            role="tab-panel"
                            aria-labelledby="current-job"
                        >
                            <Forms />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
