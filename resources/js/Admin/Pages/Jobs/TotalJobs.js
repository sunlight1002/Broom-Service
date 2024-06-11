import React, { useEffect, useState, useMemo, useRef } from "react";
import axios from "axios";
import { useAlert } from "react-alert";
import Moment from "moment";
import { useNavigate } from "react-router-dom";
import { Rating } from "react-simple-star-rating";
import "react-tooltip/dist/react-tooltip.css";
import { Tooltip } from "react-tooltip";
import { CSVLink } from "react-csv";
import Swal from "sweetalert2";
import { renderToString } from "react-dom/server";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import SwitchWorkerModal from "../../Components/Modals/SwitchWorkerModal";
import CancelJobModal from "../../Components/Modals/CancelJobModal";
import FilterButtons from "../../../Components/common/FilterButton";

export default function TotalJobs() {
    const todayFilter = {
        start_date: Moment().format("YYYY-MM-DD"),
        end_date: Moment().format("YYYY-MM-DD"),
    };
    const nextDayFilter = {
        start_date: Moment()
            .add(1, "days")
            .startOf("day")
            .format("YYYY-MM-DD"),
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

    const [from, setFrom] = useState([]);
    const [to, setTo] = useState([]);
    const [isOpenSwitchWorker, setIsOpenSwitchWorker] = useState(false);
    const [dateRange, setDateRange] = useState({
        start_date: todayFilter.start_date,
        end_date: todayFilter.end_date,
    });
    const [doneFilter, setDoneFilter] = useState("");
    const [startTimeFilter, setStartTimeFilter] = useState("");
    const [selectedFilter, setselectedFilter] = useState("Day");
    const [selectedButton,setSelectedButton]=useState();
    const [selectedJob, setSelectedJob] = useState(null);
    const [isOpenCancelModal, setIsOpenCancelModal] = useState(false);

    const tableRef = useRef(null);
    const doneFilterRef = useRef(null);
    const startTimeFilterRef = useRef(null);
    const startDateRef = useRef(null);
    const endDateRef = useRef(null);
    const actualTimeExceedFilterRef = useRef(null);

    const alert = useAlert();
    const navigate = useNavigate();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const minutesToHours = (minutes) => {
        const hours = Math.floor(minutes / 60);
        return `${hours} hours`;
    };

    useEffect(() => {
        $(tableRef.current).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/api/admin/jobs",
                type: "GET",
                beforeSend: function (request) {
                    request.setRequestHeader(
                        "Authorization",
                        `Bearer ` + localStorage.getItem("admin-token")
                    );
                },
                data: function (d) {
                    d.done_filter = doneFilterRef.current.value;
                    d.start_time_filter = startTimeFilterRef.current.value;
                    d.actual_time_exceed_filter = actualTimeExceedFilterRef
                        .current.checked
                        ? 1
                        : 0;
                    d.start_date = startDateRef.current.value;
                    d.end_date = endDateRef.current.value;
                },
            },
            order: [[0, "desc"]],
            columns: [
                {
                    title: "Date",
                    data: "start_date",
                },
                {
                    title: "Client",
                    data: "client_name",
                    render: function (data, type, row, meta) {
                        let _html = `<span class="client-name-badge dt-client-badge" style="background-color: ${
                            row.client_color ?? "#FFFFFF"
                        };" data-client-id="${row.client_id}">`;

                        _html += `<i class="fa-solid fa-user"></i>`;

                        _html += data;

                        _html += `</span>`;

                        return _html;
                    },
                },
                {
                    title: "Service",
                    data: "service_name",
                    render: function (data, type, row, meta) {
                        let _html = `<span class="service-name-badge" style="background-color: ${
                            row.service_color ?? "#FFFFFF"
                        };">`;

                        _html += data;

                        _html += `</span>`;

                        return _html;
                    },
                },
                {
                    title: "Worker",
                    data: "worker_name",
                    render: function (data, type, row, meta) {
                        let _html = `<span class="worker-name-badge dt-switch-worker-btn" data-id="${row.id}" data-total-amount="${row.total_amount}">`;

                        _html += `<i class="fa-solid fa-user"></i>`;

                        _html += data;

                        _html += `</span>`;

                        return _html;
                    },
                },
                {
                    title: "Shift",
                    data: "shifts",
                    render: function (data, type, row, meta) {
                        const _slots = data.split(",");

                        return _slots
                            .map((_slot, index) => {
                                return `<div class="rounded mb-1 shifts-badge"> ${_slot} </div>`;
                            })
                            .join(" ");
                    },
                },
                {
                    title: "If Job Was Done",
                    data: "is_job_done",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `<div class="d-flex justify-content-sm-start justify-content-md-center"> <span class="rounded " style="border: 1px solid #ebebeb; overflow: hidden"> <input type="checkbox" data-id="${
                            row.id
                        }" class="form-control dt-if-job-done-checkbox" ${
                            row.is_job_done ? "checked" : ""
                        } ${
                            row.status == "cancel" || row.is_order_closed == 1
                                ? "disabled"
                                : ""
                        }/> </span> </div>`;
                    },
                },
                {
                    title: "Time For Job",
                    data: "duration_minutes",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return `<span class="text-nowrap"> ${minutesToHours(
                            data
                        )} </span>`;
                    },
                },
                {
                    title: "Time Worker Actually",
                    data: "actual_time_taken_minutes",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        const _hours = row.actual_time_taken_minutes
                            ? parseFloat(
                                  row.actual_time_taken_minutes / 60
                              ).toFixed(2)
                            : 0;

                        const isOrderClosed =
                            row.status == "cancel" || row.is_order_closed == 1;

                        let _timeBGColor = "white";
                        if (
                            row.actual_time_taken_minutes > row.duration_minutes
                        ) {
                            _timeBGColor = "#ff0000";
                        } else if (isOrderClosed) {
                            _timeBGColor = "#e7e7e7";
                        }

                        let _html = `<div class="d-flex justify-content-sm-start justify-content-md-center"> <div class="d-flex align-items-center">`;

                        _html += `<button type="button" class="time-counter dt-time-counter-dec" data-id="${
                            row.id
                        }" data-hours="${_hours}" ${
                            isOrderClosed ? "disabled" : ""
                        } style="pointer-events: ${
                            _hours === 0 ? "none" : "auto"
                        }, opacity: ${_hours === 0 ? 0.5 : 1};"> - </button>`;

                        _html += `<span class="mx-1 time-counter" style="background-color: ${_timeBGColor}"> ${_hours} </span>`;

                        _html += `<button type="button" class="time-counter dt-time-counter-inc" ${
                            isOrderClosed ? "disabled" : ""
                        } data-id="${
                            row.id
                        }" data-hours="${_hours}"> + </button>`;

                        _html += `</div> </div>`;

                        return _html;
                    },
                },
                {
                    title: "Comments",
                    data: "comment",
                    orderable: false,
                },
                {
                    title: "Client Review",
                    data: "review",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let _html = `-`;

                        if (row.rating) {
                            _html = renderToString(
                                <div
                                    data-tooltip-hidden={!row.review}
                                    data-tooltip-id="slot-tooltip"
                                    data-tooltip-content={row.review}
                                >
                                    <Rating
                                        initialValue={20 * row.rating}
                                        allowFraction
                                        size={15}
                                        readonly
                                    />
                                </div>
                            );
                        }

                        return _html;
                    },
                },
                {
                    title: "Action",
                    data: "action",
                    orderable: false,
                    responsivePriority: 1,
                    render: function (data, type, row, meta) {
                        let _html =
                            '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                        if (
                            row.status == "completed" &&
                            !row.is_order_generated
                        ) {
                            _html += `<button type="button" class="dropdown-item dt-create-order-btn" data-id="${row.id}" data-client-id="${row.client_id}">Create Order</button>`;
                        }

                        _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">View</button>`;

                        if (
                            [
                                "not-started",
                                "scheduled",
                                "unscheduled",
                                "re-scheduled",
                            ].includes(row.status)
                        ) {
                            _html += `<button type="button" class="dropdown-item dt-switch-worker-btn" data-id="${row.id}" data-total-amount="${row.total_amount}">Switch Worker</button>`;

                            _html += `<button type="button" class="dropdown-item dt-change-worker-btn" data-id="${row.id}">Change Worker</button>`;

                            _html += `<button type="button" class="dropdown-item dt-change-shift-btn" data-id="${row.id}">Change Shift</button>`;

                            _html += `<button type="button" class="dropdown-item dt-cancel-btn" data-id="${row.id}" data-group-id="${row.job_group_id}">Cancel</button>`;
                        }

                        _html += "</div> </div>";

                        return _html;
                    },
                },
            ],
            ordering: true,
            searching: true,
            responsive: true,
            createdRow: function (row, data, dataIndex) {
                $(row).addClass("dt-row");
                $(row).attr("data-id", data.id);
            },
        });

        $(tableRef.current).on("click", "tr.dt-row,tr.child", function (e) {
            let _id = null;
            if (e.target.closest("tr.dt-row")) {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-client-badge") &&
                    !e.target.closest(".dt-time-counter-dec") &&
                    !e.target.closest(".dt-time-counter-inc") &&
                    !e.target.closest(".dt-switch-worker-btn") &&
                    (!tableRef.current.classList.contains("collapsed") ||
                        !e.target.closest(".dtr-control"))
                ) {
                    _id = $(this).data("id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-client-badge") &&
                    !e.target.closest(".dt-time-counter-dec") &&
                    !e.target.closest(".dt-time-counter-inc") &&
                    !e.target.closest(".dt-switch-worker-btn")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                navigate(`/admin/view-job/${_id}`);
            }
        });

        $(tableRef.current).on("click", ".dt-client-badge", function () {
            const _clientID = $(this).data("client-id");
            navigate(`/admin/view-client/${_clientID}`);
        });

        $(tableRef.current).on(
            "change",
            ".dt-if-job-done-checkbox",
            function () {
                const _id = $(this).data("id");
                handleJobDone(_id, this.checked);
            }
        );

        $(tableRef.current).on("click", ".dt-time-counter-dec", function () {
            const _id = $(this).data("id");
            const _hours = parseFloat($(this).data("hours"));

            const _changedHours = (
                _hours > 0 && !_hours.includes("-")
                    ? parseFloat(_hours) - 0.25
                    : 0
            ).toFixed(2);

            handleWorkerActualTime(_id, _changedHours * 60);
        });

        $(tableRef.current).on("click", ".dt-time-counter-inc", function () {
            const _id = $(this).data("id");
            const _hours = $(this).data("hours");

            const _changedHours = (parseFloat(_hours) + 0.25).toFixed(2);

            handleWorkerActualTime(_id, _changedHours * 60);
        });

        $(tableRef.current).on("click", ".dt-create-order-btn", function () {
            const _id = $(this).data("id");
            const _clientID = $(this).data("client-id");
            navigate(`/admin/add-order?j=${_id}&c=${_clientID}`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/view-job/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-switch-worker-btn", function () {
            const _id = $(this).data("id");
            const _totalAmount = $(this).data("total-amount");

            handleSwitchWorker({
                id: _id,
                total_amount: _totalAmount,
            });
        });

        $(tableRef.current).on("click", ".dt-change-worker-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/jobs/${_id}/change-worker`);
        });

        $(tableRef.current).on("click", ".dt-change-shift-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/jobs/${_id}/change-shift`);
        });

        $(tableRef.current).on("click", ".dt-cancel-btn", function () {
            const _id = $(this).data("id");
            const _groupID = $(this).data("group-id");

            handleCancel({
                id: _id,
                job_group_id: _groupID,
            });
        });

        return function cleanup() {
            $(tableRef.current).DataTable().destroy(true);
        };
    }, []);

    useEffect(() => {
        $(tableRef.current).DataTable().draw();
    }, [doneFilter, startTimeFilter, dateRange]);

    const handleJobDone = (_jobID, _checked) => {
        if (_checked) {
            axios
                .post(
                    `/api/admin/jobs/${_jobID}/update-job-done`,
                    { checked: _checked },
                    { headers }
                )
                .then((response) => {
                    $(tableRef.current).DataTable().draw();
                });
        } else {
            Swal.fire({
                title: "Are you sure?",
                text: "Current open order will be cancelled!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, Mark Job Undone!",
            }).then((result) => {
                if (result.isConfirmed) {
                    axios
                        .post(
                            `/api/admin/jobs/${_jobID}/update-job-done`,
                            { checked: _checked },
                            { headers }
                        )
                        .then((response) => {
                            $(tableRef.current).DataTable().draw();
                        })
                        .catch((e) => {
                            $(tableRef.current).DataTable().draw();
                        });
                }
            });
        }
    };

    const handleWorkerActualTime = (_jobID, _value) => {
        axios
            .post(
                `/api/admin/jobs/${_jobID}/update-worker-actual-time`,
                { value: _value },
                { headers }
            )
            .then((response) => {
                $(tableRef.current).DataTable().draw();
            })
            .catch((e) => {
                $(tableRef.current).DataTable().draw();
            });
    };

    const header = [
        { label: "Worker Name", key: "worker_name" },
        { label: "Worker ID", key: "worker_id" },
        { label: "Job", key: "job" },
        { label: "Total Actual Time", key: "hours" },
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
                `/api/admin/worker/hours/export`,
                { from: from, to: to },
                { headers }
            )
            .then((response) => {
                setFilename("Job worker hours - (" + from + " - " + to + ")");
                setAllData(response.data.jobs);
                document.querySelector("#csv").click();
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });
    };

    const csvReport = {
        data: Alldata,
        headers: header,
        filename: filename,
    };
  

    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order(parseInt(colIdx), "asc").draw();
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
                            </div>
                        </div> */}
                        <div className="App" style={{ display: "none" }}>
                            <CSVLink {...csvReport} id="csv">
                                Export to CSV
                            </CSVLink>
                        </div>

                        <div className="col-md-12 hidden-xs d-sm-flex justify-content-between mt-2">
                            <div className="d-flex align-items-center">
                                <div style={{ fontWeight: "bold" }}>Filter</div>
                                <div className="mx-3 d-flex align-items-center">
                                    <select
                                        className="form-control"
                                        value={doneFilter}
                                        onChange={(e) => {
                                            setDoneFilter(e.target.value);
                                        }}
                                    >
                                        <option value="">All</option>
                                        <option value="done">Done</option>
                                        <option value="undone">Undone</option>
                                    </select>

                                    <select
                                        className="form-control ml-3"
                                        value={startTimeFilter}
                                        onChange={(e) => {
                                            setStartTimeFilter(e.target.value);
                                        }}
                                    >
                                        <option value="">All Time</option>
                                        <option value="morning">Morning</option>
                                        <option value="noon">Noon</option>
                                        <option value="afternoon">
                                            Afternoon
                                        </option>
                                    </select>
                                </div>
                                <div
                                    style={{ fontWeight: "bold" }}
                                    className="mr-2"
                                >
                                    Date Period
                                </div>
                                {/* <FilterButtons
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
                                /> */}
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
                                    className="px-4 mr-4"
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
                                {
                                    selectedFilter === "Day" && (
                                        <div className="row"> 
                                     <FilterButtons
                                        text="Previous Day"
                                        className="px-3"
                                        
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    previousDayFilter.start_date,
                                                end_date: previousDayFilter.end_date,
                                            })
                                        }
                                         
                                       selectedFilter={selectedButton}
                                       setselectedFilter={setSelectedButton}
                                       
                                        
                                    />
                                     <FilterButtons
                                    
                                        text="Current Day"
                                        className="px-3"
                                        
                                        onClick={() =>
                                        setDateRange({
                                            start_date: todayFilter.start_date,
                                            end_date: todayFilter.end_date,
                                        })
                                        
                                    }
                                    selectedFilter={selectedButton}
                                    setselectedFilter={setSelectedButton}
                                    
                                    />
                                     <FilterButtons
                                        text="Next Day"
                                        className="px-3"
                                       
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    nextDayFilter.start_date,
                                                end_date: nextDayFilter.end_date,
                                               
                                       
                                            })
                                            
                                        }
                                        selectedFilter={selectedButton}
                                       setselectedFilter={setSelectedButton}

                                    />
                                        </div>
                                     
                                    )
                                }
                                 {
                                    selectedFilter=== "Week" && (
                                        <div className="row"> 
                                     <FilterButtons
                                        text="Previous Week"
                                        className="px-3"
                                       
                                        onClick={() =>
                                            setDateRange({
                                                start_date:
                                                    previousWeekFilter.start_date,
                                                end_date:
                                                    previousWeekFilter.end_date,
                                            })
                                        }
                                        selectedFilter={selectedButton}
                                       setselectedFilter={setSelectedButton}
                                    />
                                     <FilterButtons
                                        text="Current Week"
                                        className="px-3"
                                       
                                        onClick={() =>
                                                setDateRange({
                                                    start_date:
                                                        currentWeekFilter.start_date,
                                                    end_date:
                                                        currentWeekFilter.end_date,
                                                })
                                            }
                                            selectedFilter={selectedButton}
                                       setselectedFilter={setSelectedButton}
                                      
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
                                        selectedFilter={selectedButton}
                                        setselectedFilter={setSelectedButton}
                                    />
                                        </div>
                                     
                                    )
                                }
                              
                               
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

                                <input
                                    type="hidden"
                                    value={startTimeFilter}
                                    ref={startTimeFilterRef}
                                />

                                <input
                                    type="hidden"
                                    value={doneFilter}
                                    ref={doneFilterRef}
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
                            </div>
                        </div>

                        <div className="col-md-12 hidden-xs d-sm-flex justify-content-between my-2">
                            <div className="d-flex align-items-center">
                                <div class="form-check form-check-inline">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="inlineCheckbox1"
                                        onChange={() => {
                                            $(tableRef.current)
                                                .DataTable()
                                                .draw();
                                        }}
                                        ref={actualTimeExceedFilterRef}
                                    />
                                    <label
                                        class="form-check-label"
                                        for="inlineCheckbox1"
                                    >
                                        Actual Time Exceed
                                    </label>
                                </div>
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
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="0">Job Date</option>
                                <option value="1">Client</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body getjobslist">
                        <div className="boxPanel-Th-border-none">
                            <table
                                ref={tableRef}
                                className="display table table-bordered"
                            />
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
                                            Worker Hours Report
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
                    </div>
                </div>
            </div>

            {isOpenSwitchWorker && (
                <SwitchWorkerModal
                    setIsOpen={setIsOpenSwitchWorker}
                    isOpen={isOpenSwitchWorker}
                    job={selectedJob}
                    onSuccess={() => $(tableRef.current).DataTable().draw()}
                />
            )}

            {isOpenCancelModal && (
                <CancelJobModal
                    setIsOpen={setIsOpenCancelModal}
                    isOpen={isOpenCancelModal}
                    job={selectedJob}
                    onSuccess={() => {
                        $(tableRef.current).DataTable().draw();
                        setIsOpenCancelModal(false);
                    }}
                />
            )}

            <Tooltip id="slot-tooltip" />
        </div>
    );
}
