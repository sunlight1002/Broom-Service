import React, { useEffect, useState } from "react";
import axios from "axios";
import { Link } from "react-router-dom";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";

import WorkerSidebar from "./Layouts/WorkerSidebar";

export default function WorkerDashboard() {
    const [pastJobCount, setPastJobCount] = useState(0);
    const [pastHoursCount, setPastHoursCount] = useState(0);
    const [todayJobCount, setTodayJobCount] = useState(0);
    const [upcomingJobCount, setUpcomingJobCount] = useState(0);
    const [approveTomorrowJobs, setApproveTomorrowJobs] = useState([]);
    const [loading, setLoading] = useState("Loading...");

    const alert = useAlert();
    const { t, i18n } = useTranslation();
    const w_lng = i18n.language;

    const workerID = localStorage.getItem("worker-id");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const GetDashboardData = () => {
        axios.get("/api/dashboard", { headers }).then((response) => {
            setPastJobCount(response.data.counts.past_job_count);
            setPastHoursCount(
                parseInt(parseInt(response.data.counts.past_job_minutes) / 60)
            );
            setUpcomingJobCount(response.data.counts.upcoming_job_count);
            setTodayJobCount(response.data.counts.today_job_count);
            setApproveTomorrowJobs(response.data.approval_pending_job);

            if (response.data.approval_pending_job.length === 0) {
                setLoading("No job found");
            }
        });
    };

    const handleApprove = async (_jobID) => {
        axios
            .post(
                `/api/worker/${workerID}/jobs/${_jobID}/approve`,
                {},
                { headers }
            )
            .then((response) => {
                GetDashboardData();
                alert.success(t("job_approval.success_msg"));
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });
    };

    useEffect(() => {
        GetDashboardData();
    }, []);

    return (
        <div id="container">
            <WorkerSidebar />
            <div id="content">
                <div className="adminDash">
                    <div className="titleBox">
                        <h1 className="page-title">
                            {t("worker.sidebar.dashboard")}
                        </h1>
                    </div>
                    <div className="row">
                        <div className="col-sm-4 col-xs-6">
                            <Link to={`/worker/jobs`}>
                                <div className="dashBox">
                                    <div className="dashIcon mr-3">
                                        <i className="fa-solid fa-suitcase"></i>
                                    </div>
                                    <div className="dashText">
                                        <h3>{pastJobCount}</h3>
                                        <p>{t("worker.dashboard.past_jobs")}</p>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <div className="col-sm-4 col-xs-6">
                            <Link to={`/worker/jobs`}>
                                <div className="dashBox">
                                    <div className="dashIcon mr-3">
                                        <i className="fa-solid fa-clock"></i>
                                    </div>
                                    <div className="dashText">
                                        <h3>{pastHoursCount}</h3>
                                        <p>
                                            {t("worker.dashboard.past_hours")}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <div className="col-sm-4 col-xs-6">
                            <Link to={`/worker/jobs`}>
                                <div className="dashBox">
                                    <div className="dashIcon mr-3">
                                        <i className="fa-solid fa-suitcase"></i>
                                    </div>
                                    <div className="dashText">
                                        <h3>{upcomingJobCount}</h3>
                                        <p>
                                            {t(
                                                "worker.dashboard.upcoming_jobs"
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <div className="col-sm-4 col-xs-6">
                            <Link to={`/worker/jobs`}>
                                <div className="dashBox">
                                    <div className="dashIcon mr-3">
                                        <i className="fa-solid fa-suitcase"></i>
                                    </div>
                                    <div className="dashText">
                                        <h3>{todayJobCount}</h3>
                                        <p>
                                            {t("worker.dashboard.today_jobs")}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                    <div className="latest-users">
                        <h2 className="page-title">
                            {t("worker.dashboard.approve_tomorrow_jobs")}
                        </h2>
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {approveTomorrowJobs.length > 0 ? (
                                    <Table className="table table-bordered responsiveTable">
                                        <Thead>
                                            <Tr>
                                                <Th>
                                                    {t(
                                                        "worker.dashboard.client"
                                                    )}
                                                </Th>
                                                <Th>
                                                    {t(
                                                        "worker.dashboard.service"
                                                    )}
                                                </Th>
                                                <Th>
                                                    {t("worker.dashboard.date")}
                                                </Th>
                                                <Th>
                                                    {t(
                                                        "worker.dashboard.shift"
                                                    )}
                                                </Th>
                                                <Th>
                                                    {t(
                                                        "worker.dashboard.status"
                                                    )}
                                                </Th>
                                                <Th>
                                                    {t(
                                                        "worker.dashboard.action"
                                                    )}
                                                </Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {approveTomorrowJobs.map(
                                                (item, index) => {
                                                    return (
                                                        <Tr key={index}>
                                                            <Td>
                                                                {item.client
                                                                    ? item
                                                                          .client
                                                                          .firstname +
                                                                      " " +
                                                                      item
                                                                          .client
                                                                          .lastname
                                                                    : "NA"}
                                                            </Td>
                                                            <Td>
                                                                {item.jobservice &&
                                                                    (w_lng ==
                                                                    "en"
                                                                        ? item
                                                                              .jobservice
                                                                              .name
                                                                        : item
                                                                              .jobservice
                                                                              .heb_name)}
                                                            </Td>
                                                            <Td>
                                                                {
                                                                    item.start_date
                                                                }
                                                            </Td>
                                                            <Td>
                                                                {item.shifts}
                                                            </Td>

                                                            <Td
                                                                style={{
                                                                    textTransform:
                                                                        "capitalize",
                                                                }}
                                                            >
                                                                {item.status}
                                                            </Td>
                                                            <Td>
                                                                <div className="d-flex">
                                                                    <button
                                                                        type="button"
                                                                        className="btn btn-primary"
                                                                        onClick={() =>
                                                                            handleApprove(
                                                                                item.id
                                                                            )
                                                                        }
                                                                    >
                                                                        {t(
                                                                            "worker.jobs.view.approve"
                                                                        )}
                                                                    </button>
                                                                </div>
                                                            </Td>
                                                        </Tr>
                                                    );
                                                }
                                            )}
                                        </Tbody>
                                    </Table>
                                ) : (
                                    <p className="text-center mt-5">
                                        {loading}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
