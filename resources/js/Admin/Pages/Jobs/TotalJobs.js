import React, { useEffect, useState, useRef } from "react";
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
import { useTranslation } from "react-i18next";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import SwitchWorkerModal from "../../Components/Modals/SwitchWorkerModal";
import CancelJobModal from "../../Components/Modals/CancelJobModal";
import FilterButtons from "../../../Components/common/FilterButton";
import { Card, ListGroup } from "react-bootstrap";

export default function TotalJobs() {
    const { t, i18n } = useTranslation();
    const [from, setFrom] = useState([]);
    const [to, setTo] = useState([]);
    const [isOpenSwitchWorker, setIsOpenSwitchWorker] = useState(false);
    const [dateRange, setDateRange] = useState({
        start_date: "",
        end_date: "",
    });
    const [doneFilter, setDoneFilter] = useState("");
    const [startTimeFilter, setStartTimeFilter] = useState("");
    const [selectedJob, setSelectedJob] = useState(null);
    const [isOpenCancelModal, setIsOpenCancelModal] = useState(false);
    const [selectedDateRange, setSelectedDateRange] = useState("Week");
    const [selectedDateStep, setSelectedDateStep] = useState("Current");
    const [probbtn, setProbbtn] = useState(false)
    const [problems, setProblems] = useState([])

    const tableRef = useRef(null);
    const tableRef2 = useRef(null);
    const doneFilterRef = useRef(null);
    const startTimeFilterRef = useRef(null);
    const startDateRef = useRef(null);
    const endDateRef = useRef(null);
    const actualTimeExceedFilterRef = useRef(null);
    const hasNoWorkerFilterRef = useRef(null);

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

    const initializeDataTable1 = (data) => {
        if ($.fn.DataTable.isDataTable(tableRef2.current)) {
            $(tableRef2.current).DataTable().destroy(true);
        }

        $(tableRef2.current).DataTable({
            data: data,
            columns: [
                { title: 'Comments', data: 'problem' },
                { title: 'Created At', data: 'created_at' },
                { title: 'Client Name', data: 'client.name' },
                { title: 'Client Address', data: 'client.address' },
                {
                    title: 'Worker Name',
                    data: 'worker', 
                    render: function (data, type, row) {
                        if (data && data.firstname && data.lastname) {
                            return `${data.firstname} ${data.lastname}`;
                        } else {
                            return 'NA';
                        }
                    }
                },
                {
                    title: 'Action',
                    data: 'id',
                    orderable: false,
                    render: function (data, type, row) {
                        let _html = `
                            <div class="action-dropdown dropdown" style="text-align: center">
                                <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-ellipsis-vertical"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <button type="button" class="dropdown-item dt-take-action-btn" data-id="${row.job_id}">Take action</button>
                                    <button type="button" class="dropdown-item dt-take-all-btn" data-id="${row.job_id}">all ok</button>
                                </div>
                                
                            </div>`;
                        return _html;
                    }
                }
            ],
            responsive: true,
            order: [[0, 'desc']], // Default sorting
        });

        $(tableRef2.current).on('click', '.dt-take-action-btn', function () {
            const jobid = $(this).data('id');
            window.location = `/admin/jobs/${jobid}/change-worker`
        });

        $(tableRef2.current).on('click', '.dt-take-all-btn', function () {
            const jobid = $(this).data('id');
            setProbbtn(false)
        });
    };




    const fetchProblems = () => {
        $.ajax({
            url: '/api/client/jobs/get-problem',
            type: 'POST',
            headers: headers,
            success: function (response) {
                initializeDataTable1(response.problems);
            },
            error: function (xhr, status, error) {
                console.error('Error fetching problems:', error);
            }
        });
    };

    useEffect(() => {
        if (probbtn && tableRef2.current) {
            fetchProblems();
        }

        return () => {
            if ($.fn.DataTable.isDataTable(tableRef2.current)) {
                $(tableRef2.current).DataTable().destroy(true);
            }
        };
    }, [probbtn, tableRef2.current]);
    ;




    const initializeDataTable = () => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
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
                        d.has_no_worker = hasNoWorkerFilterRef.current.checked
                            ? 1
                            : 0;
                        d.start_date = startDateRef.current.value;
                        d.end_date = endDateRef.current.value;
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: t("global.date"),
                        data: "start_date",
                    },
                    {
                        title: t("client.dashboard.client"),
                        data: "client_name",
                        render: function (data, type, row, meta) {
                            let _html = `<span class="client-name-badge dt-client-badge" style=" color: white; background-color: ${row.client_color ?? "#FFFFFF"
                                };" data-client-id="${row.client_id}">`;

                            _html += `<i class="fa-solid fa-user"></i>`;

                            _html += data;

                            _html += `</span>`;

                            return _html;
                        },
                    },
                    {
                        title: t("global.service"),
                        data: "service_name",
                        render: function (data, type, row, meta) {
                            let _html = `<span class="service-name-badge" style="background-color: ${row.service_color ?? "#FFFFFF"
                                };">`;

                            _html += data;

                            _html += `</span>`;

                            return _html;
                        },
                    },
                    {
                        title: t("global.worker"),
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
                        title: t("client.jobs.shift"),
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
                        title: t("admin.global.if_job_was_done"),
                        data: "is_job_done",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            return `<div class="d-flex justify-content-sm-start justify-content-md-center"> <span class="rounded " style="border: 1px solid #ebebeb; overflow: hidden"> <input type="checkbox" data-id="${row.id
                                }" class="form-control dt-if-job-done-checkbox" ${row.is_job_done ? "checked" : ""
                                } ${row.status == "cancel" || row.is_order_closed == 1
                                    ? "disabled"
                                    : ""
                                }/> </span> </div>`;
                        },
                    },
                    {
                        title: t("admin.global.time_for_job"),
                        data: "duration_minutes",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            return `<span class="text-nowrap"> ${minutesToHours(
                                data
                            )} </span>`;
                        },
                    },
                    {
                        title: t("admin.global.time_worker_actually"),
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

                            _html += `<button type="button" class="time-counter dt-time-counter-dec" data-id="${row.id
                                }" data-hours="${_hours}" ${isOrderClosed ? "disabled" : ""
                                } style="pointer-events: ${_hours === 0 ? "none" : "auto"
                                }, opacity: ${_hours === 0 ? 0.5 : 1};"> - </button>`;

                            _html += `<span class="mx-1 time-counter" style="background-color: ${_timeBGColor}"> ${_hours} </span>`;

                            _html += `<button type="button" class="time-counter dt-time-counter-inc" ${isOrderClosed ? "disabled" : ""
                                } data-id="${row.id
                                }" data-hours="${_hours}"> + </button>`;

                            _html += `</div> </div>`;

                            return _html;
                        },
                    },
                    {
                        title: t("admin.leads.leadDetails.Comments"),
                        data: "comment",
                        orderable: false,
                    },
                    {
                        title: t("admin.global.client_review"),
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
                        title: t("global.action"),
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
                                _html += `<button type="button" class="dropdown-item dt-create-order-btn" data-id="${row.id}" data-client-id="${row.client_id}">${t("global.createOrder")}</button>`;
                            }

                            _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("global.view")}</button>`;

                            if (
                                [
                                    "not-started",
                                    "scheduled",
                                    "unscheduled",
                                    "re-scheduled",
                                ].includes(row.status)
                            ) {
                                _html += `<button type="button" class="dropdown-item dt-switch-worker-btn" data-id="${row.id}" data-total-amount="${row.total_amount}">${t("global.switchWorker")}</button>`;

                                _html += `<button type="button" class="dropdown-item dt-change-worker-btn" data-id="${row.id}">${t("admin.global.changeWorker")}</button>`;

                                // _html += `<button type="button" class="dropdown-item dt-change-shift-btn" data-id="${row.id}">Change Shift</button>`;

                                _html += `<button type="button" class="dropdown-item dt-cancel-btn" data-id="${row.id}" data-group-id="${row.job_group_id}">${t("modal.cancel")}</button>`;
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
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
                columnDefs: [
                    {
                        targets: '_all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass('custom-cell-class ');
                        }
                    }
                ]
            });
        }
    };

    useEffect(() => {
        initializeDataTable();

        // Customize the search input
        const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
        $("div.dt-search").append(searchInputWrapper);
        $("div.dt-search").addClass("position-relative");

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
                    !e.target.closest(".dt-if-job-done-checkbox") &&
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
                    !e.target.closest(".dt-switch-worker-btn") &&
                    !e.target.closest(".dt-if-job-done-checkbox")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                navigate(`/admin/jobs/view/${_id}`);
            }
        });

        $(tableRef.current).on("click", ".dt-client-badge", function () {
            const _clientID = $(this).data("client-id");
            navigate(`/admin/clients/view/${_clientID}`);
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
                _hours > 0 ? parseFloat(_hours) - 0.25 : 0
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
            navigate(`/admin/jobs/view/${_id}`);
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

        // $(tableRef.current).on("click", ".dt-change-shift-btn", function () {
        //     const _id = $(this).data("id");
        //     navigate(`/admin/jobs/${_id}/change-shift`);
        // });

        $(tableRef.current).on("click", ".dt-cancel-btn", function () {
            const _id = $(this).data("id");
            const _groupID = $(this).data("group-id");

            handleCancel({
                id: _id,
                job_group_id: _groupID,
            });
        });

        // Handle language changes
        i18n.on("languageChanged", () => {
            $(tableRef.current).DataTable().destroy(); // Destroy the table
            initializeDataTable(); // Reinitialize the table with updated language
        });

        // Cleanup event listeners and destroy DataTable when unmounting
        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true); // Ensure proper cleanup
                $(tableRef.current).off("click");
            }
        };
    }, [probbtn]);

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

    useEffect(() => {
        let _startMoment = Moment();
        let _endMoment = Moment();
        if (selectedDateRange == "Day") {
            if (selectedDateStep == "Previous") {
                _startMoment.subtract(1, "day");
                _endMoment.subtract(1, "day");
            } else if (selectedDateStep == "Next") {
                _startMoment.add(1, "day");
                _endMoment.add(1, "day");
            }
        } else if (selectedDateRange == "Week") {
            _startMoment.startOf("week");
            _endMoment.endOf("week");
            if (selectedDateStep == "Previous") {
                _startMoment.subtract(1, "week");
                _endMoment.subtract(1, "week");
            } else if (selectedDateStep == "Next") {
                _startMoment.add(1, "week");
                _endMoment.add(1, "week");
            }
        } else if (selectedDateRange == "Month") {
            _startMoment.startOf("month");
            _endMoment.endOf("month");
            if (selectedDateStep == "Previous") {
                _startMoment.subtract(1, "month");
                _endMoment.subtract(1, "month");
            } else if (selectedDateStep == "Next") {
                _startMoment.add(1, "month");
                _endMoment.add(1, "month");
            }
        } else if (selectedDateRange == "Year") {
            _startMoment.startOf("year");
            _endMoment.endOf("year");
            if (selectedDateStep == "Previous") {
                _startMoment.subtract(1, "year");
                _endMoment.subtract(1, "year");
            } else if (selectedDateStep == "Next") {
                _startMoment.add(1, "year");
                _endMoment.add(1, "year");
            }
        } else {
            _startMoment = Moment("2000-01-01");
        }

        setDateRange({
            start_date: _startMoment.format("YYYY-MM-DD"),
            end_date: _endMoment.format("YYYY-MM-DD"),
        });
    }, [selectedDateRange, selectedDateStep]);

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
                                {t("admin.global.Export")}
                            </CSVLink>
                        </div>

                        <div className="col-md-12 hidden-xs d-sm-flex justify-content-between mt-2">
                            <div className="d-flex align-items-center">
                                <div style={{ fontWeight: "bold" }}>{t("global.filter")}</div>
                                <div className="mx-3 d-flex align-items-center">
                                    <select
                                        className="form-control"
                                        value={doneFilter}
                                        onChange={(e) => {
                                            setDoneFilter(e.target.value);
                                        }}
                                    >
                                        <option value="">{t("admin.global.All")}</option>
                                        <option value="done">{t("admin.global.done")}</option>
                                        <option value="undone">{t("admin.global.undone")}</option>
                                    </select>

                                    <select
                                        className="form-control ml-3"
                                        value={startTimeFilter}
                                        onChange={(e) => {
                                            setStartTimeFilter(e.target.value);
                                        }}
                                    >
                                        <option value="">{t("modal.alltime")}</option>
                                        <option value="morning">{t("global.morning")}</option>
                                        <option value="noon">{t("global.noon")}</option>
                                        <option value="afternoon">
                                            {t("global.afternoon")}
                                        </option>
                                    </select>
                                </div>
                                <div
                                    style={{ fontWeight: "bold" }}
                                    className="mr-2"
                                >
                                    {t("global.date_period")}
                                </div>
                                <FilterButtons
                                    text={t("global.day")}
                                    className="px-4 mr-1"
                                    selectedFilter={selectedDateRange}
                                    setselectedFilter={setSelectedDateRange}
                                />
                                <FilterButtons
                                    text={t("global.week")}
                                    className="px-4 mr-1"
                                    selectedFilter={selectedDateRange}
                                    setselectedFilter={setSelectedDateRange}
                                />

                                <FilterButtons
                                    text={t("global.month")}
                                    className="px-4 mr-3"
                                    selectedFilter={selectedDateRange}
                                    setselectedFilter={setSelectedDateRange}
                                />

                                <FilterButtons
                                    text={t("client.previous")}
                                    className="px-3 mr-1"
                                    selectedFilter={selectedDateStep}
                                    setselectedFilter={setSelectedDateStep}
                                />
                                <FilterButtons
                                    text={t("global.current")}
                                    className="px-3 mr-1"
                                    selectedFilter={selectedDateStep}
                                    setselectedFilter={setSelectedDateStep}
                                />
                                <FilterButtons
                                    text={t("global.next")}
                                    className="px-3"
                                    selectedFilter={selectedDateStep}
                                    setselectedFilter={setSelectedDateStep}
                                />
                            </div>
                        </div>
                        <div className="col-md-12 hidden-xs d-sm-flex justify-content-between my-2">
                            <div className="d-flex align-items-center">
                                <div
                                    className="mr-3"
                                    style={{ fontWeight: "bold" }}
                                >
                                    {t("global.custom_date")}
                                </div>

                                <input
                                    className="form-control"
                                    type="date"
                                    placeholder="From date"
                                    name="from filter"
                                    style={{ width: "fit-content" }}
                                    value={dateRange.start_date}
                                    onChange={(e) => {
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
                                    {t("global.exportTimeReport")}
                                </button>

                                <button
                                    className="m-0 ml-4 btn border rounded px-3"
                                    style={!probbtn ? {
                                        background: "#2c3f51",
                                        color: "white",
                                    } : {
                                        background: "white",
                                        color: "black",
                                    }}
                                    onClick={() => setProbbtn(prev => !prev)}
                                >
                                    Problems
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
                                <div className="form-check form-check-inline">
                                    <input
                                        className="form-check-input"
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
                                        className="form-check-label"
                                        htmlFor="inlineCheckbox1"
                                    >
                                        {t("global.actualTimeExceed")}
                                    </label>
                                </div>

                                <div className="form-check form-check-inline">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="inlineCheckbox2"
                                        onChange={() => {
                                            $(tableRef.current)
                                                .DataTable()
                                                .draw();
                                        }}
                                        ref={hasNoWorkerFilterRef}
                                    />
                                    <label
                                        className="form-check-label"
                                        htmlFor="inlineCheckbox2"
                                    >
                                        {t("global.hasNoWorker")}
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
                                    {t("global.exportTimeReport")}
                                </button>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">{t("admin.leads.Options.sortBy")}</option>
                                <option value="0">{t("admin.dashboard.jobs.jobdate")}</option>
                                <option value="1">{t("admin.dashboard.jobs.client")}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body getjobslist">
                        {
                            !probbtn && (
                                <div className="boxPanel-Th-border-none">
                                    <table
                                        ref={tableRef}
                                        className="display table table-bordered"
                                    />
                                </div>
                            )
                        }

                        {
                            probbtn && (
                                <div className="boxPanel-Th-border-none">
                                    <table
                                        ref={tableRef2}
                                        className="display table table-bordered"
                                    />
                                </div>
                            )
                        }

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
                                            {t("global.workerHoursReport")}
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
                                                        {t("global.from")}
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
                                                        {t("global.to")}
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
                                            {t("modal.close")}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={(e) => handleReport(e)}
                                            className="btn btn-primary"
                                        >
                                            {t("admin.client.Export")}
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
