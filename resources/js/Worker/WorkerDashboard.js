import React, { useEffect, useState } from "react";
import axios from "axios";
import { Link } from "react-router-dom";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";

import WorkerSidebar from "./Layouts/WorkerSidebar";
import ChangeJobStatusModal from "./Components/Modals/ChangeJobStatusModal";

export default function WorkerDashboard() {
    const [pastJobCount, setPastJobCount] = useState(0);
    const [pastHoursCount, setPastHoursCount] = useState(0);
    const [todayJobCount, setTodayJobCount] = useState(0);
    const [upcomingJobCount, setUpcomingJobCount] = useState(0);
    const [approveTomorrowJobs, setApproveTomorrowJobs] = useState([]);
    const [todayJobs, setTodayJobs] = useState([]);
    const [jobComments, setJobComments] = useState([]);
    const [processingJobID, setProcessingJobID] = useState(null);
    const [isOpenChangeJobStatus, setIsOpenChangeJobStatus] = useState(false);
    const [selectedJobStatus, setSelectedJobStatus] = useState("");
    const [isSubmitting, setIsSubmitting] = useState(false);

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
        });
    };

    const getTodayJobs = () => {
        axios.get("/api/jobs/today", { headers }).then((response) => {
            setTodayJobs(response.data.today_jobs);
        });
    };

    const handleApproveJob = async (_jobID) => {
        setProcessingJobID(_jobID);

        axios
            .post(
                `/api/worker/${workerID}/jobs/${_jobID}/approve`,
                {},
                { headers }
            )
            .then((response) => {
                GetDashboardData();
                getTodayJobs();
                alert.success(t("job_approval.success_msg"));
                setProcessingJobID(null);
            })
            .catch((e) => {
                alert.error(e.response.data.message);
                setProcessingJobID(null);
            });
    };

    const handleMarkComplete = (_job, _isRunning) => {
        _isRunning ? stopTimer(_job.id) : "";
        getComments(_job.id);
        setProcessingJobID(_job.id);
        setSelectedJobStatus(_job.status);
        setIsOpenChangeJobStatus(true);
    };

    const handleOpeningTime = (_jobID) => {
        setProcessingJobID(_jobID);

        axios
            .post(
                `/api/job-opening-timestamp`,
                {
                    job_id: _jobID,
                },
                { headers }
            )
            .then((res) => {
                getTodayJobs();
                alert.success(res.data.message);
                setProcessingJobID(null);
            })
            .catch((err) => {
                alert.success(res.data.message);
                setProcessingJobID(null);
            });
    };

    const startTimer = (_jobID) => {
        setIsSubmitting(true);
        axios
            .post(`/api/jobs/${_jobID}/start-time`, {}, { headers })
            .then((res) => {
                getTodayJobs();
                setTimeout(() => {
                    setIsSubmitting(false);
                }, 500);
            });
    };

    const stopTimer = (_jobID) => {
        setIsSubmitting(true);
        axios
            .post(`/api/jobs/${_jobID}/end-time`, {}, { headers })
            .then((res) => {
                getTodayJobs();
                setTimeout(() => {
                    setIsSubmitting(false);
                }, 500);
            });
    };

    const getComments = (_jobID) => {
        axios.get(`/api/jobs/${_jobID}/comments`, { headers }).then((res) => {
            setJobComments(res.data.comments);
        });
    };

    useEffect(() => {
        GetDashboardData();
        getTodayJobs();
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
                        <div className="col-xl-4 col-sm-6 col-xs-6">
                            <Link to={`/worker/jobs?f=past`}>
                                <div className="dashBox">
                                    <div className="dashIcon mr-3">
                                        <i className="fa-solid fa-suitcase font-50"></i>
                                    </div>
                                    <div className="dashText">
                                        <h3>{pastJobCount}</h3>
                                        <p>{t("worker.dashboard.past_jobs")}</p>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <div className="col-xl-4 col-sm-6  col-xs-6">
                            <Link to={`/worker/jobs`}>
                                <div className="dashBox">
                                    <div className="dashIcon mr-3">
                                        <i className="fa-solid fa-clock font-50"></i>
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

                        <div className="col-xl-4 col-sm-6  col-xs-6">
                            <Link to={`/worker/jobs?f=upcoming`}>
                                <div className="dashBox">
                                    <div className="dashIcon mr-3">
                                        <i className="fa-solid fa-suitcase font-50"></i>
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

                        <div className="col-xl-4 col-sm-6  col-xs-6">
                            <Link to={`/worker/jobs?f=today`}>
                                <div className="dashBox">
                                    <div className="dashIcon mr-3">
                                        <i className="fa-solid fa-suitcase font-50"></i>
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
                                                    {t("worker.jobs.address")}
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
                                                    let address =
                                                        item.property_address;

                                                    let address_name =
                                                        address &&
                                                        address.address_name
                                                            ? address.address_name
                                                            : "NA";
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
                                                            <Td>
                                                                {item.property_address ? (
                                                                    <Link
                                                                        to={`https://maps.google.com?q=${item.property_address.geo_address}`}
                                                                        target="_blank"
                                                                    >
                                                                        {
                                                                            address_name
                                                                        }
                                                                    </Link>
                                                                ) : (
                                                                    <>
                                                                        {
                                                                            address_name
                                                                        }
                                                                    </>
                                                                )}
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
                                                                <div className="d-flex mt-4 mt-md-0">
                                                                    <button
                                                                        type="button"
                                                                        className="btn btn-primary"
                                                                        onClick={() =>
                                                                            handleApproveJob(
                                                                                item.id
                                                                            )
                                                                        }
                                                                    >
                                                                        {t(
                                                                            "worker.jobs.view.approve"
                                                                        )}
                                                                    </button>

                                                                    <a
                                                                        href={`/worker/jobs/view/${item.id}`}
                                                                        target="_blank"
                                                                        className="btn btn-warning"
                                                                    >
                                                                        <i className="fa fa-eye"></i>
                                                                    </a>
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
                                        {t("global.no_record_found")}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="latest-users">
                        <h2 className="page-title">
                            {t("worker.dashboard.today_jobs")}
                        </h2>
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {todayJobs.length > 0 ? (
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
                                                    {t(
                                                        "worker.dashboard.shift"
                                                    )}
                                                </Th>
                                                <Th>
                                                    {t("worker.jobs.address")}
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
                                            {todayJobs.map((item, index) => {
                                                const isRunning =
                                                    item.time.filter(
                                                        (i) =>
                                                            i.end_time === null
                                                    ).length > 0;
                                                let address =
                                                    item.property_address;

                                                let address_name =
                                                    address &&
                                                    address.address_name
                                                        ? address.address_name
                                                        : "NA";
                                                return (
                                                    <Tr key={index}>
                                                        <Td>
                                                            {item.client
                                                                ? item.client
                                                                      .firstname +
                                                                  " " +
                                                                  item.client
                                                                      .lastname
                                                                : "NA"}
                                                        </Td>
                                                        <Td>
                                                            {item.jobservice &&
                                                                (w_lng == "en"
                                                                    ? item
                                                                          .jobservice
                                                                          .name
                                                                    : item
                                                                          .jobservice
                                                                          .heb_name)}
                                                        </Td>
                                                        <Td>{item.shifts}</Td>
                                                        <Td>
                                                            {item.property_address ? (
                                                                <Link
                                                                    to={`https://maps.google.com?q=${item.property_address.geo_address}`}
                                                                    target="_blank"
                                                                >
                                                                    {
                                                                        address_name
                                                                    }
                                                                </Link>
                                                            ) : (
                                                                <>
                                                                    {
                                                                        address_name
                                                                    }
                                                                </>
                                                            )}
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
                                                            {item.job_opening_timestamp ===
                                                                null &&
                                                            item.worker_approved_at ===
                                                                null ? (
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        handleApproveJob(
                                                                            item.id
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        processingJobID ==
                                                                        item.id
                                                                    }
                                                                    className="btn btn-primary mr-2 mt-4 mt-md-0"
                                                                >
                                                                    {t(
                                                                        "worker.jobs.view.approve"
                                                                    )}
                                                                </button>
                                                            ) : item.job_opening_timestamp ===
                                                                  null &&
                                                              item.worker_approved_at !==
                                                                  null ? (
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        handleOpeningTime(
                                                                            item.id
                                                                        )
                                                                    }
                                                                    className="btn btn-success mr-2 mt-4 mt-md-0"
                                                                    disabled={
                                                                        processingJobID ==
                                                                        item.id
                                                                    }
                                                                >
                                                                    {t(
                                                                        "worker.jobs.view.onMyWay"
                                                                    )}
                                                                </button>
                                                            ) : (
                                                                <>
                                                                    {item.status !=
                                                                        "completed" &&
                                                                        item.status !=
                                                                            "cancel" && (
                                                                            <button
                                                                                type="button"
                                                                                onClick={() =>
                                                                                    handleMarkComplete(
                                                                                        item,
                                                                                        isRunning
                                                                                    )
                                                                                }
                                                                                disabled={
                                                                                    processingJobID ==
                                                                                    item.id
                                                                                }
                                                                                className="btn btn-success mr-2 mt-4 mt-md-0"
                                                                            >
                                                                                {t(
                                                                                    "worker.jobs.view.completebtn"
                                                                                )}
                                                                            </button>
                                                                        )}
                                                                    {item.status !=
                                                                        "completed" &&
                                                                        item.status !=
                                                                            "cancel" && (
                                                                            <>
                                                                                {!isRunning ? (
                                                                                    <button
                                                                                        disabled={
                                                                                            isSubmitting
                                                                                        }
                                                                                        type="button"
                                                                                        onClick={() =>
                                                                                            startTimer(
                                                                                                item.id
                                                                                            )
                                                                                        }
                                                                                        className="btn btn-primary mr-2 mt-4 mt-md-0"
                                                                                    >
                                                                                        {item
                                                                                            .time
                                                                                            .length >
                                                                                        0
                                                                                            ? t(
                                                                                                  "worker.jobs.view.resbtn"
                                                                                              )
                                                                                            : t(
                                                                                                  "worker.jobs.view.startbtn"
                                                                                              )}
                                                                                    </button>
                                                                                ) : (
                                                                                    <button
                                                                                        disabled={
                                                                                            isSubmitting
                                                                                        }
                                                                                        type="button"
                                                                                        onClick={() =>
                                                                                            stopTimer(
                                                                                                item.id
                                                                                            )
                                                                                        }
                                                                                        className="btn btn-danger dangerous mr-2 mt-4 mt-md-0"
                                                                                    >
                                                                                        {t(
                                                                                            "worker.jobs.view.stopbtn"
                                                                                        )}
                                                                                    </button>
                                                                                )}
                                                                            </>
                                                                        )}
                                                                </>
                                                            )}

                                                            <a
                                                                href={`/worker/jobs/view/${item.id}`}
                                                                target="_blank"
                                                                className="btn btn-warning"
                                                            >
                                                                <i className="fa fa-eye"></i>
                                                            </a>
                                                        </Td>
                                                    </Tr>
                                                );
                                            })}
                                        </Tbody>
                                    </Table>
                                ) : (
                                    <p className="text-center mt-5">
                                        {t("global.no_record_found")}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    {isOpenChangeJobStatus && (
                        <ChangeJobStatusModal
                            allComment={jobComments}
                            jobId={processingJobID}
                            jobStatus={selectedJobStatus}
                            setIsOpen={setIsOpenChangeJobStatus}
                            isOpen={isOpenChangeJobStatus}
                            onSuccess={() => {
                                getTodayJobs();
                                setIsOpenChangeJobStatus(false);
                                setProcessingJobID(null);
                            }}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}
