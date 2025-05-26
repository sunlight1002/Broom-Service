import axios from "axios";
import Moment from "moment";
import React, { useEffect, useRef, useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import { CSVLink } from "react-csv";
import { renderToString } from "react-dom/server";
import { useTranslation } from "react-i18next";
import { json, useNavigate } from "react-router-dom";
import { Rating } from "react-simple-star-rating";
import { Tooltip } from "react-tooltip";
import "react-tooltip/dist/react-tooltip.css";
import Swal from "sweetalert2";

import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import $ from "jquery";

import FilterButtons from "../../../Components/common/FilterButton";
import CancelJobModal from "../../Components/Modals/CancelJobModal";
import SwitchWorkerModal from "../../Components/Modals/SwitchWorkerModal";
import Sidebar from "../../Layouts/Sidebar";
import { ChangeShiftModal } from "../../Components/Modals/ChangeShiftModal";

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
    const [isOpenCommentModal, setIsOpenCommentModal] = useState(false);
    const [isChangeShiftModal, setIsChangeShiftModal] = useState(false)
    const [selectedDateRange, setSelectedDateRange] = useState("Day");
    const [selectedDateStep, setSelectedDateStep] = useState("Current");
    const [probbtn, setProbbtn] = useState(false)
    const [problems, setProblems] = useState([])
    const [comment, setComment] = useState("");
    const role = localStorage.getItem("admin-role");
    const adminId = localStorage.getItem("admin-id");

    const tableRef = useRef(null);
    const tableRef2 = useRef(null);
    const doneFilterRef = useRef(null);
    const startTimeFilterRef = useRef(null);
    const startDateRef = useRef(null);
    const endDateRef = useRef(null);
    const actualTimeExceedFilterRef = useRef(null);
    const hasNoWorkerFilterRef = useRef(null);
    const showAllWorkerFilterRef = useRef(null);
    const showCancelJobsFilterRef = useRef(null);
    const showSupervisorJobsFilterRef = useRef(null);
    const [AllWorkers, setAllWorkers] = useState([]);
    const [allClients, setAllClients] = useState([]);
    const [selectedJobDate, setSelectedJobDate] = useState(null);
    const alert = useAlert();
    const navigate = useNavigate();

    const shouldDisable = role === "supervisor";
    const buttonAttributes = shouldDisable ? "disabled" : "";

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const minutesToHours = (minutes) => {
        const hours = Math.floor(minutes / 60);
        return `${hours} hours`;
    };

    const timeIntervals = {
        "Day": t("global.day"),
        "Week": t("global.week"),
        "Month": t("global.month"),
        "Previous": t("client.previous"),
        "Current": t("global.current"),
        "Next": t("global.next")
    }

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
            autoWidth: true,
            width: "100%",
            scrollX: true,
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



    const handleComent = (id) => {
        setSelectedJob(id);
        setIsOpenCommentModal(true);
    }


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


    const handleAssignJobToSupervisor = async (jobId) => {
        const res = await axios.post("/api/admin/jobs/assign-job-to-supervisor", { job_id: jobId, admin_id: adminId }, { headers });
        if (res.status == 200) {
            alert.success(res.data.message);
            $(tableRef.current).DataTable().draw();
        }
    }


    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/jobs?role=" + role,
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
                        d.show_all_worker = showAllWorkerFilterRef.current.checked
                            ? 1
                            : 0;
                        d.show_cancel_jobs = showCancelJobsFilterRef.current.checked
                            ? 1
                            : 0;
                        d.assigned_jobs = showSupervisorJobsFilterRef.current.checked
                            ? 1
                            : 0;
                        d.start_date = startDateRef.current.value;
                        d.end_date = endDateRef.current.value;
                    },
                },
                order: [[0, "asc"]],
                columns: [
                    {
                        title: t("global.date"),
                        data: "start_date",
                        className: "px-1",
                        responsivePriority: 1,
                        width: "3%",
                        render: function (data, type, row, meta) {
                            if (!data) return ""; // Handle empty data
                            let formattedDate = "";

                            let dateParts = data.split("/"); // Assuming format is DD/MM/YY
                            if (dateParts.length === 3) {
                                formattedDate = `${dateParts[0]}/${dateParts[1]}`;
                            }

                            // If the date is in YYYY-MM-DD format, adjust accordingly
                            dateParts = data.split("-");
                            if (dateParts.length === 3) {
                                formattedDate = `${dateParts[2]}/${dateParts[1]}`;
                            }

                            let serviceHtml = `<div class="rounded mb-1 shifts-badge">${row?.shifts}</div>`;
                            return `<div>${formattedDate} </br> ${serviceHtml}</div>`;
                        },
                    },
                    {
                        title: t("client.dashboard.client"),
                        data: "client_name",
                        responsivePriority: 1,
                        className: "text-center px-1",
                        // width: "12%",
                        render: function (data, type, row, meta) {
                            if (!data) return ""; // Handle empty data

                            let nameParts = data.split(" ");
                            let displayName = nameParts[0]; // Default to first name

                            if (nameParts.length > 1) {
                                displayName += " " + nameParts[1].substring(0, 2); // Append first two letters of last name
                            }

                            let _html = `<span class="worker-name-badge dt-client-badge" data-client-id="${row.client_id}" data-total-amount="${row.total_amount}">`;
                            _html += `<i class="fa-solid fa-user"></i> `;
                            _html += displayName; // Show formatted name
                            _html += `</span>`;

                            return _html;
                        },
                    },
                    {
                        title: t("global.worker"),
                        data: "worker_name",
                        responsivePriority: 1,
                        className: "text-center px-1",
                        // width: "12%",
                        render: function (data, type, row, meta) {
                            if (!data) return ""; // Handle empty data

                            let nameParts = data.split(" ");
                            let displayName = nameParts[0]; // Default to first name

                            if (nameParts.length > 1) {
                                displayName += " " + nameParts[1].substring(0, 2); // Append first two letters of last name
                            }

                            let _html = `<span class="worker-name-badge dt-switch-worker-btn" data-id="${row.id}" data-total-amount="${row.total_amount}">`;
                            _html += `<i class="fa-solid fa-user"></i> `;
                            _html += displayName; // Show formatted name
                            _html += `</span>`;

                            return _html;
                        },
                    },
                    {
                        title: t("global.service"),
                        data: "service_name",
                        render: function (data, type, row, meta) {
                            let _html = `<span class="service-name-badge" style=" color: ${row.service_color == "#00FF" ? 'white' : 'black'}; background-color: ${row.service_color ?? "#FFFFFF"
                                };">`;

                            _html += data;

                            _html += `</span>`;

                            return _html;
                        },
                    },
                    // {
                    //     title: t("client.jobs.shift"),
                    //     data: null,
                    //     render: function (data, type, row, meta) {
                    //         return `<div class="rounded mb-1 shifts-badge">${data?.shifts}</div>`;
                    //     },
                    // },
                    {
                        title: t("admin.global.if_job_was_done"),
                        data: "is_job_done",
                        responsivePriority: 1,
                        width: "5%",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            return `<div class="d-flex justify-content-sm-start justify-content-md-center"> <span class="rounded " style="border: 1px solid #ebebeb; overflow: hidden"> <input ${buttonAttributes} type="checkbox" data-id="${row.id
                                }" class="form-control dt-if-job-done-checkbox" ${row.is_job_done ? "checked" : ""
                                } ${row.status == "cancel" || row.is_order_closed == 1
                                    ? "disabled"
                                    : ""
                                }/> </span> </div>`;
                        },
                    },
                    {
                        title: t("admin.global.time_for_job"),
                        data: null,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            return `<span class="text-nowrap"> ${data.duration_minutes / 60 + " " + "Hours"} </span>`;
                        },
                    },
                    {
                        title: t("admin.global.time_worker_actually"),
                        data: "actual_time_taken_minutes",
                        width: "10%",
                        orderable: false,
                        // className: "",
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

                            let _html = `<div class="d-flex justify-content-sm-start justify-content-md-center"> <div class="d-flex align-items-center ">`;

                            _html += `<button ${buttonAttributes} type="button" class="time-counter dt-time-counter-dec" data-id="${row.id
                                }" data-hours="${_hours}" ${isOrderClosed ? "disabled" : ""
                                } style="pointer-events: ${_hours === 0 ? "none" : "auto"
                                }, opacity: ${_hours === 0 ? 0.5 : 1};"> - </button>`;

                            _html += `<span class="mx-1 time-counter" style="background-color: ${_timeBGColor}"> ${_hours} </span>`;

                            _html += `<button ${buttonAttributes} type="button" class="time-counter dt-time-counter-inc" ${isOrderClosed ? "disabled" : ""
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
                        className: "dt-comment",
                        render: function (data, type, row, meta) {
                            return `<span class="text-wrap" data-id="${row.id}"> ${data} </span>`;
                        },
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
                        width: "3%",
                        className: "text-center",
                        render: function (data, type, row, meta) {
                            let _html =
                                '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                            if (role == "supervisor") {
                                _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("global.view")}</button>`;
                            } else {
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

                                    _html += `<button type="button" class="dropdown-item dt-change-shift-btn" data-date="${row.start_date}" data-id="${row.id}">Change Shift</button>`;

                                    _html += `<button type="button" class="dropdown-item dt-cancel-btn" data-id="${row.id}" data-group-id="${row.job_group_id}">${t("modal.cancel")}</button>`;
                                }

                                if (!row.is_assigned_to_supervisor) {
                                    _html += `<button type="button" class="dropdown-item dt-supervisor-btn" data-id="${row.id}">Assign Supervisor</button>`;
                                } else {
                                    _html += `<button type="button" class="dropdown-item dt-supervisor-btn" data-id="${row.id}">Remove job from assign</button>`;
                                }
                            }
                            _html += "</div> </div>";

                            return _html;
                        },
                    },
                ],
                ordering: true,
                searching: true,
                responsive: true,
                autoWidth: true,
                width: "100%",
                scrollX: true,
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
                ],
                initComplete: function () {
                    // Explicitly set the initial page after table initialization
                    const table = $(tableRef.current).DataTable();
                    table.page(initialPage).draw("page");
                },
            });
        } else {
            // Reuse the existing table and set the page directly
            const table = $(tableRef.current).DataTable();
            table.page(initialPage).draw("page");
        }
    };

    const getCurrentPageNumber = () => {
        const table = $(tableRef.current).DataTable();
        const pageInfo = table.page.info();
        return pageInfo.page + 1; // Adjusted to return 1-based page number
    };
    useEffect(() => {
        const searchParams = new URLSearchParams(location.search);
        const pageFromUrl = parseInt(searchParams.get("page")) || 1;
        const initialPage = pageFromUrl - 1;

        initializeDataTable(initialPage);

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
                    !e.target.closest(".dt-comment") &&
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
                    !e.target.closest(".dt-if-job-done-checkbox") &&
                    !e.target.closest(".dt-comment")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                navigate(`/admin/jobs/view/${_id}`);
            }
        });

        // Event listener for pagination
        $(tableRef.current).on("page.dt", function () {
            const currentPageNumber = getCurrentPageNumber();
            const url = new URL(window.location);
            url.searchParams.set("page", currentPageNumber);
            window.history.replaceState({}, "", url);
        });

        $(tableRef.current).on("click", ".dt-client-badge", function () {
            const _clientID = $(this).data("client-id");
            navigate(`/admin/clients/view/${_clientID}`);
        });

        $(tableRef.current).on("change", ".dt-if-job-done-checkbox", function () {
            const _id = $(this).data("id");
            handleJobDone(_id, this.checked);
        });

        $(tableRef.current).on("click", ".dt-time-counter-dec", function (e) {
            const _id = $(this).data("id");
            const _hours = parseFloat($(this).data("hours"));
            const _changedHours = (
                _hours > 0 ? parseFloat(_hours) - 0.25 : 0
            ).toFixed(2);
            handleWorkerActualTime(_id, _changedHours * 60);
        });

        $(tableRef.current).on("click", ".dt-time-counter-inc", function (e) {
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

        if (role != "supervisor") {
            $(tableRef.current).on("click", ".dt-switch-worker-btn", function () {
                const _id = $(this).data("id");
                const _totalAmount = $(this).data("total-amount");
                handleSwitchWorker({ id: _id, total_amount: _totalAmount });
            });


            $(tableRef.current).on("click", ".dt-change-worker-btn", function () {
                const _id = $(this).data("id");
                navigate(`/admin/jobs/${_id}/change-worker`);
            });
        }

        $(tableRef.current).on("click", ".dt-change-shift-btn", function () {
            const _id = $(this).data("id");
            const date = $(this).data("date");
            handleChangeShift({ _id, date });
            // navigate(`/admin/jobs/${_id}/change-shift`);
        });

        $(tableRef.current).on("click", ".dt-cancel-btn", function () {
            const _id = $(this).data("id");
            const _groupID = $(this).data("group-id");
            handleCancel({ id: _id, job_group_id: _groupID });
        });

        $(tableRef.current).on("click", ".dt-comment", function () {
            const _id = $(this).find("span").data("id"); // or more specific like `.find("span.dt-comment-text")`
            handleComent(_id);
        });

        $(tableRef.current).on("click", ".dt-supervisor-btn", function () {
            const _id = $(this).data("id");
            handleAssignJobToSupervisor(_id);
        });


        i18n.on("languageChanged", () => {
            $(tableRef.current).DataTable().destroy();
            initializeDataTable(initialPage);
        });

        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true);
                $(tableRef.current).off("click");
                $(tableRef.current).off("page.dt");
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

    const handleSaveComment = async (jobid) => {
        const res = await axios.post("/api/admin/jobs/save-comment", { job_id: jobid, comment: comment }, { headers });
        if (res.status == 200) {
            setComment('');
            setIsOpenCommentModal(false);
            $(tableRef.current).DataTable().draw();
        }
    }

    const handleWorkerActualTime = (_jobID, _value) => {
        axios
            .post(
                `/api/admin/jobs/${_jobID}/update-worker-actual-time`,
                { value: _value },
                { headers }
            )
            .then(() => {
                const table = $(tableRef.current).DataTable();

                const newHours = (_value / 60).toFixed(2);

                // Get both the parent row and child row
                const $mainRow = table.rows().nodes().to$().filter(`[data-id="${_jobID}"]`);
                const $childRow = $mainRow.hasClass("dtr-expanded")
                    ? $mainRow.next(".child")
                    : null;

                // === 🟢 1. Update in main row if visible ===
                const $targetTD = $mainRow.find("td").filter(function () {
                    return $(this).find(".dt-time-counter-dec").length > 0;
                });

                $targetTD.find(".dt-time-counter-dec").data("hours", newHours);
                $targetTD.find(".dt-time-counter-inc").data("hours", newHours);
                $targetTD.find("span.time-counter").text(newHours);

                // === 🟢 2. Update inside child row (collapsed view) ===
                if ($childRow && $childRow.length > 0) {
                    $childRow.find(".dt-time-counter-dec").data("hours", newHours);
                    $childRow.find(".dt-time-counter-inc").data("hours", newHours);
                    $childRow.find("span.time-counter").text(newHours);
                }

                // ✅ Optional: visual feedback
                $targetTD.find("span.time-counter").css("background", "#d4edda");
                if ($childRow) {
                    $childRow.find("span.time-counter").css("background", "#d4edda");
                }

                setTimeout(() => {
                    $targetTD.find("span.time-counter").css("background", "");
                    if ($childRow) {
                        $childRow.find("span.time-counter").css("background", "");
                    }
                }, 1000);
            })
            .catch((e) => {
                console.error("Error updating time", e);
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
        getWorkerToSwitch(_job.id);
        setSelectedJob(_job);
        setIsOpenSwitchWorker(true);
    };

    const handleChangeShift = (_job) => {
        setSelectedJob(_job._id);
        setSelectedJobDate(_job.date);
        console.log(_job);

        setIsChangeShiftModal(true);
    };

    const getWorkerToSwitch = (id) => {
        axios
            .get(`/api/admin/jobs/${id}/worker-to-switch`, {
                headers,
            })
            .then((response) => {
                setAllWorkers(response.data.workers);
            });
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

    useEffect(() => {
        if (!localStorage.getItem("selectedDateRangeJob") && selectedDateRange == "Day") {
            localStorage.setItem("selectedDateRangeJob", "Day");
        }
        if (!localStorage.getItem("selectedDateStepJob") && selectedDateStep == "Current") {
            localStorage.setItem("selectedDateStepJob", "Current");
        }
        if (!localStorage.getItem("doneFilter") && doneFilter == "All") {
            localStorage.setItem("doneFilter", "All");
        }
        if (!localStorage.getItem("startTimeFilter") && startTimeFilter == "All") {
            localStorage.setItem("startTimeFilter", "All");
        }
        const storedDateRange = localStorage.getItem("dateRangeJob");
        if (storedDateRange) {
            setDateRange(JSON.parse(storedDateRange)); // Parse JSON string back into an object
        }

        const storedFilter = localStorage.getItem("selectedDateRangeJob") || "Day"; // Default to "Day" if no value is set
        setSelectedDateRange(storedFilter);

        const storedFilter2 = localStorage.getItem("selectedDateStepJob") || "Current"; // Default to "Day" if no value is set
        setSelectedDateStep(storedFilter2);

        const storedFilter3 = localStorage.getItem("doneFilter") || "All"; // Default to "Day" if no value is set
        setDoneFilter(storedFilter3);

        const storedFilter4 = localStorage.getItem("startTimeFilter") || "All"; // Default to "Day" if no value is set
        setStartTimeFilter(storedFilter4);


    }, [selectedDateRange, selectedDateStep, doneFilter, startTimeFilter]);

    const resetLocalStorage = () => {
        localStorage.removeItem("selectedDateRangeJob");
        localStorage.removeItem("selectedDateStepJob");
        localStorage.removeItem("doneFilter");
        localStorage.removeItem("startTimeFilter");
        localStorage.removeItem("dateRangeJob");
        setSelectedDateRange("Week");
        setSelectedDateStep("Current");
        setDoneFilter("All");
        setStartTimeFilter("All");
        setDateRange({ start_date: "", end_date: "" });
        alert.success("Filters reset successfully");
    }

    return (
        <div id="container">
            <Sidebar />
            <div id="content" className="job-listing-page">
                <div className="titleBox customer-title mb-3 ">
                    <div className="row">
                        {
                            role != "supervisor" && (
                                <>
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
                                    <div className="col-sm-3 mt-3">
                                        <div className="d-flex align-items-center">
                                            <div style={{ fontWeight: "bold" }}>{t("global.filter")}</div>
                                            <div className="mx-3 d-flex align-items-center">
                                                <select
                                                    className="form-control"
                                                    value={doneFilter}
                                                    onChange={(e) => {
                                                        setDoneFilter(e.target.value);
                                                        localStorage.setItem("doneFilter", e.target.value);
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
                                                        localStorage.setItem("startTimeFilter", e.target.value);
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
                                        </div>
                                    </div>
                                    <div className="col-sm mt-3 d-none d-lg-block">
                                        <div className="d-flex align-items-center">
                                            {
                                                role != "supervisor" && (
                                                    <>
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
                                                    </>
                                                )
                                            }
                                            <button
                                                className="m-0 ml-4 btn border rounded px-3"
                                                style={{
                                                    background: "#2c3f51",
                                                    color: "white",
                                                }}
                                                onClick={() => resetLocalStorage()}
                                            >
                                                Reset Filters
                                            </button>
                                        </div>
                                    </div>
                                    {/* Mobile */}
                                    <div className="col-12 hidden-xl pl-0">
                                        <div className="job-buttons">
                                            {
                                                role != "supervisor" && (
                                                    <>
                                                        <button
                                                            className="ml-2 btn border rounded navyblue"
                                                            data-toggle="modal"
                                                            data-target="#exampleModal"
                                                        >
                                                            {t("global.exportTimeReport")}
                                                        </button>
                                                        <button
                                                            className="ml-2 btn border rounded"
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
                                                    </>
                                                )
                                            }
                                            <button
                                                className="ml-2 btn border rounded"
                                                style={{
                                                    background: "#2c3f51",
                                                    color: "white",
                                                }}
                                                onClick={() => resetLocalStorage()}
                                            >
                                                Reset Filters
                                            </button>
                                        </div>
                                    </div>
                                    <div className="col-md-12 d-none d-lg-block justify-content-between mt-2">
                                        <div className="d-flex align-items-center">
                                            <div
                                                style={{ fontWeight: "bold" }}
                                                className="mr-2"
                                            >
                                                {t("global.date_period")}
                                            </div>
                                            {/* {Object.entries(timeIntervals).map(([key, value]) => (
                                    <FilterButtons
                                        text={value}
                                        name={key}
                                        className="px-3 mr-1"
                                        key={key}
                                        selectedFilter={selectedDateRange}
                                        setselectedFilter={(status) => setSelectedDateRange(status)}
                                    />
                                ))} */}
                                            <FilterButtons
                                                text={t("global.day")}
                                                className="px-4 mr-1"
                                                selectedFilter={selectedDateRange}
                                                setselectedFilter={setSelectedDateRange}
                                                onClick={() => {
                                                    localStorage.setItem("selectedDateRangeJob", "Day");
                                                }}
                                            />
                                            <FilterButtons
                                                text={t("global.week")}
                                                className="px-4 mr-1"
                                                selectedFilter={selectedDateRange}
                                                setselectedFilter={setSelectedDateRange}
                                                onClick={() => {
                                                    localStorage.setItem("selectedDateRangeJob", "Week");
                                                }}
                                            />

                                            <FilterButtons
                                                text={t("global.month")}
                                                className="px-4 mr-3"
                                                selectedFilter={selectedDateRange}
                                                setselectedFilter={setSelectedDateRange}
                                                onClick={() => {
                                                    localStorage.setItem("selectedDateRangeJob", "Month");
                                                }}
                                            />

                                            <FilterButtons
                                                text={t("client.previous")}
                                                className="px-3 mr-1"
                                                selectedFilter={selectedDateStep}
                                                setselectedFilter={setSelectedDateStep}
                                                onClick={() => {
                                                    localStorage.setItem("selectedDateStepJob", "Previous");
                                                }}
                                            />
                                            <FilterButtons
                                                text={t("global.current")}
                                                className="px-3 mr-1"
                                                selectedFilter={selectedDateStep}
                                                setselectedFilter={setSelectedDateStep}
                                                onClick={() => {
                                                    localStorage.setItem("selectedDateStepJob", "Current");
                                                }}
                                            />
                                            <FilterButtons
                                                text={t("global.next")}
                                                className="px-3"
                                                selectedFilter={selectedDateStep}
                                                setselectedFilter={setSelectedDateStep}
                                                onClick={() => {
                                                    localStorage.setItem("selectedDateStepJob", "Next");
                                                }}
                                            />
                                        </div>
                                    </div>
                                    <div className="col-sm-12 mt-0 pl-2 d-flex d-lg-none">
                                        <div className="search-data m-0">
                                            <div className="action-dropdown dropdown d-flex align-items-center mt-md-4 mr-2 ">
                                                <div
                                                    className=" mr-3"
                                                    style={{ fontWeight: "bold" }}
                                                >
                                                    {t("global.date_period")}
                                                </div>
                                                <button
                                                    type="button"
                                                    className="btn btn-default navyblue dropdown-toggle"
                                                    data-toggle="dropdown"
                                                >
                                                    <i className="fa fa-filter"></i>
                                                </button>
                                                <span className="ml-2" style={{
                                                    padding: "6px",
                                                    border: "1px solid #ccc",
                                                    borderRadius: "5px"
                                                }}>{selectedDateRange || t("admin.leads.All")}</span>

                                                <div className="dropdown-menu dropdown-menu-right">

                                                    <button
                                                        className="dropdown-item"
                                                        onClick={() => {
                                                            setSelectedDateRange("Day");
                                                            localStorage.setItem("selectedDateRangeJob", "Day");
                                                        }}
                                                    >
                                                        {t("global.day")}
                                                    </button>
                                                    <button
                                                        className="dropdown-item"
                                                        onClick={() => {
                                                            setSelectedDateRange("Week");
                                                            localStorage.setItem("selectedDateRangeJob", "Week");
                                                        }}
                                                    >
                                                        {t("global.week")}
                                                    </button>
                                                    <button
                                                        className="dropdown-item"
                                                        onClick={() => {
                                                            setSelectedDateRange("Month");
                                                            localStorage.setItem("selectedDateRangeJob", "Month");
                                                        }}
                                                    >
                                                        {t("global.month")}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-sm-12 pl-2 d-flex d-lg-none">
                                        <div className="search-data mt-2">
                                            <div className="action-dropdown dropdown d-flex align-items-center mt-md-4 mr-2 ">
                                                <div
                                                    className=" mr-3"
                                                    style={{ fontWeight: "bold" }}
                                                >
                                                    {t("global.date_period")}{t("global.type")}
                                                </div>
                                                <button
                                                    type="button"
                                                    className="btn btn-default navyblue dropdown-toggle"
                                                    data-toggle="dropdown"
                                                >
                                                    <i className="fa fa-filter"></i>
                                                </button>
                                                <span className="ml-2" style={{
                                                    padding: "6px",
                                                    border: "1px solid #ccc",
                                                    borderRadius: "5px"
                                                }}>{selectedDateStep || t("admin.leads.All")}</span>

                                                <div className="dropdown-menu dropdown-menu-right">
                                                    <button
                                                        className="dropdown-item"
                                                        onClick={() => {
                                                            setSelectedDateStep("Previous");
                                                            localStorage.setItem("selectedDateStepJob", "Previous");
                                                        }}
                                                    >
                                                        {t("client.previous")}
                                                    </button>
                                                    <button
                                                        className="dropdown-item"
                                                        onClick={() => {
                                                            setSelectedDateStep("Current");
                                                            localStorage.setItem("selectedDateStepJob", "Current");
                                                        }}
                                                    >
                                                        {t("global.current")}
                                                    </button>
                                                    <button
                                                        className="dropdown-item"
                                                        onClick={() => {
                                                            setSelectedDateStep("Next");
                                                            localStorage.setItem("selectedDateStepJob", "Next");
                                                        }}
                                                    >
                                                        {t("global.next")}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-md-12 d-sm-flex justify-content-between my-1">
                                        <div className="d-flex align-items-center flex-wrap mt-2">
                                            <div
                                                className="mr-3"
                                                style={{ fontWeight: "bold" }}
                                            >
                                                {t("global.custom_date")}
                                            </div>
                                            <div className="d-flex align-items-center flex-wrap">
                                                <input
                                                    className="form-control"
                                                    type="date"
                                                    placeholder="From date"
                                                    name="from filter"
                                                    style={{ width: "fit-content" }}
                                                    value={dateRange.start_date}
                                                    onChange={(e) => {
                                                        const updatedDateRange = {
                                                            start_date: e.target.value,
                                                            end_date: dateRange.end_date,
                                                        };

                                                        setDateRange(updatedDateRange);

                                                        // Corrected: JSON.stringify instead of json.stringify
                                                        localStorage.setItem("dateRangeJob", JSON.stringify(updatedDateRange));
                                                    }}
                                                />
                                                <div className="mx-2">{t("global.to")}</div>
                                                <input
                                                    className="form-control"
                                                    type="date"
                                                    placeholder="To date"
                                                    name="to_filter"
                                                    style={{ width: "fit-content" }}
                                                    value={dateRange.end_date}
                                                    onChange={(e) => {
                                                        const updatedDateRange = {
                                                            start_date: dateRange.start_date,
                                                            end_date: e.target.value,
                                                        };

                                                        setDateRange(updatedDateRange);

                                                        // Corrected: JSON.stringify instead of json.stringify
                                                        localStorage.setItem("dateRangeJob", JSON.stringify(updatedDateRange));
                                                    }}
                                                />

                                            </div>

                                        </div>
                                    </div>
                                </>
                            )
                        }
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

                        <div className="col-md-12 justify-content-between my-2">
                            <div className="d-flex align-items-center flex-wrap">
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

                                <div className="form-check form-check-inline">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="inlineCheckbox3"
                                        onChange={() => {
                                            $(tableRef.current)
                                                .DataTable()
                                                .draw();
                                        }}
                                        ref={showAllWorkerFilterRef}
                                    />
                                    <label
                                        className="form-check-label"
                                        htmlFor="inlineCheckbox3"
                                    >
                                        Show all workers
                                    </label>
                                </div>
                                <div className="form-check form-check-inline">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="inlineCheckbox4"
                                        onChange={() => {
                                            $(tableRef.current)
                                                .DataTable()
                                                .draw();
                                        }}
                                        ref={showCancelJobsFilterRef}
                                    />
                                    <label
                                        className="form-check-label"
                                        htmlFor="inlineCheckbox4"
                                    >
                                        Show Cancelled Jobs
                                    </label>
                                </div>
                                <div className="form-check form-check-inline">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="inlineCheckbox5"
                                        onChange={() => {
                                            $(tableRef.current)
                                                .DataTable()
                                                .draw();
                                        }}
                                        ref={showSupervisorJobsFilterRef}
                                    />
                                    <label
                                        className="form-check-label"
                                        htmlFor="inlineCheckbox5"
                                    >
                                        {role == "supervisor" ? "Show assigned Jobs" : "Supervisor assigned Jobs"}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-2">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">{t("admin.leads.Options.sortBy")}</option>
                                <option value="0">{t("admin.dashboard.jobs.jobDate")}</option>
                                <option value="1">{t("admin.dashboard.jobs.client")}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body getjobslist p-0">
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


            {
                isOpenCommentModal && (
                    <Modal
                        size="md"
                        className="modal-container"
                        show={isOpenCommentModal}
                        onHide={() => {
                            setIsOpenCommentModal(false);
                        }}
                    >
                        <Modal.Header closeButton>
                            <Modal.Title>Comment</Modal.Title>
                        </Modal.Header>

                        <Modal.Body>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Comment
                                        </label>
                                        <textarea
                                            type="text"
                                            value={comment}
                                            name="comment"
                                            onChange={(e) => setComment(e.target.value)}
                                            className="form-control"
                                            required
                                            placeholder="Enter Note"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </Modal.Body>

                        <Modal.Footer>
                            <Button
                                type="button"
                                className="btn btn-secondary"
                                onClick={() => {
                                    setIsOpenCommentModal(false);
                                }}
                            >
                                {t("modal.close")}
                            </Button>
                            <Button
                                type="button"
                                onClick={() => handleSaveComment(selectedJob)}
                                className="btn btn-primary"
                            // disabled={loading}
                            >
                                {t("modal.save")}
                            </Button>
                        </Modal.Footer>
                    </Modal>
                )
            }

            {isOpenSwitchWorker && (
                <SwitchWorkerModal
                    setIsOpen={setIsOpenSwitchWorker}
                    isOpen={isOpenSwitchWorker}
                    job={selectedJob}
                    AllWorkers={AllWorkers}
                    onSuccess={() => $(tableRef.current).DataTable().draw()}
                />
            )}
            {isChangeShiftModal && (
                <ChangeShiftModal
                    setIsOpen={setIsChangeShiftModal}
                    isOpen={isChangeShiftModal}
                    job={selectedJob}
                    selectedDate={selectedJobDate}
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
