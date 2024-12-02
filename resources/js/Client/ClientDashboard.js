import React, { useEffect, useState } from "react";
import ClientSidebar from "./Layouts/ClientSidebar";
import axios from "axios";
import { Link } from "react-router-dom";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";
import { BsSuitcaseLg } from "react-icons/bs";
import { FaRegBookmark } from "react-icons/fa";
import { FaArrowsToEye } from "react-icons/fa6";
import { getShiftsDetails } from "../Utils/common.utils";


export default function ClientDashboard() {
    const [totalJobs, setTotalJobs] = useState([0]);
    const [totalOffers, setTotalOffers] = useState([0]);
    const [latestJobs, setlatestJobs] = useState([]);
    const [loading, setLoading] = useState("Loading...");
    const { t, i18n } = useTranslation();
    const c_lng = i18n.language;
    const headers = {
        Accept: "application/json, text/plain, /",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const GetDashboardData = () => {
        axios.get("/api/client/dashboard", { headers }).then((response) => {
            setTotalJobs(response.data.total_jobs);
            setTotalOffers(response.data.total_offers);
            if (response.data.latest_jobs.length > 0) {
                setlatestJobs(response.data.latest_jobs);
            } else {
                setLoading(t("global.no_record_found"));
            }
        });
    };

    useEffect(() => {
        GetDashboardData();
    }, []);

    return (
        <div id="container">
            <ClientSidebar />
            <div id="content">
                <div className="adminDash andClient">
                    <div className="titleBox">
                        <h1 className="page-title">
                            {t("client.sidebar.dashboard")}
                        </h1>
                    </div>
                    <div className="row">
                        <div className="col-xl-3 col-sm-6 col-xs-6">
                            <Link to="/client/jobs">
                                <div className="dashBox">
                                    <div className="dashIcon mr-4">
                                        <i className=""><BsSuitcaseLg className="font-50" /></i>
                                    </div>
                                    <div className="dashText">
                                        <h3>{totalJobs}</h3>
                                        <p>{t("client.common.services")}</p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                        <div className="col-xl-3 col-sm-6 col-xs-6">
                            <Link to="/client/offered-price">
                                <div className="dashBox">
                                    <div className="dashIcon mr-4">
                                        <i className=""><FaRegBookmark className="font-50" /></i>
                                    </div>
                                    <div className="dashText">
                                        <h3>{totalOffers}</h3>
                                        <p>{t("client.common.offers")}</p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                    <div className="latest-users">
                        <h2 className="page-title">
                            {t("client.dashboard.upcoming_services")}
                        </h2>
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {latestJobs.length > 0 ? (
                                    <Table className="table table-no-borders responsiveTable">
                                        <Thead>
                                            <Tr>
                                                <Th>
                                                    {t("client.dashboard.service")}
                                                </Th>
                                                <Th>
                                                    {t("client.dashboard.date")}
                                                </Th>
                                                <Th>
                                                    {t("client.dashboard.shift")}
                                                </Th>
                                                <Th style={{ display: "none" }}>
                                                    {t("client.dashboard.assigned_worker")}
                                                </Th>
                                                <Th>
                                                    {t("client.dashboard.status")}
                                                </Th>
                                                <Th>
                                                    {t("client.dashboard.total")}
                                                </Th>
                                                <Th>
                                                    {t("client.dashboard.action")}
                                                </Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {latestJobs.map((item, index) => {
                                                let status = item.status;
                                                if (status === "not-started") {
                                                    status = t("j_status.not-started");
                                                }
                                                if (status === "progress") {
                                                    status = t("j_status.progress");
                                                }
                                                if (status === "completed") {
                                                    status = t("j_status.completed");
                                                }
                                                if (status === "scheduled") {
                                                    status = t("j_status.scheduled");
                                                }
                                                if (status === "unscheduled") {
                                                    status = t("j_status.unscheduled");
                                                }
                                                if (status === "re-scheduled") {
                                                    status = t("j_status.re-scheduled");
                                                }
                                                if (status === "cancel") {
                                                    status = t("j_status.cancel");
                                                }
                                                const { durationInHours, startTime, endTime } = getShiftsDetails(item);
                                                
                                                return (
                                                    <Tr key={index}>
                                                        <Td>
                                                            {item.jobservice &&
                                                                (c_lng === "en"
                                                                    ? item.jobservice.name
                                                                    : item.jobservice.heb_name)}
                                                        </Td>
                                                        <Td>{item.start_date}</Td>
                                                        <Td>{startTime}-{endTime}</Td>
                                                        <Td style={{ display: "none" }}>
                                                            {item.worker
                                                                ? item.worker.firstname + " " + item.worker.lastname
                                                                : "NA"}
                                                        </Td>
                                                        <Td className="d-flex align-items-center">
                                                            <span className="td-status mr-2">{status}</span>
                                                            {item.status === "cancel" &&
                                                                ` (with cancellation fees of ${item.cancellation_fee_amount} ${t("global.currency")} )`}
                                                        </Td>
                                                        <Td>
                                                            {item.jobservice &&
                                                                item.jobservice.total + " " + t("global.currency")}
                                                        </Td>
                                                        <Td>
                                                            <div className="d-flex">
                                                                <Link
                                                                    to={`/client/jobs/view/${Base64.encode(item.id.toString())}`}
                                                                    className=""
                                                                    style={{background: "#E5EBF1", color: "#2F4054", padding: "5px 7px 0px", borderRadius: "5px"}}
                                                                >
                                                                    <i className="fa fa-eye font-18"></i>
                                                                </Link>
                                                            </div>
                                                        </Td>
                                                    </Tr>
                                                );
                                            })}
                                        </Tbody>
                                    </Table>
                                ) : (
                                    <p className="text-center mt-5">{loading}</p>
                                )}
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}