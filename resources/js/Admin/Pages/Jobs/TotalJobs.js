import React, { useEffect, useState } from "react";
import ReactPaginate from "react-paginate";
import axios from "axios";
import { Link } from "react-router-dom";
import { useAlert } from "react-alert";
import Moment from "moment";
import { useNavigate } from "react-router-dom";
import { CSVLink } from "react-csv";

import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";
import Sidebar from "../../Layouts/Sidebar";
import SwitchWorkerModal from "../../Components/Modals/SwitchWorkerModal";

export default function TotalJobs() {
    const [totalJobs, setTotalJobs] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState("Loading...");
    const [filter, setFilter] = useState("");
    const [from, setFrom] = useState([]);
    const [to, setTo] = useState([]);
    const alert = useAlert();
    const navigate = useNavigate();

    const [lw, setLw] = useState("Change shift");
    const [AllFreq, setAllFreq] = useState([]);
    const [service, setService] = useState([]);
    const [sworkers, setSworkers] = useState([]);
    const [lng, setLng] = useState(null);
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

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getJobs = () => {
        axios.get(`/api/admin/jobs`, { headers }).then((response) => {
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
                "/api/admin/jobs?page=" +
                    currentPage +
                    "&filter_week=all&q=" +
                    filter,
                { headers }
            )
            .then((response) => {
                if (response.data.jobs.data.length > 0) {
                    setTotalJobs(response.data.jobs.data);
                    setPageCount(response.data.jobs.last_page);
                } else {
                    setLoading("No Job found");
                    setTotalJobs([]);
                }
            });
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Job!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/jobs/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Job has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getJobs();
                        }, 1000);
                    });
            }
        });
    };

    const [workers, setWorkers] = useState([]);
    const [Aworker, setAworker] = useState([]);
    const handleChange = (e, index) => {
        const id = e.target.name;
        axios.get(`/api/admin/job-worker/${id}`, { headers }).then((res) => {
            if (res.data.aworker.length > 0) {
                setAworker(res.data.aworker);
            } else {
                setAworker([]);
            }
        });
    };

    const upWorker = (e, index) => {
        let newWorkers = [...workers];
        newWorkers[e.target.name] = e.target.value;
        setWorkers(newWorkers);
        let up = e.target.parentNode.parentNode.lastChild.lastChild;
        setTimeout(() => {
            up.click();
        }, 500);
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

    const filterJobs = (e) => {
        filterJobs1();
    };

    const filterJobDate = (w) => {
        $("#filter-week").val(w);
        filterJobs1();
    };

    const filterJobs1 = () => {
        let filter_value = $("#search-field").val();
        let filter_week = $("#filter-week").val();

        axios
            .get(`/api/admin/jobs`, {
                headers,
                params: {
                    filter_week,
                    q: filter_value,
                },
            })
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

    const slot = [
        ["fullday-8am-16pm"],
        ["morning1-8am-9am"],
        ["morning2-9am-10am"],
        ["morning3-10am-11am"],
        ["morning4-11am-12pm"],
        ["morning-8am-12pm"],
        ["afternoon1-12pm-13pm"],
        ["afternoon2-13pm-14pm"],
        ["afternoon3-14pm-15pm"],
        ["afternoon4-15pm-16pm"],
        ["afternoon-12pm-16pm"],
        ["evening1-16pm-17pm"],
        ["evening2-17pm-18pm"],
        ["evening3-18pm-19pm"],
        ["evening4-19pm-20pm"],
        ["evening-16pm-20pm"],
        ["night1-20pm-21pm"],
        ["night2-21pm-22pm"],
        ["night3-22pm-23pm"],
        ["night4-23pm-24am"],
        ["night-20pm-24am"],
    ];

    const getFrequency = (lng) => {
        axios
            .post("/api/admin/all-service-schedule", { lng }, { headers })
            .then((res) => {
                setAllFreq(res.data.schedules);
            });
    };

    const shiftChange = (e) => {
        $("#edit-shift").modal("show");
    };

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

    const handleShift = (e) => {
        let newvalues = { ...cshift };

        if (e.target.name == "job" && e.target.value) {
            let j = e.target.options[e.target.selectedIndex];

            newvalues["contract"] = j.getAttribute("contract");
            newvalues["service"] = j.getAttribute("schedule_id");
            newvalues["client"] = j.getAttribute("client");
            setLng(j.getAttribute("lng"));
        }

        if (e.target.name == "shift_date") {
            getWorker(cshift.service, e.target.value);
        }

        // if (e.target.name == 'contract' && e.target.value) {

        //     setService(JSON.parse(contracts.find((c) => c.id == e.target.value).offer.services));
        // }
        if (e.target.name == "repetency" && e.target.value != "one_time") {
            getFrequency(lng);
        }

        if (e.target.name == "frequency") {
            newvalues["cycle"] =
                e.target.options[e.target.selectedIndex].getAttribute("cycle");
            newvalues["period"] =
                e.target.options[e.target.selectedIndex].getAttribute("period");
        }
        newvalues[e.target.name] = e.target.value;
        console.log(newvalues);
        setCshift(newvalues);
    };

    const getWorker = (sid, d) => {
        setLw("Loading data..");
        axios
            .get(`/api/admin/shift-change-worker/${sid}/${d}`, { headers })
            .then((res) => {
                setSworkers(res.data.data);
                setLw("Change shift");
            });
    };

    const isEmptyOrSpaces = (str) => {
        return str === null || str === "";
    };

    const changeShift = (e) => {
        e.preventDefault();

        // if (isEmptyOrSpaces(cshift.job)) {
        //     window.alert('Please select job');
        //     return;
        // }

        if (isEmptyOrSpaces(cshift.shift_date)) {
            window.alert("Please choose new shift date");
            return;
        }
        if (isEmptyOrSpaces(cshift.shift_time)) {
            window.alert("Please choose new shift time");
            return;
        }

        if (isEmptyOrSpaces(cshift.repetency)) {
            window.alert("Please select repetency");
            return;
        }

        if (
            cshift.repetency == "untill_date" &&
            (isEmptyOrSpaces(cshift.from) || isEmptyOrSpaces(cshift.to))
        ) {
            window.alert("Please select From and To date");
            return;
        }
        if (cshift.repetency == "one_time" && isEmptyOrSpaces(cshift.job)) {
            window.alert("Please select job");
            return;
        }
        if (
            cshift.repetency == "forever" &&
            isEmptyOrSpaces(cshift.frequency)
        ) {
            window.alert("Please select frequency");
            return;
        }

        axios
            .post(`/api/admin/update-shift`, { cshift }, { headers })
            .then((res) => {
                getJobs();
                resetShift();
                $("#edit-shift").modal("hide");
                alert.success(res.data.success);
            });
    };

    const handleSwitchWorker = (_jobID) => {
        setSelectedJobId(_jobID);
        setIsOpenSwitchWorker(true);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content" className="job-listing-page">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-2 col-4">
                            <h1 className="page-title">Jobs</h1>
                        </div>

                        <div className="col-sm-8 hidden-xs">
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
                                {/* <button className="ml-1 btn btn-info" onClick={e => shiftChange(e)} >Shift Change</button> */}
                                <button
                                    className="ml-2 btn btn-warning addButton"
                                    data-toggle="modal"
                                    data-target="#exampleModal"
                                >
                                    Export Time Reports
                                </button>
                            </div>
                            <div className="App" style={{ display: "none" }}>
                                <CSVLink {...csvReport} id="csv">
                                    Export to CSV
                                </CSVLink>
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
                                {/* <button className="btn btn-info mr-3" onClick={e => shiftChange(e)} >Shift Change</button> */}
                                <button
                                    className="ml-2 reportModal btn btn-warning"
                                    data-toggle="modal"
                                    data-target="#exampleModal"
                                >
                                    Export Time Reports
                                </button>
                            </div>
                        </div>
                        <div className="col-sm-2 hidden-xs">
                            <div className="search-data">
                                <input
                                    type="text"
                                    id="search-field"
                                    className="form-control"
                                    placeholder="Search"
                                    onChange={filterJobs}
                                    style={{ marginRight: "0" }}
                                />
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
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {totalJobs.length > 0 ? (
                                    <table className="table table-bordered">
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
                                                <th scope="col">Client</th>
                                                <th scope="col">Worker</th>
                                                <th scope="col">Shift</th>
                                                <th scope="col">Service</th>
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
                                                            <span className="d-block mb-1">
                                                                {Moment(
                                                                    item.start_date
                                                                ).format(
                                                                    "DD-MM-YYYY"
                                                                )}
                                                            </span>
                                                        </td>
                                                        <td
                                                            style={
                                                                item.client
                                                                    ? {
                                                                          background:
                                                                              item
                                                                                  .client
                                                                                  .color,
                                                                      }
                                                                    : {}
                                                            }
                                                        >
                                                            <Link
                                                                to={
                                                                    item.client
                                                                        ? `/admin/view-client/${item.client.id}`
                                                                        : "#"
                                                                }
                                                                style={{
                                                                    color: "#000000",
                                                                }}
                                                            >
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
                                                            <Link
                                                                to={
                                                                    item.worker
                                                                        ? `/admin/view-worker/${item.worker.id}`
                                                                        : "#"
                                                                }
                                                            >
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
                                                            </Link>
                                                            <select
                                                                name={item.id}
                                                                className="form-control mb-3 mt-1"
                                                                value={
                                                                    workers[
                                                                        `${item.id}`
                                                                    ]
                                                                        ? workers[
                                                                              `${item.id}`
                                                                          ]
                                                                        : ""
                                                                }
                                                                onFocus={(e) =>
                                                                    handleChange(
                                                                        e,
                                                                        index
                                                                    )
                                                                }
                                                                onChange={(e) =>
                                                                    upWorker(
                                                                        e,
                                                                        index
                                                                    )
                                                                }
                                                            >
                                                                <option value="">
                                                                    --- Select
                                                                    ---
                                                                </option>
                                                                {Aworker.length >
                                                                0 ? (
                                                                    Aworker &&
                                                                    Aworker.map(
                                                                        (
                                                                            w,
                                                                            i
                                                                        ) => {
                                                                            return (
                                                                                <option
                                                                                    value={
                                                                                        w.id
                                                                                    }
                                                                                    key={
                                                                                        i
                                                                                    }
                                                                                >
                                                                                    {" "}
                                                                                    {
                                                                                        w.firstname
                                                                                    }{" "}
                                                                                    {
                                                                                        w.lastname
                                                                                    }
                                                                                </option>
                                                                            );
                                                                        }
                                                                    )
                                                                ) : (
                                                                    <option value="">
                                                                        No
                                                                        worker
                                                                        Match
                                                                    </option>
                                                                )}
                                                            </select>
                                                        </td>
                                                        <td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                            style={
                                                                ix != undefined
                                                                    ? {
                                                                          background:
                                                                              ix.bg,
                                                                          color: ix.tc,
                                                                      }
                                                                    : {
                                                                          background:
                                                                              "#d3d3d3",
                                                                          color: "#444",
                                                                      }
                                                            }
                                                        >
                                                            <span className="mBlue">
                                                                {item.shifts}
                                                            </span>
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
                                                                        <button
                                                                            className="dropdown-item"
                                                                            onClick={() => {
                                                                                setCshift(
                                                                                    {
                                                                                        contract:
                                                                                            item.contract_id,
                                                                                        client: item.client_id,
                                                                                        repetency:
                                                                                            "",
                                                                                        job: item.id,
                                                                                        from: "",
                                                                                        to: "",
                                                                                        worker: "",
                                                                                        service:
                                                                                            item.schedule_id,
                                                                                        shift_date:
                                                                                            "",
                                                                                        frequency:
                                                                                            "",
                                                                                        cycle: "",
                                                                                        period: "",
                                                                                        shift_time:
                                                                                            "",
                                                                                    }
                                                                                );
                                                                                $(
                                                                                    "#edit-shift"
                                                                                ).modal(
                                                                                    "show"
                                                                                );
                                                                            }}
                                                                        >
                                                                            Change
                                                                            Shift
                                                                        </button>
                                                                        <button
                                                                            className="dropdown-item"
                                                                            onClick={() =>
                                                                                handleSwitchWorker(
                                                                                    item.id
                                                                                )
                                                                            }
                                                                        >
                                                                            Switch
                                                                            Worker
                                                                        </button>
                                                                        <button
                                                                            className="dropdown-item"
                                                                            onClick={() =>
                                                                                handleDelete(
                                                                                    item.id
                                                                                )
                                                                            }
                                                                        >
                                                                            Delete
                                                                        </button>
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

                        <div
                            className="modal fade"
                            id="edit-shift"
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
                                            Change Shift
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
                                                <label className="control-label">
                                                    Job
                                                </label>

                                                <select
                                                    disabled
                                                    className="form-control mb-3"
                                                    name="job"
                                                    value={cshift.job}
                                                    onChange={(e) =>
                                                        handleShift(e)
                                                    }
                                                >
                                                    <option value="">
                                                        {" "}
                                                        --- Please select Job
                                                        ---
                                                    </option>
                                                    {totalJobs.map((j, i) => {
                                                        return (
                                                            <option
                                                                contract={
                                                                    j.contract_id
                                                                }
                                                                client={
                                                                    j.client_id
                                                                }
                                                                value={j.id}
                                                                lng={
                                                                    j.client
                                                                        ? j
                                                                              .client
                                                                              .lng
                                                                        : "heb"
                                                                }
                                                                schedule_id={
                                                                    j.schedule_id
                                                                }
                                                                key={i}
                                                            >
                                                                {j.client
                                                                    ? j.client
                                                                          .firstname +
                                                                      " " +
                                                                      j.client
                                                                          .lastname
                                                                    : "NA"}{" "}
                                                                | {j.shifts} |{" "}
                                                                {Moment(
                                                                    j.start_date
                                                                ).format(
                                                                    "DD MMM, Y"
                                                                )}
                                                            </option>
                                                        );
                                                    })}
                                                </select>

                                                <label className="control-label">
                                                    New Shift date
                                                </label>

                                                <input
                                                    className="form-control mb-3"
                                                    name="shift_date"
                                                    type="date"
                                                    value={cshift.shift_date}
                                                    onChange={(e) =>
                                                        handleShift(e)
                                                    }
                                                />

                                                <label className="control-label">
                                                    New Shift time
                                                </label>

                                                <select
                                                    className="form-control mb-3"
                                                    name="shift_time"
                                                    value={cshift.shift_time}
                                                    onChange={(e) =>
                                                        handleShift(e)
                                                    }
                                                >
                                                    <option value="">
                                                        {" "}
                                                        --- Please select new
                                                        shift time ---
                                                    </option>
                                                    {slot?.map((s, i) => {
                                                        return (
                                                            <option
                                                                value={s}
                                                                key={i}
                                                            >
                                                                {s}
                                                            </option>
                                                        );
                                                    })}
                                                </select>

                                                {lw == "Change shift" &&
                                                    cshift.shift_time != "" && (
                                                        <>
                                                            <label className="control-label">
                                                                Worker
                                                            </label>

                                                            <select
                                                                className="form-control mb-3"
                                                                name="worker"
                                                                value={
                                                                    cshift.worker
                                                                }
                                                                onChange={(e) =>
                                                                    handleShift(
                                                                        e
                                                                    )
                                                                }
                                                            >
                                                                <option value="">
                                                                    {" "}
                                                                    --- Please
                                                                    select
                                                                    available
                                                                    workers ---
                                                                </option>
                                                                {sworkers &&
                                                                    sworkers.map(
                                                                        (
                                                                            item,
                                                                            index
                                                                        ) => {
                                                                            return (
                                                                                <option
                                                                                    value={
                                                                                        item.id
                                                                                    }
                                                                                    key={
                                                                                        index
                                                                                    }
                                                                                >
                                                                                    {" "}
                                                                                    {
                                                                                        item.firstname
                                                                                    }{" "}
                                                                                    {
                                                                                        item.lastname
                                                                                    }{" "}
                                                                                </option>
                                                                            );
                                                                        }
                                                                    )}
                                                            </select>

                                                            <label className="control-label">
                                                                Repetnacy
                                                            </label>

                                                            <select
                                                                name="repetency"
                                                                onChange={(e) =>
                                                                    handleShift(
                                                                        e
                                                                    )
                                                                }
                                                                value={
                                                                    cshift.repetency
                                                                }
                                                                className="form-control mb-3"
                                                            >
                                                                <option value="">
                                                                    {" "}
                                                                    --- Please
                                                                    select
                                                                    repetnacy
                                                                    ---
                                                                </option>
                                                                <option value="one_time">
                                                                    {" "}
                                                                    One Time (
                                                                    for single
                                                                    job )
                                                                </option>
                                                                <option value="forever">
                                                                    {" "}
                                                                    Forever{" "}
                                                                </option>
                                                                <option value="untill_date">
                                                                    {" "}
                                                                    Until Date{" "}
                                                                </option>
                                                            </select>
                                                        </>
                                                    )}

                                                {cshift.repetency &&
                                                    cshift.repetency !=
                                                        "one_time" && (
                                                        <>
                                                            <label className="control-label">
                                                                New Frequency
                                                            </label>

                                                            <select
                                                                name="frequency"
                                                                className="form-control mb-3"
                                                                value={
                                                                    cshift.frequency ||
                                                                    ""
                                                                }
                                                                onChange={(e) =>
                                                                    handleShift(
                                                                        e
                                                                    )
                                                                }
                                                            >
                                                                <option value="">
                                                                    {" "}
                                                                    -- Please
                                                                    select
                                                                    frequency --
                                                                </option>
                                                                {AllFreq &&
                                                                    AllFreq.map(
                                                                        (
                                                                            s,
                                                                            i
                                                                        ) => {
                                                                            return (
                                                                                <option
                                                                                    cycle={
                                                                                        s.cycle
                                                                                    }
                                                                                    period={
                                                                                        s.period
                                                                                    }
                                                                                    name={
                                                                                        s.name
                                                                                    }
                                                                                    value={
                                                                                        s.id
                                                                                    }
                                                                                    key={
                                                                                        i
                                                                                    }
                                                                                >
                                                                                    {" "}
                                                                                    {
                                                                                        s.name
                                                                                    }{" "}
                                                                                </option>
                                                                            );
                                                                        }
                                                                    )}
                                                            </select>
                                                        </>
                                                    )}

                                                {cshift.repetency ==
                                                    "untill_date" && (
                                                    <>
                                                        <label className="control-label">
                                                            From
                                                        </label>

                                                        <input
                                                            className="form-control mb-3"
                                                            type="date"
                                                            placeholder="From date"
                                                            name="from"
                                                            value={cshift.from}
                                                            onChange={(e) =>
                                                                handleShift(e)
                                                            }
                                                        />

                                                        <label className="control-label">
                                                            To
                                                        </label>

                                                        <input
                                                            className="form-control mb-3"
                                                            type="date"
                                                            placeholder="To date"
                                                            name="to"
                                                            value={cshift.to}
                                                            onChange={(e) =>
                                                                handleShift(e)
                                                            }
                                                        />
                                                    </>
                                                )}

                                                <button
                                                    className="btn btn-success form-control"
                                                    onClick={(e) =>
                                                        changeShift(e)
                                                    }
                                                >
                                                    {" "}
                                                    {lw}{" "}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {isOpenSwitchWorker && (
                <SwitchWorkerModal
                    setIsOpen={setIsOpenSwitchWorker}
                    isOpen={isOpenSwitchWorker}
                    jobId={selectedJobId}
                />
            )}
        </div>
    );
}
