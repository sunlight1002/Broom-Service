import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Moment from "moment";
import { useAlert } from "react-alert";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";
import FilterButtons from "../../../Components/common/FilterButton";

export default function WorkerHours() {
    const alert = useAlert();
    const todayFilter = {
        start_date: Moment().format("YYYY-MM-DD"),
        end_date: Moment().format("YYYY-MM-DD"),
    };
    const nextDayFilter = {
        start_date: Moment().add(1, "days").startOf("day").format("YYYY-MM-DD"),
        end_date: Moment().add(1, "days").endOf("day").format("YYYY-MM-DD"),
    };
    const previousDayFilter = {
        start_date: Moment()
            .subtract(1, "days")
            .startOf("day")
            .format("YYYY-MM-DD"),
        end_date: Moment()
            .subtract(1, "days")
            .endOf("day")
            .format("YYYY-MM-DD"),
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

    const [dateRange, setDateRange] = useState({
        start_date: todayFilter.start_date,
        end_date: todayFilter.end_date,
    });
    const [selectedFilter, setselectedFilter] = useState("Day");
    const [selectedDateStep, setSelectedDateStep] = useState("Current Day");
    const [filters, setFilters] = useState({
        manpower_company_id: "",
        is_my_company: false,
    });
    const [manpowerCompanies, setManpowerCompanies] = useState([]);
    const [selectedWorkerIDs, setSelectedWorkerIDs] = useState([]);

    const tableRef = useRef(null);
    const startDateRef = useRef(null);
    const endDateRef = useRef(null);
    const manpowerCompanyRef = useRef(null);
    const isMyCompanyRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        $(tableRef.current).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/api/admin/workers/working-hours",
                type: "GET",
                beforeSend: function (request) {
                    request.setRequestHeader(
                        "Authorization",
                        `Bearer ` + localStorage.getItem("admin-token")
                    );
                },
                data: function (d) {
                    d.manpower_company_id = manpowerCompanyRef.current.value;
                    d.start_date = startDateRef.current.value;
                    d.end_date = endDateRef.current.value;
                    d.is_my_company = isMyCompanyRef.current.value;
                },
            },
            order: [[1, "desc"]],
            columns: [
                {
                    title: `<input type="checkbox" class="form-control dt-select-all" />`,
                    data: "id",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `<input type="checkbox" value="${data}" class="form-control dt-check-worker"/>`;
                    },
                },
                {
                    title: "Worker",
                    data: "name",
                    render: function (data, type, row, meta) {
                        return `<a href="/admin/view-worker/${row.id}" class="dt-worker-link" data-worker-id="${row.id}"> ${data} </a>`;
                    },
                },
                {
                    title: "Hours",
                    data: "minutes",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return data ? convertMinsToDecimalHrs(data) : "NA";
                    },
                },
            ],
            ordering: true,
            searching: true,
            responsive: true,
            drawCallback: function (settings) {
                setSelectedWorkerIDs([]);
                $(tableRef.current)
                    .find(".dt-select-all")
                    .prop("checked", false);
            },
        });

        $(tableRef.current).on("change", ".dt-select-all", function (e) {
            if (e.target.checked) {
                const _workerIDs = $(tableRef.current)
                    .find(".dt-check-worker")
                    .map(function () {
                        return this.value;
                    })
                    .get();

                setSelectedWorkerIDs(_workerIDs);
                $(tableRef.current)
                    .find(".dt-check-worker")
                    .prop("checked", true);
            } else {
                setSelectedWorkerIDs([]);
                $(tableRef.current)
                    .find(".dt-check-worker")
                    .prop("checked", false);
            }
        });

        $(tableRef.current).on("change", ".dt-check-worker", function (e) {
            const _workerID = e.target.value;

            if (e.target.checked) {
                setSelectedWorkerIDs((_workerIDs) => {
                    if (!_workerIDs.includes(_workerID)) {
                        return _workerIDs.concat([_workerID]);
                    }

                    return _workerIDs;
                });
            } else {
                setSelectedWorkerIDs((_workerIDs) => {
                    if (_workerIDs.includes(_workerID)) {
                        return _workerIDs.filter((i) => i != _workerID);
                    }

                    return _workerIDs;
                });
            }
        });

        $(tableRef.current).on("click", ".dt-worker-link", function () {
            const _workerID = $(this).data("worker-id");
            navigate(`/admin/view-worker/${_workerID}`);
        });

        return function cleanup() {
            $(tableRef.current).DataTable().destroy(true);
        };
    }, []);

    const handleExport = async () => {
        let _filters = {};

        if (filters.manpower_company_id !== "") {
            _filters.manpower_company_id = filters.manpower_company_id;
        }

        _filters.worker_ids = selectedWorkerIDs;
        _filters.start_date = dateRange.start_date;
        _filters.end_date = dateRange.end_date;

        await axios
            .post(
                "/api/admin/workers/working-hours/export",
                {
                    ..._filters,
                },
                {
                    headers,
                    responseType: "blob",
                }
            )
            .then((response) => {
                const fileName =
                    "Worker Hours - (" +
                    dateRange.start_date +
                    " - " +
                    dateRange.end_date +
                    ")";

                // create file link in browser's memory
                const href = URL.createObjectURL(response.data);

                // create "a" HTML element with href to file & click
                const link = document.createElement("a");
                link.href = href;
                link.setAttribute("download", `${fileName}.csv`); //or any other extension
                document.body.appendChild(link);
                link.click();

                // clean up "a" element & remove ObjectURL
                document.body.removeChild(link);
                URL.revokeObjectURL(href);
            });
    };

    useEffect(() => {
        $(tableRef.current).DataTable().draw();
    }, [dateRange, filters]);

    const getManpowerCompanies = async () => {
        await axios
            .get("/api/admin/manpower-companies-list", {
                headers,
            })
            .then((response) => {
                if (response?.data?.companies?.length > 0) {
                    setManpowerCompanies(response.data.companies);
                } else {
                    setManpowerCompanies([]);
                }
            });
    };

    useEffect(() => {
        getManpowerCompanies();
    }, []);

    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order(parseInt(colIdx), "asc").draw();
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Worker Hours</h1>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="0">Worker Name</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="row">
                    <div className="col-md-12 hidden-xs d-sm-flex justify-content-between mt-2">
                        <div className="d-flex align-items-center">
                            <div
                                style={{ fontWeight: "bold" }}
                                className="mr-2"
                            >
                                Date Period
                            </div>
                            <FilterButtons
                                text="Day"
                                className="px-4 mr-1"
                                onClick={() => {
                                    setDateRange({
                                        start_date: todayFilter.start_date,
                                        end_date: todayFilter.end_date,
                                    });
                                    setSelectedDateStep("Current Day");
                                }}
                                selectedFilter={selectedFilter}
                                setselectedFilter={setselectedFilter}
                            />
                            <FilterButtons
                                text="Week"
                                className="px-4 mr-3"
                                onClick={() => {
                                    setDateRange({
                                        start_date:
                                            currentWeekFilter.start_date,
                                        end_date: currentWeekFilter.end_date,
                                    });
                                    setSelectedDateStep("Current Week");
                                }}
                                selectedFilter={selectedFilter}
                                setselectedFilter={setselectedFilter}
                            />
                            {selectedFilter === "Day" && (
                                <div className="row">
                                    <FilterButtons
                                        text="Previous Day"
                                        className="px-3"
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    previousDayFilter.start_date,
                                                end_date:
                                                    previousDayFilter.end_date,
                                            })
                                        }
                                        selectedFilter={selectedDateStep}
                                        setselectedFilter={setSelectedDateStep}
                                    />
                                    <FilterButtons
                                        text="Current Day"
                                        className="px-3"
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    todayFilter.start_date,
                                                end_date: todayFilter.end_date,
                                            })
                                        }
                                        selectedFilter={selectedDateStep}
                                        setselectedFilter={setSelectedDateStep}
                                    />
                                    <FilterButtons
                                        text="Next Day"
                                        className="px-3"
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    nextDayFilter.start_date,
                                                end_date:
                                                    nextDayFilter.end_date,
                                            })
                                        }
                                        selectedFilter={selectedDateStep}
                                        setselectedFilter={setSelectedDateStep}
                                    />
                                </div>
                            )}
                            {selectedFilter === "Week" && (
                                <div className="row">
                                    <FilterButtons
                                        text="Previous Week"
                                        className="px-3"
                                        selectedFilter={selectedDateStep}
                                        setselectedFilter={setSelectedDateStep}
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    previousWeekFilter.start_date,
                                                end_date:
                                                    previousWeekFilter.end_date,
                                            })
                                        }
                                    />
                                    <FilterButtons
                                        text="Current Week"
                                        className="px-3"
                                        selectedFilter={selectedDateStep}
                                        setselectedFilter={setSelectedDateStep}
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    currentWeekFilter.start_date,
                                                end_date:
                                                    currentWeekFilter.end_date,
                                            })
                                        }
                                    />
                                    <FilterButtons
                                        text="Next Week"
                                        className="px-3"
                                        selectedFilter={selectedDateStep}
                                        setselectedFilter={setSelectedDateStep}
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    nextWeekFilter.start_date,
                                                end_date:
                                                    nextWeekFilter.end_date,
                                            })
                                        }
                                    />
                                </div>
                            )}
                            <input
                                type="hidden"
                                value={filters.manpower_company_id}
                                ref={manpowerCompanyRef}
                            />

                            <input
                                type="hidden"
                                value={dateRange.start_date}
                                ref={startDateRef}
                            />

                            <input
                                type="hidden"
                                value={dateRange.end_date}
                                ref={endDateRef}
                            />

                            <input
                                type="hidden"
                                value={filters.is_my_company}
                                ref={isMyCompanyRef}
                            />
                        </div>
                    </div>
                    <div className="col-md-12 hidden-xs d-sm-flex justify-content-between my-2">
                        <div className="d-flex align-items-center">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                Custom Date Range
                            </div>

                            <input
                                className="form-control"
                                type="date"
                                placeholder="From date"
                                name="from filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.start_date}
                                onChange={(e) => {
                                    setselectedFilter("Custom Range");
                                    setDateRange({
                                        start_date: e.target.value,
                                        end_date: dateRange.end_date,
                                    });
                                }}
                            />
                            <div className="mx-2">to</div>
                            <input
                                className="form-control"
                                type="date"
                                placeholder="To date"
                                name="to filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.end_date}
                                onChange={(e) => {
                                    setselectedFilter("Custom Range");
                                    setDateRange({
                                        start_date: dateRange.start_date,
                                        end_date: e.target.value,
                                    });
                                }}
                            />
                            <button
                                type="button"
                                className="m-0 ml-4 btn border rounded px-3"
                                onClick={handleExport}
                                style={{
                                    background: "#2c3f51",
                                    color: "white",
                                }}
                            >
                                Export
                            </button>
                        </div>
                    </div>
                    <div className="col-sm-12 hidden-xs d-sm-flex  mt-2">
                        <div
                            className="mr-3 align-items-center"
                            style={{ fontWeight: "bold" }}
                        >
                            Manpower Company
                        </div>
                        <div className="d-flex">
                            <select
                                className="form-control"
                                onChange={(e) => {
                                    setFilters({
                                        ...filters,
                                        manpower_company_id: e.target.value,
                                        is_my_company: false,
                                    });
                                }}
                                value={filters.manpower_company_id}
                            >
                                <option value="">--- Select ---</option>

                                {manpowerCompanies.map((company, _index) => (
                                    <option key={_index} value={company.id}>
                                        {" "}
                                        {company.name}
                                    </option>
                                ))}
                            </select>
                            <button
                                className={`btn border rounded px-3 mx-1`}
                                style={
                                    filters.is_my_company === true
                                        ? { background: "white" }
                                        : {
                                              background: "#2c3f51",
                                              color: "white",
                                          }
                                }
                                onClick={() => {
                                    setFilters({
                                        ...filters,
                                        manpower_company_id: "",
                                        is_my_company: true,
                                    });
                                }}
                            >
                                My Company
                            </button>
                            <button
                                className={`btn border rounded px-3 mx-1`}
                                style={
                                    filters.is_my_company !== true &&
                                    filters.manpower_company_id === ""
                                        ? { background: "white" }
                                        : {
                                              background: "#2c3f51",
                                              color: "white",
                                          }
                                }
                                onClick={() => {
                                    setFilters({
                                        ...filters,
                                        manpower_company_id: "",
                                        is_my_company: false,
                                    });
                                }}
                            >
                                All
                            </button>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table table-bordered"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
