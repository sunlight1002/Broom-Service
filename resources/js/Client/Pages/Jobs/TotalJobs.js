import React, { useEffect, useState } from "react";
import ReactPaginate from "react-paginate";
import axios from "axios";
import { Link } from "react-router-dom";
import { useAlert } from "react-alert";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";

import Sidebar from "../../Layouts/ClientSidebar";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";

export default function TotalJobs() {
    const [totalJobs, setTotalJobs] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState("Loading...");

    const alert = useAlert();
    const { t, i18n } = useTranslation();
    const c_lng = i18n.language;
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const getJobs = () => {
        axios.post("/api/client/jobs", {}, { headers }).then((response) => {
            if (response.data.jobs.length > 0) {
                setTotalJobs(response.data.jobs);
                setPageCount(response.data.jobs.last_page);
            } else {
                setTotalJobs([]);
                setLoading(t("client.jobs.noJobFound"));
            }
        });
    };

    useEffect(() => {
        getJobs();
    }, []);

    const handlePageClick = async (data) => {
        let currentPage = data.selected + 1;
        axios
            .get("/api/client/jobs?page=" + currentPage, { headers })
            .then((response) => {
                if (response.data.jobs.data.length > 0) {
                    setTotalJobs(response.data.jobs.data);
                    setPageCount(response.data.jobs.last_page);
                } else {
                    setTotalJobs([]);
                    setLoading(t("client.jobs.noJobFound"));
                }
            });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">
                                {t("client.jobs.title")}
                            </h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder={t("client.search")}
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {totalJobs.length > 0 ? (
                                    <Table className="table table-bordered responsiveTable">
                                        <Thead>
                                            <Tr>
                                                <Th scope="col">
                                                    {t("client.jobs.date")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("client.jobs.worker")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("client.jobs.service")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("client.jobs.shift")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("client.jobs.address")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("client.jobs.c_time")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("client.jobs.status")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("client.jobs.total")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("client.jobs.action")}
                                                </Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {totalJobs.map((item, index) => {
                                                let address =
                                                    item.property_address;

                                                let address_name =
                                                    address &&
                                                    address.address_name
                                                        ? address.address_name
                                                        : "NA";
                                                let cords =
                                                    address &&
                                                    address.latitude &&
                                                    address.longitude
                                                        ? address.latitude +
                                                          "," +
                                                          address.longitude
                                                        : "NA";
                                                let status = item.status;
                                                if (status == "not-started") {
                                                    status = t(
                                                        "j_status.not-started"
                                                    );
                                                }
                                                if (status == "progress") {
                                                    status =
                                                        t("j_status.progress");
                                                }
                                                if (status == "completed") {
                                                    status =
                                                        t("j_status.completed");
                                                }
                                                if (status == "scheduled") {
                                                    status =
                                                        t("j_status.scheduled");
                                                }
                                                if (status == "unscheduled") {
                                                    status = t(
                                                        "j_status.unscheduled"
                                                    );
                                                }
                                                if (status == "re-scheduled") {
                                                    status = t(
                                                        "j_status.re-scheduled"
                                                    );
                                                }
                                                if (status == "cancel") {
                                                    status =
                                                        t("j_status.cancel");
                                                }
                                                return (
                                                    <Tr key={index}>
                                                        <Td>
                                                            {Moment(
                                                                item.start_date
                                                            ).format(
                                                                "DD MMM, Y"
                                                            )}
                                                        </Td>
                                                        <Td>
                                                            <h6>
                                                                {item.worker
                                                                    ? item
                                                                          .worker
                                                                          .firstname +
                                                                      " " +
                                                                      item
                                                                          .worker
                                                                          .lastname
                                                                    : "NA"}
                                                            </h6>
                                                        </Td>
                                                        <Td>
                                                            {item.jobservice &&
                                                                (c_lng == "en"
                                                                    ? item
                                                                          .jobservice
                                                                          .name
                                                                    : item
                                                                          .jobservice
                                                                          .heb_name)}
                                                        </Td>
                                                        <Td>{item.shifts}</Td>
                                                        <Td>
                                                            {cords !== "NA" ? (
                                                                <Link
                                                                    to={`https://maps.google.com?q=${cords}`}
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
                                                        <Td>
                                                            {item.jobservice
                                                                ? convertMinsToDecimalHrs(
                                                                      item
                                                                          .jobservice
                                                                          .duration_minutes
                                                                  ) + " Hours"
                                                                : "NA"}
                                                        </Td>
                                                        <Td>
                                                            {status}
                                                            {item.status ==
                                                            "cancel"
                                                                ? ` (${t(
                                                                      "client.jobs.view.with_cancel"
                                                                  )} ${
                                                                      item.cancellation_fee_amount
                                                                  } + ${t(
                                                                      "global.currency"
                                                                  )})`
                                                                : ""}
                                                        </Td>
                                                        <Td>
                                                            {item.jobservice &&
                                                                item.jobservice
                                                                    .total +
                                                                    " " +
                                                                    t(
                                                                        "global.currency"
                                                                    ) +
                                                                    " + " +
                                                                    t(
                                                                        "global.vat"
                                                                    )}
                                                        </Td>
                                                        <Td>
                                                            <div className="action-dropdown dropdown pb-2">
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-default dropdown-toggle"
                                                                    data-toggle="dropdown"
                                                                >
                                                                    <i className="fa fa-ellipsis-vertical"></i>
                                                                </button>
                                                                <div className="dropdown-menu">
                                                                    <Link
                                                                        to={`/client/view-job/${Base64.encode(
                                                                            item.id.toString()
                                                                        )}`}
                                                                        className="dropdown-item"
                                                                    >
                                                                        {t(
                                                                            "client.jobs.view_btn"
                                                                        )}
                                                                    </Link>
                                                                    <Link
                                                                        to={`/client/jobs/${Base64.encode(
                                                                            item.id.toString()
                                                                        )}/change-schedule`}
                                                                        className="dropdown-item"
                                                                    >
                                                                        {t(
                                                                            "client.jobs.change_schedule"
                                                                        )}
                                                                    </Link>
                                                                </div>
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
                                {totalJobs.length > 0 ? (
                                    <ReactPaginate
                                        previousLabel={t("client.previous")}
                                        nextLabel={t("client.next")}
                                        breakLabel={"..."}
                                        pageCount={pageCount}
                                        marginPagesDisplayed={2}
                                        pageRangeDisplayed={3}
                                        onPageChange={handlePageClick}
                                        containerClassName={
                                            "pagination justify-content-end mt-3"
                                        }
                                        pageClassName={"page-item"}
                                        pageLinkClassName={"page-link"}
                                        previousClassName={"page-item"}
                                        previousLinkClassName={"page-link"}
                                        nextClassName={"page-item"}
                                        nextLinkClassName={"page-link"}
                                        breakClassName={"page-item"}
                                        breakLinkClassName={"page-link"}
                                        activeClassName={"active"}
                                    />
                                ) : (
                                    <></>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
