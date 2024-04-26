import React, { useEffect, useState } from "react";
import ReactPaginate from "react-paginate";
import axios from "axios";
import { Link } from "react-router-dom";
import { useAlert } from "react-alert";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useLocation } from "react-router-dom";
import Moment from "moment";
import { useTranslation } from "react-i18next";

import WorkerSidebar from "../../Layouts/WorkerSidebar";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";

export default function WorkerTotalJobs() {
    const [totalJobs, setTotalJobs] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState("Loading...");
    const [filter, setFilter] = useState("");
    const alert = useAlert();
    const location = useLocation();
    const id = localStorage.getItem("worker-id");
    const { t, i18n } = useTranslation();
    const w_lng = i18n.language;
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const getJobs = () => {
        axios.get(`/api/jobs?id=${id}`, { headers }).then((response) => {
            if (response.data.jobs.data.length > 0) {
                setTotalJobs(response.data.jobs.data);
                setPageCount(response.data.jobs.last_page);
            } else {
                setTotalJobs([]);
                setLoading("No Job found");
            }
        });
    };

    useEffect(() => {
        getJobs();
    }, []);

    const handlePageClick = async (data) => {
        let currentPage = data.selected + 1;
        axios
            .get(
                `/api/jobs?id=${id}&page=` + currentPage + "&filter_week=all",
                { headers }
            )
            .then((response) => {
                if (response.data.jobs.data.length > 0) {
                    setTotalJobs(response.data.jobs.data);
                    setPageCount(response.data.jobs.last_page);
                } else {
                    setTotalJobs([]);
                    setLoading("No Job found");
                }
            });
    };

    const filterJobDate = (w) => {
        $("#filter-week").val(w);
        filterJobs1();
    };
    const filterJobs1 = () => {
        let filter_week = $("#filter-week").val();

        axios
            .get(`/api/jobs?id=${id}&filter_week=${filter_week}`, { headers })
            .then((response) => {
                if (response.data.jobs.data.length > 0) {
                    setTotalJobs(response.data.jobs.data);
                    setPageCount(response.data.jobs.last_page);
                } else {
                    setTotalJobs([]);
                    setPageCount(response.data.jobs.last_page);
                    setLoading("No Jobs found");
                }
            });
    };

    return (
        <div id="container">
            <WorkerSidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-2 col-4">
                            <h1 className="page-title">
                                {t("worker.jobs.title")}
                            </h1>
                        </div>

                        <div className="col-sm-7 hidden-xs">
                            <div className="job-buttons">
                                <input type="hidden" id="filter-week" />
                                <button
                                    className="btn btn-info"
                                    onClick={(e) => {
                                        filterJobDate("all");
                                        setFilter(e.target.value);
                                    }}
                                    style={{
                                        background: "#858282",
                                        borderColor: "#858282",
                                    }}
                                >
                                    {" "}
                                    All Jobs
                                </button>
                                <button
                                    className="ml-2 btn btn-success"
                                    onClick={(e) => {
                                        filterJobDate("current");
                                    }}
                                >
                                    {" "}
                                    Current week
                                </button>
                                <button
                                    className="ml-2 btn btn-pink"
                                    onClick={(e) => {
                                        filterJobDate("next");
                                    }}
                                >
                                    {" "}
                                    Next week
                                </button>
                                <button
                                    className="ml-2 btn btn-primary"
                                    onClick={(e) => {
                                        filterJobDate("nextnext");
                                    }}
                                >
                                    {" "}
                                    Next Next week
                                </button>
                            </div>
                        </div>
                        <div className="col-12 hidden-xl">
                            <div className="job-buttons">
                                <input type="hidden" id="filter-week" />
                                <button
                                    className="btn btn-info"
                                    onClick={(e) => {
                                        filterJobDate("all");
                                    }}
                                    style={{
                                        background: "#858282",
                                        borderColor: "#858282",
                                    }}
                                >
                                    {" "}
                                    All Jobs
                                </button>
                                <button
                                    className="ml-2 btn btn-success"
                                    onClick={(e) => {
                                        filterJobDate("current");
                                    }}
                                >
                                    {" "}
                                    Current week
                                </button>
                                <button
                                    className="ml-2 btn btn-pink"
                                    onClick={(e) => {
                                        filterJobDate("next");
                                    }}
                                >
                                    {" "}
                                    Next week
                                </button>
                                <button
                                    className="btn btn-primary"
                                    onClick={(e) => {
                                        filterJobDate("nextnext");
                                    }}
                                >
                                    {" "}
                                    Next Next week
                                </button>
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
                                                    {t("worker.jobs.date")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("worker.jobs.client")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("worker.jobs.service")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("worker.jobs.shift")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("worker.jobs.address")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("worker.jobs.c_time")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("worker.jobs.status")}
                                                </Th>
                                                <Th scope="col">
                                                    {t("worker.jobs.action")}
                                                </Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {totalJobs.map((item, index) => {
                                                //let services =  (item.offer.services) ? JSON.parse(item.offer.services) : [];
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
                                                        <Td
                                                            style={{
                                                                textTransform:
                                                                    "capitalize",
                                                            }}
                                                        >
                                                            {item.status}
                                                        </Td>
                                                        <Td>
                                                            <Link
                                                                to={`/worker/view-job/${item.id}`}
                                                                className="btn btn-primary"
                                                            >
                                                                {t(
                                                                    "worker.jobs.viewbtn"
                                                                )}
                                                            </Link>
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
                                        previousLabel={t("worker.previous")}
                                        nextLabel={t("worker.next")}
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
