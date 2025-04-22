import axios from "axios";
import Moment from "moment";
import React, { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import ReactPaginate from "react-paginate";
import { Link } from "react-router-dom";
import { Table, Tbody, Td, Th, Thead, Tr } from "react-super-responsive-table";

import FilterButtons from "../../../Components/common/FilterButton";
import WorkerSidebar from "../../Layouts/WorkerSidebar";

export default function WorkerTotalJobs() {
    const todayFilter = {
        start_date: Moment().format("YYYY-MM-DD"),
        end_date: Moment().format("YYYY-MM-DD"),
    };
    const currentWeekFilter = {
        start_date: Moment().startOf("week").format("YYYY-MM-DD"),
        end_date: Moment().endOf("week").format("YYYY-MM-DD"),
    };
    const nextWeekFilter = {
        start_date: Moment()
            .add(1, "weeks")
            .startOf("week")
            .format("YYYY-MM-DD"),
        end_date: Moment().add(1, "weeks").endOf("week").format("YYYY-MM-DD"),
    };
    const previousWeekFilter = {
        start_date: Moment()
            .subtract(1, "weeks")
            .startOf("week")
            .format("YYYY-MM-DD"),
        end_date: Moment()
            .subtract(1, "weeks")
            .endOf("week")
            .format("YYYY-MM-DD"),
    };

    const { t, i18n } = useTranslation();

    const [totalJobs, setTotalJobs] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState(t("common.loading"));
    const [currentPage, setCurrentPage] = useState(0);
    const [dateRange, setDateRange] = useState({
        start_date: currentWeekFilter.start_date,
        end_date: currentWeekFilter.end_date,
    });
    const [selectedFilter, setselectedFilter] = useState(t("global.week"));

    const queryParams = new URLSearchParams(window.location.search);
    const urlParamF = queryParams.get("f");
    const w_lng = i18n.language;

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const getJobs = () => {
        let _filters = {};

        _filters.start_date = dateRange.start_date;
        _filters.end_date = dateRange.end_date;

        axios
            .get(`/api/jobs`, {
                headers,
                params: {
                    page: currentPage,
                    ..._filters,
                },
            })
            .then((response) => {
                if (response.data.jobs.data.length > 0) {
                    setTotalJobs(response.data.jobs.data);
                    setPageCount(response.data.jobs.last_page);
                } else {
                    setTotalJobs([]);
                    setLoading(t("worker.jobs.noJobFound"));
                }
            });
    };

    useEffect(() => {
        getJobs();
    }, [currentPage, dateRange]);    

    useEffect(() => {
        if (urlParamF == "past") {
            setselectedFilter(t("worker.jobs.custom_range"));
            setDateRange({
                start_date: Moment()
                    .subtract(1, "days")
                    .subtract(1, "weeks")
                    .format("YYYY-MM-DD"),
                end_date: Moment().subtract(1, "days").format("YYYY-MM-DD"),
            });
        } else if (urlParamF == "upcoming") {
            setselectedFilter(t("worker.jobs.custom_range"));
            setDateRange({
                start_date: Moment().add(1, "days").format("YYYY-MM-DD"),
                end_date: Moment()
                    .add(1, "days")
                    .add(1, "weeks")
                    .format("YYYY-MM-DD"),
            });
        } else if (urlParamF == "today") {
            setselectedFilter(t("global.day"));
            setDateRange({
                start_date: todayFilter.start_date,
                end_date: todayFilter.end_date,
            });
        }
    }, [urlParamF]);

    return (
        <div id="container">
            <WorkerSidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-md-12 mt-2">
                            <div className="d-sm-flex align-items-center">
                                <div
                                    style={{ fontWeight: "bold" }}
                                    className="mr-2 "
                                >
                                    {t("worker.jobs.date_period")}
                                </div>

                                <div>
                                    <FilterButtons
                                        text={t("global.day")}
                                        className="px-4 mr-1 "
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    todayFilter.start_date,
                                                end_date: todayFilter.end_date,
                                            })
                                        }
                                        selectedFilter={selectedFilter}
                                        setselectedFilter={setselectedFilter}
                                    />
                                    <FilterButtons
                                        text={t("global.week")}
                                        className="px-4 mr-3 "
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    currentWeekFilter.start_date,
                                                end_date:
                                                    currentWeekFilter.end_date,
                                            })
                                        }
                                        selectedFilter={selectedFilter}
                                        setselectedFilter={setselectedFilter}
                                    />
                                    <FilterButtons
                                        text={t("global.previous_week")}
                                        className="px-3 mr-1 "
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    previousWeekFilter.start_date,
                                                end_date:
                                                    previousWeekFilter.end_date,
                                            })
                                        }
                                        selectedFilter={selectedFilter}
                                        setselectedFilter={setselectedFilter}
                                    />
                                    <FilterButtons
                                        text={t("global.next_week")}
                                        className="px-3 "
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    nextWeekFilter.start_date,
                                                end_date:
                                                    nextWeekFilter.end_date,
                                            })
                                        }
                                        selectedFilter={selectedFilter}
                                        setselectedFilter={setselectedFilter}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="col-md-12 my-2">
                            <div className="d-sm-flex align-items-center">
                                <div
                                    className="mr-3"
                                    style={{ fontWeight: "bold" }}
                                >
                                    {t("worker.jobs.custom_date_range")}
                                </div>
                                <div className="d-flex align-items-center">
                                    <input
                                        className="form-control"
                                        type="date"
                                        placeholder="From date"
                                        name="from filter"
                                        style={{ width: "fit-content" }}
                                        value={dateRange.start_date}
                                        onChange={(e) => {
                                            setselectedFilter(
                                                t("worker.jobs.custom_range")
                                            );
                                            setDateRange({
                                                start_date: e.target.value,
                                                end_date: dateRange.end_date,
                                            });
                                        }}
                                    />
                                    <div className="mx-2">{t("global.to")}</div>
                                    <input
                                        className="form-control"
                                        type="date"
                                        placeholder="To date"
                                        name="to filter"
                                        style={{ width: "fit-content" }}
                                        value={dateRange.end_date}
                                        onChange={(e) => {
                                            setselectedFilter(
                                                t("worker.jobs.custom_range")
                                            );
                                            setDateRange({
                                                start_date:
                                                    dateRange.start_date,
                                                end_date: e.target.value,
                                            });
                                        }}
                                    />
                                </div>
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
                                                        <Td>{item?.shifts}</Td>
                                                        <Td>
                                                            {item.property_address ? (
                                                                <Link
                                                                to={
                                                                    item.property_address.latitude && item.property_address.longitude
                                                                        ? `https://maps.google.com/?q=${item.property_address.latitude},${item.property_address.longitude}`
                                                                        : `https://maps.google.com?q=${item.property_address.geo_address}`
                                                                }
                                                                target="_blank"
                                                            >
                                                                {address_name}
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
                                                            {item?.jobservice
                                                                ? item?.jobservice?.duration_minutes / 60 + " Hours"
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
                                                                to={`/worker/jobs/view/${item.id}`}
                                                                className="btn btn-primary mt-4 mt-md-0 ml-1"
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
                                        onPageChange={(data) => {
                                            setCurrentPage(data.selected + 1);
                                        }}
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
