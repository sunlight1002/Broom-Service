import axios from "axios";
import Moment from "moment";
import React, { useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import { CSVLink } from "react-csv";
import { renderToString } from "react-dom/server";
import { useTranslation } from "react-i18next";
import { json, useNavigate, useLocation } from "react-router-dom";
import { Rating } from "react-simple-star-rating";
import { Tooltip } from "react-tooltip";
import "react-tooltip/dist/react-tooltip.css";
import Swal from "sweetalert2";
import { Link } from "react-router-dom";
import { Button, Modal } from "react-bootstrap";
import i18next from "i18next";
import { getDataTableStateConfig, TABLE_IDS } from '../../../Utils/datatableStateManager';

import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import $ from "jquery";

import FilterButtons from "../../../Components/common/FilterButton";
import Sidebar from "../../Layouts/Sidebar";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function Conflicts() {
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

    const tableRef = useRef(null);
    const doneFilterRef = useRef(null);
    const startTimeFilterRef = useRef(null);
    const startDateRef = useRef(null);
    const endDateRef = useRef(null);
    const actualTimeExceedFilterRef = useRef(null);
    const hasNoWorkerFilterRef = useRef(null);
    const [AllWorkers, setAllWorkers] = useState([]);

    const alert = useAlert();
    const navigate = useNavigate();
    const location = useLocation();

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

    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            const baseConfig = {
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/conflicts",
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                    // data: function (d) {
                    //     d.start_time_filter = startTimeFilterRef.current.value;
                    //     d.start_date = startDateRef.current.value;
                    //     d.end_date = endDateRef.current.value;
                    // },
                },
                order: [[0, "asc"]],
                columns: [
                    { 
                        data: "date", 
                        title: t("global.date"),
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

                            let serviceHtml = `<div class="rounded mb-1 shifts-badge">${row?.shift}</div>`;
                            // return `<div>${formattedDate} </br> ${serviceHtml}</div>`;
                            return `<div>${formattedDate} </br> <span style="color: #f30909;">Hours: </span>${row?.hours}</br>${serviceHtml}</div>`;

                        }, 
                    },
                    { 
                        data: "job_id", 
                        title: t("global.job_id"),
                        render: function (data, type, row, meta) {
                            return `<span class="dt-job-btn" data-id="${data}">${data}</span>`;
                        },
                    },
                    { data: "worker_name", title: t("global.worker") },
                    { data: "client_name", title: t("global.client") },
                    { data: "conflict_client_name", title: t("global.conflictClient") },
                    { data: "conflict_job_id", title: t("global.conflictJob") },
                    // { 
                    //     data: "hours", 
                    //     title: t("global.hours"),
                    // },
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
                        targets: "_all",
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass("custom-cell-class");
                        },
                    },
                ],
                initComplete: function () {
                    // Explicitly set the initial page after table initialization
                    const table = $(tableRef.current).DataTable();
                    table.page(initialPage).draw("page");
                },
            };

            // Add state management configuration
            const stateConfig = getDataTableStateConfig(TABLE_IDS.CONFLICTS, {
                onStateLoad: (settings, data) => {
                    console.log('Conflicts table state loaded:', data);
                },
                onStateSave: (settings, data) => {
                    console.log('Conflicts table state saved:', data);
                }
            });

            const fullConfig = { ...baseConfig, ...stateConfig };

            $(tableRef.current).DataTable(fullConfig);
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

        // Event listener for pagination
        $(tableRef.current).on("page.dt", function () {
            const currentPageNumber = getCurrentPageNumber();

            // Update the URL with the page number
            const url = new URL(window.location);
            url.searchParams.set("page", currentPageNumber);

            // Use replaceState to avoid adding new history entry
            window.history.replaceState({}, "", url);
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

        $(tableRef.current).on("click", ".dt-job-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/jobs/view/${_id}`);
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
            initializeDataTable(initialPage);
        });

        // Cleanup event listeners and destroy DataTable when unmounting
        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true); // Ensure proper cleanup
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
        getWorkerToSwitch(_job.id);
        setSelectedJob(_job);
        setIsOpenSwitchWorker(true);
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
        if (!localStorage.getItem("selectedDateRange") && selectedDateRange == "Week") {
            localStorage.setItem("selectedDateRange", "Week");
        }
        if (!localStorage.getItem("selectedDateStep") && selectedDateStep == "Current") {
            localStorage.setItem("selectedDateStep", "Current");
        }
        if (!localStorage.getItem("doneFilter") && doneFilter == "All") {
            localStorage.setItem("doneFilter", "All");
        }
        if (!localStorage.getItem("startTimeFilter") && startTimeFilter == "All") {
            localStorage.setItem("startTimeFilter", "All");
        }
        const storedDateRange = localStorage.getItem("dateRange");
        if (storedDateRange) {
            setDateRange(JSON.parse(storedDateRange)); // Parse JSON string back into an object
        }

        const storedFilter = localStorage.getItem("selectedDateRange") || "Day"; // Default to "Day" if no value is set
        setSelectedDateRange(storedFilter);

        const storedFilter2 = localStorage.getItem("selectedDateStep") || "Current"; // Default to "Day" if no value is set
        setSelectedDateStep(storedFilter2);

        const storedFilter3 = localStorage.getItem("doneFilter") || "All"; // Default to "Day" if no value is set
        setDoneFilter(storedFilter3);

        const storedFilter4 = localStorage.getItem("startTimeFilter") || "All"; // Default to "Day" if no value is set
        setStartTimeFilter(storedFilter4);


    }, [selectedDateRange, selectedDateStep, doneFilter, startTimeFilter]);

    const resetLocalStorage = () => {
        localStorage.removeItem("selectedDateRange");
        localStorage.removeItem("selectedDateStep");
        localStorage.removeItem("doneFilter");
        localStorage.removeItem("startTimeFilter");
        localStorage.removeItem("dateRange");
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
                        <div className="col-sm-2 col-4">
                            <h1 className="page-title">{t("admin.sidebar.conflicts")}</h1>
                        </div>

                        {/* <div className="App" style={{ display: "none" }}>
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
                                <button
                                    className="m-0 ml-4 btn border rounded px-3"
                                    style={!probbtn ? {
                                        background: "#2c3f51",
                                        color: "white",
                                    } : {
                                        background: "white",
                                        color: "black",
                                    }}
                                    onClick={() => resetLocalStorage()}
                                >
                                    Reset Filters
                                </button>
                            </div>
                        </div>
                        <div className="col-12 hidden-xl pl-0">
                            <div className="job-buttons">
                                <button
                                    className="ml-2 btn border rounded"
                                    style={!probbtn ? {
                                        background: "#2c3f51",
                                        color: "white",
                                    } : {
                                        background: "white",
                                        color: "black",
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
                                                localStorage.setItem("selectedDateRange", "Day");
                                            }}
                                        >
                                            {t("global.day")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                setSelectedDateRange("Week");
                                                localStorage.setItem("selectedDateRange", "Week");
                                            }}
                                        >
                                            {t("global.week")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                setSelectedDateRange("Month");
                                                localStorage.setItem("selectedDateRange", "Month");
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
                                                localStorage.setItem("selectedDateStep", "Previous");
                                            }}
                                        >
                                            {t("client.previous")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                setSelectedDateStep("Current");
                                                localStorage.setItem("selectedDateStep", "Current");
                                            }}
                                        >
                                            {t("global.current")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                setSelectedDateStep("Next");
                                                localStorage.setItem("selectedDateStep", "Next");
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
                                            localStorage.setItem("dateRange", JSON.stringify(updatedDateRange));
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
                                            localStorage.setItem("dateRange", JSON.stringify(updatedDateRange));
                                        }}
                                    />

                                </div>
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
                        </div> */}
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
                    </div>
                </div>
            </div>

            <Tooltip id="slot-tooltip" />
        </div>
    );
}
