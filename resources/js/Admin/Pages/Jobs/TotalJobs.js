import React, { useEffect, useState } from "react";
import ReactPaginate from "react-paginate";
import axios from "axios";
import { Link } from "react-router-dom";
import { useAlert } from "react-alert";
import Moment from "moment";
import { useNavigate } from "react-router-dom";
import { CSVLink } from "react-csv";
import Swal from "sweetalert2";
import useDebounce from "./hooks/useDebounce";

import Sidebar from "../../Layouts/Sidebar";
import SwitchWorkerModal from "../../Components/Modals/SwitchWorkerModal";
import CancelJobModal from "../../Components/Modals/CancelJobModal";

export default function TotalJobs() {
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

    const [totalJobs, setTotalJobs] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState("Loading...");
    const [from, setFrom] = useState([]);
    const [to, setTo] = useState([]);
    const [cshift, setCshift] = useState({
        contract: "",
        client: "",
        repetency: "",
        job: "",
        from: "",
        to: "",
        worker: "",
        service: "",
        shift_date: "",
        frequency: "",
        cycle: "",
        period: "",
        shift_time: "",
    });
    const [isOpenSwitchWorker, setIsOpenSwitchWorker] = useState(false);
    const [selectedJobId, setSelectedJobId] = useState(null);
    const [currentPage, setCurrentPage] = useState(0);
    const [dateRange, setDateRange] = useState({
        start_date: currentWeekFilter.start_date,
        end_date: currentWeekFilter.end_date,
    });
    const [paymentFilter, setPaymentFilter] = useState("");
    const [searchVal, setSearchVal] = useState("");
    const [selectedFilter, setselectedFilter] = useState("Week");
    const [selectedJob, setSelectedJob] = useState(null);
    const [isOpenCancelModal, setIsOpenCancelModal] = useState(false);

    const alert = useAlert();
    const navigate = useNavigate();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getJobs = () => {
        let _filters = {};

        if (searchVal) {
            _filters.keyword = searchVal;
        }

        if (paymentFilter) {
            _filters.payment_filter = paymentFilter;
        }

        _filters.start_date = dateRange.start_date;
        _filters.end_date = dateRange.end_date;

        axios
            .get(`/api/admin/jobs`, {
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
                    setPageCount(0);
                    setLoading("No Job found");
                }
            });
    };

    useEffect(() => {
        getJobs();
    }, [currentPage, paymentFilter, dateRange, searchVal]);

    const handleJobDone = (_jobID, _checked) => {
        axios
            .post(
                `/api/admin/jobs/${_jobID}/update-job-done`,
                { checked: _checked },
                { headers }
            )
            .then((response) => {
                getJobs();
            });
    };

    const handleWorkerActualTime = (_jobID, _value) => {
        axios
            .post(
                `/api/admin/jobs/${_jobID}/update-worker-actual-time`,
                { value: _value },
                { headers }
            )
            .then((response) => {
                getJobs();
            });
    };

    const handleNavigate = (e, id) => {
        e.preventDefault();
        navigate(`/admin/view-job/${id}`);
    };

    function toHoursAndMinutes(totalSeconds) {
        const totalMinutes = Math.floor(totalSeconds / 60);
        const s = totalSeconds % 60;
        const h = Math.floor(totalMinutes / 60);
        const m = totalMinutes % 60;
        return decimalHours(h, m, s);
    }

    function decimalHours(h, m, s) {
        var hours = parseInt(h, 10);
        var minutes = m ? parseInt(m, 10) : 0;
        var min = minutes / 60;
        return hours + ":" + min.toString().substring(0, 4);
    }

    const header = [
        { label: "Worker Name", key: "worker_name" },
        { label: "Worker ID", key: "worker_id" },
        { label: "Start Time", key: "start_time" },
        { label: "End Time", key: "end_time" },
        { label: "Total Time", key: "time_diffrence" },
    ];

    const [Alldata, setAllData] = useState([]);
    const [filename, setFilename] = useState("");
    const handleReport = (e) => {
        e.preventDefault();

        if (!from) {
            window.alert("Please select form date!");
            return false;
        }
        if (!to) {
            window.alert("Please select to date!");
            return false;
        }

        axios
            .post(
                `/api/admin/export_report`,
                { type: "all", from: from, to: to },
                { headers }
            )
            .then((res) => {
                if (res.data.status_code == 404) {
                    alert.error(res.data.msg);
                } else {
                    setFilename(res.data.filename);
                    let rep = res.data.report;
                    for (let r in rep) {
                        rep[r].time_diffrence = toHoursAndMinutes(
                            rep[r].time_total
                        );
                    }

                    setAllData(rep);
                    document.querySelector("#csv").click();
                }
            });
    };

    const csvReport = {
        data: Alldata,
        headers: header,
        filename: filename,
    };
    const copy = [...totalJobs];
    const [order, setOrder] = useState("ASC");
    const sortTable = (e, col) => {
        let n = e.target.nodeName;
        if (n != "SELECT") {
            if (n == "TH") {
                let q = e.target.querySelector("span");
                if (q.innerHTML === "↑") {
                    q.innerHTML = "↓";
                } else {
                    q.innerHTML = "↑";
                }
            } else {
                let q = e.target;
                if (q.innerHTML === "↑") {
                    q.innerHTML = "↓";
                } else {
                    q.innerHTML = "↑";
                }
            }
        }

        if (order == "ASC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? 1 : -1
            );
            setTotalJobs(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setTotalJobs(sortData);
            setOrder("ASC");
        }
    };

    const shiftColors = [
        {
            bg: "yellow",
            tc: "#444",
            shift: "morning",
            start: "08:00",
            end: "12:00",
        },
        {
            bg: "#79BAEC",
            tc: "#fff",
            shift: "afternoon",
            start: "12:00",
            end: "16:00",
        },
        {
            bg: "#DBF9DB",
            tc: "#444",
            shift: "evening",
            start: "16:00",
            end: "20:00",
        },
        {
            bg: "#B09FCA",
            tc: "#fff",
            shift: "night",
            start: "20:00",
            end: "24:00",
        },
        {
            bg: "#d3d3d3",
            tc: "#444",
            shift: "fullday",
            start: "08:00",
            end: "16:00",
        },
    ];

    const resetShift = () => {
        setCshift({
            contract: "",
            client: "",
            repetency: "",
            job: "",
            from: "",
            to: "",
            worker: "",
            service: "",
            shift_date: "",
            frequency: "",
            cycle: "",
            period: "",
            shift_time: "",
        });
    };

    const handleSwitchWorker = (_job) => {
        setSelectedJob(_job);
        setIsOpenSwitchWorker(true);
    };

    const handleCancel = (_job) => {
        setSelectedJob(_job);
        setIsOpenCancelModal(true);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content" className="job-listing-page">
                <div className="titleBox customer-title">
                    <div className="row">
                        {/* <div className="col-sm-2 col-4">
                            <h1 className="page-title">Jobs</h1>
                        </div> */}
                        {/* Desktop */}
                        {/* <div className="col-sm-8 hidden-xs">
                            <div className="job-buttons">
                                <button
                                    className="ml-2 btn btn-warning addButton"
                                    data-toggle="modal"
                                    data-target="#exampleModal"
                                >
                                    Export Time Reports
                                </button>
                                <Link
                                    className="ml-2 btn btn-warning addButton"
                                    to={`/admin/jobs/change-worker-requests`}
                                >
                                    Change Worker Requests
                                </Link>
                            </div>
                            <div className="App" style={{ display: "none" }}>
                                <CSVLink {...csvReport} id="csv">
                                    Export to CSV
                                </CSVLink>
                            </div>
                        </div> */}
                        {/* <div className="col-sm-2 hidden-xs">
                            <div className="search-data">
                                <input
                                    type="text"
                                    value={searchVal}
                                    className="form-control"
                                    placeholder="Search"
                                    onChange={(e) => {
                                        setSearchVal(e.target.value);
                                    }}
                                    style={{ marginRight: "0" }}
                                />
                            </div>
                        </div> */}

                        <div className="col-md-12 hidden-xs d-sm-flex justify-content-between mt-2">
                            <div className="d-flex align-items-center">
                                <div style={{ fontWeight: "bold" }}>Filter</div>
                                <div className="mx-3 d-flex align-items-center border rounded">
                                    <div className="mx-2 text-nowrap">
                                        By Payment
                                    </div>
                                    <select
                                        className="form-control"
                                        value={paymentFilter}
                                        onChange={(e) => {
                                            setPaymentFilter(e.target.value);
                                        }}
                                    >
                                        <option value="">All</option>
                                        <option value="1">Only Paid</option>
                                        <option value="0">Only Unpaid</option>
                                    </select>
                                </div>
                                <div
                                    style={{ fontWeight: "bold" }}
                                    className="mr-2"
                                >
                                    Date Period
                                </div>
                                <FilterButtons
                                    text="Day"
                                    className="px-4 mr-1"
                                    onClick={() =>
                                        setDateRange({
                                            start_date: todayFilter.start_date,
                                            end_date: todayFilter.end_date,
                                        })
                                    }
                                    selectedFilter={selectedFilter}
                                    setselectedFilter={setselectedFilter}
                                />
                                <FilterButtons
                                    text="Week"
                                    className="px-4 mr-3"
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
                                    text="Previous Week"
                                    className="px-3 mr-1"
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
                                    text="Next Week"
                                    className="px-3"
                                    onClick={() =>
                                        setDateRange({
                                            start_date:
                                                nextWeekFilter.start_date,
                                            end_date: nextWeekFilter.end_date,
                                        })
                                    }
                                    selectedFilter={selectedFilter}
                                    setselectedFilter={setselectedFilter}
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
                                    className="m-0 ml-4 btn border rounded px-3"
                                    data-toggle="modal"
                                    style={{
                                        background: "#2c3f51",
                                        color: "white",
                                    }}
                                    data-target="#exampleModal"
                                >
                                    Export Time Reports
                                </button>
                                <Link
                                    className="m-0 ml-4 btn border rounded px-3"
                                    to={`/admin/jobs/change-worker-requests`}
                                    style={{
                                        background: "#2c3f51",
                                        color: "white",
                                    }}
                                >
                                    Change Worker Requests
                                </Link>
                            </div>
                        </div>
                        {/* Mobile */}
                        <div className="col-12 hidden-xl">
                            <div className="job-buttons">
                                <button
                                    className="ml-2 reportModal btn btn-warning"
                                    data-toggle="modal"
                                    data-target="#exampleModal"
                                >
                                    Export Time Reports
                                </button>
                                <Link
                                    className="ml-2 btn btn-warning addButton"
                                    to={`/admin/jobs/change-worker-requests`}
                                >
                                    Change Worker Requests
                                </Link>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e, e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="start_date">Job Date</option>
                                <option value="status">Status</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body getjobslist">
                        <div className="boxPanel-th-border-none">
                            <div className="table-responsive">
                                {totalJobs.length > 0 ? (
                                    <table className="table">
                                        <thead>
                                            <tr>
                                                <th
                                                    scope="col"
                                                    onClick={(e) => {
                                                        sortTable(
                                                            e,
                                                            "start_date"
                                                        );
                                                    }}
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                >
                                                    Date{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;{" "}
                                                    </span>
                                                </th>
                                                <th scope="col">
                                                    Client Reviews
                                                </th>
                                                <th scope="col">
                                                    If Job Was Done
                                                </th>
                                                <th scope="col">Client</th>
                                                <th scope="col">Worker</th>
                                                <th scope="col">Shift</th>
                                                <th scope="col">Service</th>
                                                <th scope="col">
                                                    Time For Job
                                                </th>
                                                <th scope="col">
                                                    Time Worker Actually
                                                </th>
                                                <th scope="col">Comments</th>
                                                <th
                                                    className="text-center"
                                                    scope="col"
                                                >
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {totalJobs.map((item, index) => {
                                                const _shifts =
                                                    item.shifts.split(", ");
                                                const _shift =
                                                    _shifts[0].split("-");

                                                const _startTime = Moment(
                                                    "1990-01-01 " + _shift[0]
                                                );
                                                const _endTime = Moment(
                                                    "1990-01-01 " + _shift[1]
                                                );

                                                const ix = shiftColors.find(
                                                    (_s) => {
                                                        const _shiftStartTime =
                                                            Moment(
                                                                "1990-01-01 " +
                                                                    _s.start
                                                            );
                                                        const _shiftEndTime =
                                                            Moment(
                                                                "1990-01-01 " +
                                                                    _s.end
                                                            );

                                                        return (
                                                            _shiftStartTime.isSame(
                                                                _startTime
                                                            ) ||
                                                            _shiftEndTime.isSame(
                                                                _endTime
                                                            ) ||
                                                            _shiftStartTime.isBetween(
                                                                _startTime,
                                                                _endTime
                                                            ) ||
                                                            _shiftEndTime.isBetween(
                                                                _startTime,
                                                                _endTime
                                                            ) ||
                                                            _startTime.isBetween(
                                                                _shiftStartTime,
                                                                _shiftEndTime
                                                            ) ||
                                                            _endTime.isBetween(
                                                                _shiftStartTime,
                                                                _shiftEndTime
                                                            )
                                                        );
                                                    }
                                                );

                                                return (
                                                    <tr
                                                        key={index}
                                                        style={{
                                                            cursor: "pointer",
                                                        }}
                                                    >
                                                        <td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                        >
                                                            <span className="d-block text-nowrap mb-1">
                                                                {Moment(
                                                                    item.start_date
                                                                ).format(
                                                                    "DD/MM/YYYY"
                                                                )}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            {item.review && (
                                                                <div className="d-flex justify-content-center">
                                                                    <div
                                                                        className="rounded"
                                                                        style={{
                                                                            padding:
                                                                                "2px 10px",
                                                                            backgroundColor:
                                                                                "#f4f4f4",
                                                                            border: "1px solid #ebebeb",
                                                                        }}
                                                                    >
                                                                        {
                                                                            item.review
                                                                        }
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td>
                                                            <div className="d-flex justify-content-center">
                                                                <span
                                                                    className="rounded"
                                                                    style={{
                                                                        border: "1px solid #ebebeb",
                                                                        overflow:
                                                                            "hidden",
                                                                    }}
                                                                >
                                                                    <input
                                                                        type="checkbox"
                                                                        name="job-compeleted"
                                                                        checked={
                                                                            item.is_job_done
                                                                        }
                                                                        onChange={(
                                                                            e
                                                                        ) => {
                                                                            handleJobDone(
                                                                                item.id,
                                                                                e
                                                                                    .target
                                                                                    .checked
                                                                            );
                                                                        }}
                                                                        style={{
                                                                            height: "20px",
                                                                            width: "20px",
                                                                            accentColor:
                                                                                "#f4f4f4",
                                                                        }}
                                                                        className="form-control"
                                                                    />
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <Link
                                                                to={
                                                                    item.client
                                                                        ? `/admin/view-client/${item.client.id}`
                                                                        : "#"
                                                                }
                                                                style={{
                                                                    color: "#000000",
                                                                    background:
                                                                        item
                                                                            .client
                                                                            .color ||
                                                                        "#FFFFFF",
                                                                    padding:
                                                                        "3px 8px",
                                                                    borderRadius:
                                                                        "5px",
                                                                    display:
                                                                        "flex",
                                                                    alignItems:
                                                                        "center",
                                                                    width: "max-content",
                                                                }}
                                                            >
                                                                <i
                                                                    className="fa-solid fa-user"
                                                                    style={{
                                                                        fontSize:
                                                                            "12px",
                                                                        marginRight:
                                                                            "5px",
                                                                    }}
                                                                ></i>
                                                                {item.client
                                                                    ? item
                                                                          .client
                                                                          .firstname +
                                                                      " " +
                                                                      item
                                                                          .client
                                                                          .lastname
                                                                    : "NA"}
                                                            </Link>
                                                        </td>
                                                        <td>
                                                            <div
                                                                onClick={() => {
                                                                    handleSwitchWorker(
                                                                        item
                                                                    );
                                                                    // $(
                                                                    //     "#available-workers"
                                                                    // ).modal(
                                                                    //     "show"
                                                                    // );
                                                                }}
                                                                style={{
                                                                    color: "black",
                                                                    background:
                                                                        "#f4f4f4",
                                                                    padding:
                                                                        "3px 8px",
                                                                    border: "1px solid #ebebeb",
                                                                    borderRadius:
                                                                        "5px",
                                                                    display:
                                                                        "flex",
                                                                    alignItems:
                                                                        "center",
                                                                    width: "max-content",
                                                                }}
                                                            >
                                                                <i
                                                                    className="fa-solid fa-user"
                                                                    style={{
                                                                        fontSize:
                                                                            "12px",
                                                                        marginRight:
                                                                            "5px",
                                                                    }}
                                                                ></i>
                                                                {item.worker
                                                                    ? item
                                                                          .worker
                                                                          .firstname +
                                                                      " " +
                                                                      item
                                                                          .worker
                                                                          .lastname
                                                                    : "NA"}
                                                            </div>
                                                        </td>
                                                        <td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                        >
                                                            <div className="d-flex flex-column justify-content-center">
                                                                {shiftHelperFn(
                                                                    item.shifts
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                            style={{
                                                                background: `${
                                                                    item.jobservice &&
                                                                    item
                                                                        .jobservice
                                                                        .service
                                                                        ? item
                                                                              .jobservice
                                                                              .service
                                                                              ?.color_code
                                                                        : "#FFFFFF"
                                                                }`,
                                                            }}
                                                        >
                                                            {item.jobservice &&
                                                                (item.client &&
                                                                item.client
                                                                    .lng == "en"
                                                                    ? item
                                                                          .jobservice
                                                                          .name
                                                                    : item
                                                                          .jobservice
                                                                          .heb_name)}
                                                        </td>
                                                        <td>
                                                            <div className="d-flex justify-content-center">
                                                                {item.jobservice &&
                                                                    item.client && (
                                                                        <span className="text-nowrap">
                                                                            {minutesToHours(
                                                                                item
                                                                                    .jobservice
                                                                                    .duration_minutes
                                                                            )}
                                                                        </span>
                                                                    )}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div className="d-flex justify-content-center">
                                                                {item && (
                                                                    <ActuallyTimeWorker
                                                                        data={
                                                                            item
                                                                        }
                                                                        emitValue={(
                                                                            e
                                                                        ) => {
                                                                            handleWorkerActualTime(
                                                                                item.id,
                                                                                e *
                                                                                    60
                                                                            );
                                                                        }}
                                                                    />
                                                                )}
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div className="d-flex justify-content-center">
                                                                {item.comment ||
                                                                    "-"}
                                                            </div>
                                                        </td>
                                                        <td className="text-center">
                                                            <div className="action-dropdown dropdown pb-2">
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-default dropdown-toggle"
                                                                    data-toggle="dropdown"
                                                                >
                                                                    <i className="fa fa-ellipsis-vertical"></i>
                                                                </button>

                                                                {item.client && (
                                                                    <div className="dropdown-menu">
                                                                        {item.client &&
                                                                            !item.is_order_generated && (
                                                                                <Link
                                                                                    to={`/admin/add-order?j=${item.id}&c=${item.client.id}`}
                                                                                    className="dropdown-item"
                                                                                >
                                                                                    Create
                                                                                    Order
                                                                                </Link>
                                                                            )}
                                                                        {/* {item.client &&
                                                                            item.order && (
                                                                                <Link
                                                                                    to={`/admin/add-invoice?j=${item.id}&c=${item.client.id}`}
                                                                                    className="dropdown-item"
                                                                                >
                                                                                    Create
                                                                                    Invoice
                                                                                </Link>
                                                                            )} */}
                                                                        <Link
                                                                            to={`/admin/view-job/${item.id}`}
                                                                            className="dropdown-item"
                                                                        >
                                                                            View
                                                                        </Link>
                                                                        {/* <button
                                                                            className="dropdown-item"
                                                                            onClick={() => {
                                                                                console.log(
                                                                                    `edit item - ${item.id}`
                                                                                );
                                                                                $(
                                                                                    "#edit-job"
                                                                                ).modal(
                                                                                    "show"
                                                                                );
                                                                            }}
                                                                        >
                                                                            Edit
                                                                        </button> */}
                                                                        {[
                                                                            "not-started",
                                                                            "scheduled",
                                                                            "unscheduled",
                                                                            "re-scheduled",
                                                                        ].includes(
                                                                            item.status
                                                                        ) && (
                                                                            <>
                                                                                <button
                                                                                    className="dropdown-item"
                                                                                    onClick={() =>
                                                                                        handleSwitchWorker(
                                                                                            item
                                                                                        )
                                                                                    }
                                                                                >
                                                                                    Switch
                                                                                    Worker
                                                                                </button>
                                                                                <Link
                                                                                    to={`/admin/jobs/${item.id}/change-worker`}
                                                                                    className="dropdown-item"
                                                                                >
                                                                                    Change
                                                                                    Worker
                                                                                </Link>
                                                                                <Link
                                                                                    to={`/admin/jobs/${item.id}/change-shift`}
                                                                                    className="dropdown-item"
                                                                                >
                                                                                    Change
                                                                                    Shift
                                                                                </Link>
                                                                                <button
                                                                                    className="dropdown-item"
                                                                                    onClick={() =>
                                                                                        handleCancel(
                                                                                            item
                                                                                        )
                                                                                    }
                                                                                >
                                                                                    Cancel
                                                                                </button>
                                                                            </>
                                                                        )}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                ) : (
                                    <p className="text-center mt-5">
                                        {loading}
                                    </p>
                                )}
                                {totalJobs.length > 0 && (
                                    <ReactPaginate
                                        previousLabel={"Previous"}
                                        nextLabel={"Next"}
                                        breakLabel={"..."}
                                        pageCount={pageCount}
                                        marginPagesDisplayed={2}
                                        pageRangeDisplayed={3}
                                        onPageChange={() => {
                                            setCurrentPage(currentPage + 1);
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
                                )}
                            </div>
                        </div>

                        <div
                            className="modal fade"
                            id="exampleModal"
                            tabIndex="-1"
                            role="dialog"
                            aria-labelledby="exampleModalLabel"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog" role="document">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5
                                            className="modal-title"
                                            id="exampleModalLabel"
                                        >
                                            Export Records
                                        </h5>
                                        <button
                                            type="button"
                                            className="close"
                                            data-dismiss="modal"
                                            aria-label="Close"
                                        >
                                            <span aria-hidden="true">
                                                &times;
                                            </span>
                                        </button>
                                    </div>
                                    <div className="modal-body">
                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        From
                                                    </label>
                                                    <input
                                                        type="date"
                                                        onChange={(e) =>
                                                            setFrom(
                                                                e.target.value
                                                            )
                                                        }
                                                        className="form-control"
                                                        required
                                                    />
                                                </div>
                                            </div>

                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        To
                                                    </label>
                                                    <input
                                                        type="date"
                                                        onChange={(e) =>
                                                            setTo(
                                                                e.target.value
                                                            )
                                                        }
                                                        className="form-control"
                                                        required
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary closeb"
                                            data-dismiss="modal"
                                        >
                                            Close
                                        </button>
                                        <button
                                            type="button"
                                            onClick={(e) => handleReport(e)}
                                            className="btn btn-primary"
                                        >
                                            Export
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* <div
                            className="modal fade"
                            id="edit-job"
                            tabIndex="-1"
                            role="dialog"
                            aria-labelledby="exampleModalLabel"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog" role="document">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5
                                            className="modal-title"
                                            id="exampleModalLabel"
                                        >
                                            Edit Job
                                        </h5>
                                        <button
                                            type="button"
                                            className="close"
                                            data-dismiss="modal"
                                            aria-label="Close"
                                            onClick={(e) => resetShift()}
                                        >
                                            <span aria-hidden="true">
                                                &times;
                                            </span>
                                        </button>
                                    </div>
                                    <div className="modal-body">
                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="mb-2">
                                                    <label className="control-label mb-0">
                                                        Date
                                                    </label>

                                                    <input
                                                        className="form-control"
                                                        name="shift_date"
                                                        type="date"
                                                    />
                                                </div>
                                                <div className="mb-2">
                                                    <label className="control-label mb-0">
                                                        Client
                                                    </label>

                                                    <input
                                                        className="form-control"
                                                        name="client"
                                                        type="text"
                                                    />
                                                </div>
                                                <div className="mb-2">
                                                    <label className="control-label mb-0">
                                                        Worker
                                                    </label>

                                                    <select
                                                        className="form-control"
                                                        onChange={(e) => {
                                                            console.log(e);
                                                        }}
                                                    >
                                                        <option
                                                            value="William"
                                                            selected
                                                        >
                                                            William
                                                        </option>
                                                        <option value="Adam">
                                                            Adam
                                                        </option>
                                                    </select>
                                                </div>
                                                <div className="mb-2">
                                                    <label className="control-label mb-0">
                                                        Shift
                                                    </label>

                                                    <input
                                                        className="form-control"
                                                        name="shift"
                                                        type="text"
                                                    />
                                                </div>
                                                <div className="mb-2">
                                                    <label className="control-label mb-0">
                                                        Service
                                                    </label>

                                                    <input
                                                        className="form-control"
                                                        name="service"
                                                        type="text"
                                                    />
                                                </div>
                                                <div className="mb-2">
                                                    <label className="control-label mb-0">
                                                        Comments
                                                    </label>

                                                    <textarea
                                                        className="form-control"
                                                        name="comments"
                                                        type="text"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary closeb"
                                            data-dismiss="modal"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="button"
                                            onClick={(e) => {
                                                console.log("submit");
                                            }}
                                            className="btn btn-primary"
                                        >
                                            Submit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div> */}

                        {/* <div
                            className="modal fade"
                            id="available-workers"
                            tabIndex="-1"
                            role="dialog"
                            aria-labelledby="exampleModalLabel"
                            aria-hidden="true"
                        >
                            <div className="modal-dialog" role="document">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5
                                            className="modal-title"
                                            id="exampleModalLabel"
                                        >
                                            Available Workers
                                        </h5>
                                        <button
                                            type="button"
                                            className="close"
                                            data-dismiss="modal"
                                            aria-label="Close"
                                            onClick={(e) => resetShift()}
                                        >
                                            <span aria-hidden="true">
                                                &times;
                                            </span>
                                        </button>
                                    </div>
                                    <div className="modal-body">
                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="mb-2">
                                                    <label className="control-label mb-1">
                                                        Worker Gender
                                                    </label>

                                                    <select
                                                        className="form-control"
                                                        onChange={(e) => {
                                                            console.log(e);
                                                        }}
                                                    >
                                                        <option
                                                            value="male"
                                                            selected
                                                        >
                                                            Only Male
                                                        </option>
                                                        <option value="female">
                                                            Only Female
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="modal-footer">
                                        <button
                                            type="button"
                                            className="btn btn-secondary closeb"
                                            data-dismiss="modal"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="button"
                                            onClick={(e) => {
                                                console.log("submit");
                                            }}
                                            className="btn btn-primary"
                                        >
                                            Submit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div> */}
                    </div>
                </div>
            </div>

            {isOpenSwitchWorker && (
                <SwitchWorkerModal
                    setIsOpen={setIsOpenSwitchWorker}
                    isOpen={isOpenSwitchWorker}
                    job={selectedJob}
                    onSuccess={() => getJobs()}
                />
            )}

            {isOpenCancelModal && (
                <CancelJobModal
                    setIsOpen={setIsOpenCancelModal}
                    isOpen={isOpenCancelModal}
                    job={selectedJob}
                />
            )}
        </div>
    );
}

const minutesToHours = (minutes) => {
    const hours = Math.floor(minutes / 60);
    return `${hours} hours`;
};

const shiftHelperFn = (timeString) => {
    if (timeString) {
        const arrOfStr = timeString.split(",");
        return arrOfStr.map((s, index) => (
            <div
                className="rounded mb-1"
                style={{
                    whiteSpace: "nowrap",
                    background: "#e7a917",
                    color: "white",
                    padding: "3px 8px",
                }}
                key={index}
            >
                {s}
            </div>
        ));
    } else {
        return "-";
    }
};

const divStyle = {
    background: "#f4f4f4",
    color: "black",
    padding: "3px 8px",
    border: "1px solid #ebebeb",
};

const ActuallyTimeWorker = ({ data, emitValue }) => {
    const [count, setCount] = useState(0);
    const [isChanged, setisChanged] = useState(false);
    const debouncedValue = useDebounce(count, 500);

    useEffect(() => {
        isChanged && emitValue(debouncedValue);
    }, [debouncedValue]);

    useEffect(() => {
        setCount(
            data.actual_time_taken_minutes
                ? Math.floor(data.actual_time_taken_minutes / 60)
                : 0
        );
    }, [data]);

    return (
        <div className="d-flex align-items-center">
            <div
                onClick={() => {
                    setisChanged(true);
                    setCount(count - 1);
                }}
                style={{
                    ...divStyle,
                    pointerEvents: count === 0 ? "none" : "auto",
                    opacity: count === 0 ? 0.5 : 1,
                }}
            >
                -
            </div>
            <span className="mx-1" style={{ ...divStyle, background: "white" }}>
                {count}
            </span>
            <button
                onClick={() => {
                    setisChanged(true);
                    setCount(count + 1);
                }}
                style={divStyle}
            >
                +
            </button>
        </div>
    );
};

const FilterButtons = ({
    text,
    className,
    selectedFilter,
    setselectedFilter,
    onClick,
}) => (
    <button
        className={`btn border rounded ${className}`}
        style={
            selectedFilter === text
                ? { background: "white" }
                : {
                      background: "#2c3f51",
                      color: "white",
                  }
        }
        onClick={() => {
            onClick?.();
            setselectedFilter(text);
        }}
    >
        {text}
    </button>
);
