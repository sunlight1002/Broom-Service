import React, { useEffect, useState } from "react";
import axios from "axios";
import { Link } from "react-router-dom";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useTranslation } from "react-i18next";

import WorkerSidebar from "./Layouts/WorkerSidebar";

export default function WorkerDashboard() {
    const [totalJobs, setTotalJobs] = useState([0]);
    const [latestJobs, setlatestJobs] = useState([]);
    const [loading, setLoading] = useState("Loading...");
    const { t, i18n } = useTranslation();
    const w_lng = i18n.language;

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const GetDashboardData = () => {
        axios.get("/api/dashboard", { headers }).then((response) => {
            setTotalJobs(response.data.total_jobs);
            if (response.data.latest_jobs.length > 0) {
                setlatestJobs(response.data.latest_jobs);
            } else {
                setLoading("No job found");
            }
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
                        <div className="col-sm-3 col-xs-6">
                            <Link to={`/worker/jobs`}>
                                <div className="dashBox">
                                    <div className="dashIcon mr-3">
                                        <i className="fa-solid fa-suitcase"></i>
                                    </div>
                                    <div className="dashText">
                                        <h3>{totalJobs}</h3>
                                        <p>{t("worker.sidebar.jobs")}</p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                    <div className="latest-users">
                        <h2 className="page-title">
                            {t("worker.dashboard.upcoming_jobs")}
                        </h2>
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {latestJobs.length > 0 ? (
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
                                            {latestJobs.map((item, index) => {
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
                                                        <Td>
                                                            {item.start_date}
                                                        </Td>
                                                        <Td>{item.shifts}</Td>

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
                                                                <Link
                                                                    to={`/worker/view-job/${item.id}`}
                                                                    className="ml-auto ml-md-2 btn bg-yellow"
                                                                >
                                                                    <i className="fa fa-eye"></i>
                                                                </Link>
                                                            </div>
                                                        </Td>
                                                    </Tr>
                                                );
                                            })}
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
