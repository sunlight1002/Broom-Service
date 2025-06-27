import React, { useState, useEffect, useRef } from "react";
import { Button, Modal } from "react-bootstrap";

import axios from "axios";
import Moment from "moment";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAlert } from "react-alert";

import $, { data } from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import FilterButtons from "../../../Components/common/FilterButton";
import { getMobileStatusBadgeHtml } from '../../../Utils/common.utils';

export default function Schedule() {
    const { t, i18n } = useTranslation();
    const navigate = useNavigate();
    const [isLoading, setIsLoading] = useState(false);
    const [filter, setFilter] = useState("All");
    const tableRef = useRef(null);
    const [statusModal, setStatusModal] = useState(false)
    const [selectedMeetingId, setSelectedMeetingId] = useState(null)
    const [status, setStatus] = useState("");
    const alert = useAlert();
    const [dateRange, setDateRange] = useState({
        start_date: "",
        end_date: "",
    });

    const [selectedDateRange, setSelectedDateRange] = useState("Day");
    const [selectedDateStep, setSelectedDateStep] = useState("Current");

    const startDateRef = useRef(null);
    const endDateRef = useRef(null);
    const filterRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const meetingStatuses = [
        t("admin.schedule.options.meetingStatus.Pending"),
        t("admin.schedule.options.meetingStatus.Confirmed"),
        t("admin.schedule.options.meetingStatus.Completed"),
        t("admin.schedule.options.meetingStatus.Declined"),
        t("admin.schedule.options.meetingStatus.rescheduled"),
    ];

    const statusArr = {
        "pending": t("admin.schedule.options.meetingStatus.Pending"),
        "confirmed": t("admin.schedule.options.meetingStatus.Confirmed"),
        "completed": t("admin.schedule.options.meetingStatus.Completed"),
        "declined": t("admin.schedule.options.meetingStatus.Declined"),
        // "rescheduled": t("admin.schedule.options.meetingStatus.rescheduled"),
    };

    const handleModal = (id, status) => {
        if (status != "rescheduled") {
            setStatus(status)
        }
        setSelectedMeetingId(id);
        setStatusModal(true);
    };

    const handleUpdateStatus = async () => {
        try {
            const res = await axios.put(`/api/admin/change-schedule-status/${selectedMeetingId}`, { status }, { headers });
            if (res.status === 200) {
                setStatusModal(false);
                alert.success(res?.data?.message);
                $(tableRef.current).DataTable().draw();
            }
        } catch (error) {
            console.log(error);
        }
    }

    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/schedule",
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                    data: function (d) {
                        d.filter = filterRef.current.value;
                        d.start_date = startDateRef.current.value;
                        d.end_date = endDateRef.current.value;
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: t("admin.dashboard.pending.scheduled"),
                        data: "start_date",
                        render: function (data, type, row, meta) {
                            let _html = "";

                            if (row.start_date) {
                                _html += `<span class="text-blue"> ${Moment(
                                    row.start_date
                                ).format("DD/MM/Y")} </span>`;

                                _html += `<br /> <span class="text-blue"> ${Moment(
                                    row.start_date
                                ).format("dddd")} </span>`;

                                if (row.start_time && row.end_time) {
                                    _html += `<br /> <span class="text-green"> Start : ${row.start_time} </span>`;
                                    _html += `<br /> <span class="text-danger"> End : ${row.end_time} </span>`;
                                }
                            }

                            return _html;
                        },
                    },
                    {
                        title: t("admin.global.Name"),
                        data: "name",
                        render: function (data, type, row, meta) {
                            const badge = getMobileStatusBadgeHtml(row.booking_status);
                            return `<a href="/admin/clients/view/${row.client_id}" target="_blank" class="dt-client-link"> ${data} ${badge}</a>`;
                        },
                    },
                    {
                        title: t("admin.dashboard.pending.contact"),
                        data: "phone",
                        render: function (data) {
                            return `+${data}`;
                        }
                    },
                    {
                        title: t("admin.global.Address"),
                        data: "address_name",
                        render: function (data, type, row, meta) {
                            if (data) {
                                if (row.latitude && row.longitude) {
                                    return `<a href="https://maps.google.com/?q=${row.latitude},${row.longitude}" target="_blank" style="color: black; text-decoration: underline;">
                                    ${row.city ? row.city + ", " : ""} ${data}
                                </a>`;
                                } else {
                                    return `<a href="https://maps.google.com?q=${row.geo_address}" target="_blank" class="" style="color: black; text-decoration: underline;"> ${row.city ? row.city + ", " : ""} ${data} </a>`;
                                }
                            } else {
                                return "NA";
                            }
                        },
                    },
                    {
                        title: t("client.meeting.attender"),
                        data: "attender_name",
                    },
                    {
                        title: t("admin.global.Status"),
                        data: "booking_status",
                        render: function (data, type, row, meta) {
                            let color = "";
                            if (data == "pending") {
                                color = "purple";
                            } else if (data == "confirmed" || data == "completed") {
                                color = "green";
                            } else {
                                color = "red";
                            }
                            const badge = getMobileStatusBadgeHtml(data);
                            return `<p class="dt-meeting-status" data-id="${row.id}" data-status="${data}" style="background-color: #efefef; color: ${color}; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        ${data} ${badge}
                                    </p>`;
                        },
                    },
                    {
                        title: t("admin.global.Action"),
                        data: "action",
                        orderable: false,
                        responsivePriority: 1,
                        render: function (data, type, row, meta) {
                            let _html =
                                '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                            _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}" data-client-id="${row.client_id}">${t("admin.leads.view")}</button>`;

                            _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>`;

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
                    $(row).attr("data-client-id", data.client_id);
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
            let _clientID = null;
            if (e.target.closest("tr.dt-row")) {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-client-link") &&
                    !e.target.closest(".dt-address-link") &&
                    !e.target.closest(".dt-meeting-status") &&
                    (!tableRef.current.classList.contains("collapsed") ||
                        !e.target.closest(".dtr-control"))
                ) {
                    _id = $(this).data("id");
                    _clientID = $(this).data("client-id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-client-link") &&
                    !e.target.closest(".dt-address-link") &&
                    !e.target.closest(".dt-meeting-status")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                    _clientID = $(e.target)
                        .closest("tr.child")
                        .prev()
                        .data("client-id");
                }
            }

            if (_id) {
                navigate(`/admin/schedule/view/${_clientID}?sid=${_id}`);
            }
        });

        // Event listener for pagination
        $(tableRef.current).on("page.dt", function () {
            const currentPageNumber = getCurrentPageNumber();

            // Update the URL with the page number
            const url = new URL(window.location);
            url.searchParams.set("page", currentPageNumber);

            // Use replaceState to avoid adding new history entry
            window.history.replaceState({}, "", url);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            const _clientID = $(this).data("client-id");
            navigate(`/admin/schedule/view/${_clientID}?sid=${_id}`);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        $(tableRef.current).on("click", ".dt-meeting-status", function () {
            const _id = $(this).data("id");
            const _status = $(this).data("status");
            handleModal(_id, _status);
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
    }, [location.search]);


    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Meeting!",
        }).then((result) => {
            if (result.isConfirmed) {
                setIsLoading(true);

                axios
                    .delete(`/api/admin/schedule/${id}`, { headers })
                    .then((response) => {
                        setIsLoading(false);

                        Swal.fire(
                            "Deleted!",
                            "Meeting has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            $(tableRef.current).DataTable().draw();
                        }, 1000);
                    })
                    .catch((e) => {
                        setIsLoading(false);

                        Swal.fire({
                            title: "Error!",
                            text: e.response.data.message,
                            icon: "error",
                        });
                    });
            }
        });
    };

    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order(parseInt(colIdx), "asc").draw();
    };

    useEffect(() => {
        $(tableRef.current).DataTable().draw();
    }, [filter, dateRange]);


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
        if (!localStorage.getItem("selectedDateRangeMeeting") && selectedDateRange == "Day") {
            localStorage.setItem("selectedDateRangeMeeting", "Day");
        }
        if (!localStorage.getItem("selectedDateStepMeeting") && selectedDateStep == "Current") {
            localStorage.setItem("selectedDateStepMeeting", "Current");
        }
        const storedDateRange = localStorage.getItem("dateRangeMeeting");
        if (storedDateRange) {
            setDateRange(JSON.parse(storedDateRange)); // Parse JSON string back into an object
        }

        const storedFilter = localStorage.getItem("selectedDateRangeMeeting") || "Day"; // Default to "Day" if no value is set
        setSelectedDateRange(storedFilter);

        const storedFilter2 = localStorage.getItem("selectedDateStepMeeting") || "Current"; // Default to "Day" if no value is set
        setSelectedDateStep(storedFilter2);

        const storedFilter3 = localStorage.getItem("selectedFilterMeeting") || "All";
        setFilter(storedFilter3);

    }, [selectedDateRange, selectedDateStep]);

    const resetLocalStorage = () => {
        localStorage.removeItem("selectedDateRangeMeeting");
        localStorage.removeItem("selectedDateStepMeeting");
        localStorage.removeItem("dateRangeMeeting");
        localStorage.removeItem("selectedFilterMeeting");
        setSelectedDateRange("Day");
        setSelectedDateStep("Current");
        setFilter("All");
        // setDateRange({ start_date: "", end_date: "" });
        alert.success("Filters reset successfully");
        // localStorage.setItem(
        //     "dateRange",
        //     JSON.stringify({ start_date: "", end_date: "" })
        // );

    }

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("client.common.meetings")}</h1>
                        </div>
                        {/* <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">{t("admin.leads.Options.sortBy")}</option>
                                <option value="0">{t("j_status.scheduled")}</option>
                                <option value="5">{t("global.status")}</option>
                            </select>
                        </div> */}
                    </div>
                </div>
                <div className="d-none d-lg-block">
                    <div className="row">
                        <div
                            style={{
                                fontWeight: "bold",
                                marginTop: 10,
                                marginLeft: 15,
                            }}
                        >
                            {t("global.filter")}
                        </div>
                        <div>
                            <FilterButtons
                                text={t("admin.global.All")}
                                className="px-3 mr-1 ml-2"
                                selectedFilter={filter}
                                setselectedFilter={setFilter}
                            />
                            {meetingStatuses.map((_status, _index) => {
                                return (
                                    <FilterButtons
                                        text={_status}
                                        className="mr-1 px-3 ml-2"
                                        key={_index}
                                        selectedFilter={filter}
                                        setselectedFilter={setFilter}
                                        onClick={() => {
                                            localStorage.setItem("selectedFilterMeeting", _status);
                                        }}
                                    />
                                );
                            })}
                        </div>
                        <input
                            type="hidden"
                            value={filter}
                            ref={filterRef}
                        />
                    </div>
                </div>
                <div className="row">
                    <div className="col-sm-12 mt-0 pl-3 d-flex d-lg-none">
                        <div className="search-data m-0">
                            <div className="action-dropdown dropdown d-flex align-items-center mt-md-4 mr-2 ">
                                <div
                                    className=" mr-3"
                                    style={{ fontWeight: "bold" }}
                                >
                                    {t("global.filter")}
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
                                }}>{filter || t("admin.leads.All")}</span>

                                <div className="dropdown-menu dropdown-menu-right">
                                    <button
                                        className="dropdown-item"
                                        onClick={() => {
                                            setFilter("All");
                                            localStorage.setItem("selectedFilterMeeting", "Day");
                                        }}
                                    >
                                        {t("admin.global.All")}
                                    </button>

                                    {meetingStatuses.map((_status, _index) => {
                                        return (
                                            <button
                                                className="dropdown-item"
                                                key={_index}
                                                onClick={() => {
                                                    setFilter(_status);
                                                    localStorage.setItem("selectedFilterMeeting", _status);
                                                }}
                                            >
                                                {_status}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
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
                                    localStorage.setItem("selectedDateRangeMeeting", "Day");
                                }}
                            />
                            <FilterButtons
                                text={t("global.week")}
                                className="px-4 mr-1"
                                selectedFilter={selectedDateRange}
                                setselectedFilter={setSelectedDateRange}
                                onClick={() => {
                                    localStorage.setItem("selectedDateRangeMeeting", "Week");
                                }}
                            />

                            <FilterButtons
                                text={t("global.month")}
                                className="px-4 mr-3"
                                selectedFilter={selectedDateRange}
                                setselectedFilter={setSelectedDateRange}
                                onClick={() => {
                                    localStorage.setItem("selectedDateRangeMeeting", "Month");
                                }}
                            />

                            <FilterButtons
                                text={t("client.previous")}
                                className="px-3 mr-1"
                                selectedFilter={selectedDateStep}
                                setselectedFilter={setSelectedDateStep}
                                onClick={() => {
                                    localStorage.setItem("selectedDateStepMeeting", "Previous");
                                }}
                            />
                            <FilterButtons
                                text={t("global.current")}
                                className="px-3 mr-1"
                                selectedFilter={selectedDateStep}
                                setselectedFilter={setSelectedDateStep}
                                onClick={() => {
                                    localStorage.setItem("selectedDateStepMeeting", "Current");
                                }}
                            />
                            <FilterButtons
                                text={t("global.next")}
                                className="px-3"
                                selectedFilter={selectedDateStep}
                                setselectedFilter={setSelectedDateStep}
                                onClick={() => {
                                    localStorage.setItem("selectedDateStepMeeting", "Next");
                                }}
                            />
                        </div>
                    </div>
                    <div className="col-sm-12 mt-2 pl-2 d-flex d-lg-none">
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
                                            localStorage.setItem("selectedDateRangeMeeting", "Day");
                                        }}
                                    >
                                        {t("global.day")}
                                    </button>
                                    <button
                                        className="dropdown-item"
                                        onClick={() => {
                                            setSelectedDateRange("Week");
                                            localStorage.setItem("selectedDateRangeMeeting", "Week");
                                        }}
                                    >
                                        {t("global.week")}
                                    </button>
                                    <button
                                        className="dropdown-item"
                                        onClick={() => {
                                            setSelectedDateRange("Month");
                                            localStorage.setItem("selectedDateRangeMeeting", "Month");
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
                                            localStorage.setItem("selectedDateStepMeeting", "Previous");
                                        }}
                                    >
                                        {t("client.previous")}
                                    </button>
                                    <button
                                        className="dropdown-item"
                                        onClick={() => {
                                            setSelectedDateStep("Current");
                                            localStorage.setItem("selectedDateStepMeeting", "Current");
                                        }}
                                    >
                                        {t("global.current")}
                                    </button>
                                    <button
                                        className="dropdown-item"
                                        onClick={() => {
                                            setSelectedDateStep("Next");
                                            localStorage.setItem("selectedDateStepMeeting", "Next");
                                        }}
                                    >
                                        {t("global.next")}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    style={{
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "left",
                    }}
                    className="hide-scrollbar my-2"
                >
                    <p className="mr-2" style={{ fontWeight: "bold" }}>{t("admin.schedule.date")}</p>

                    <div className="d-flex align-items-center flex-wrap">
                        <input
                            className="form-control calender"
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
                                localStorage.setItem(
                                    "dateRangeMeeting",
                                    JSON.stringify(updatedDateRange)
                                );
                            }}
                        />
                        <div className="mx-2">-</div>
                        <input
                            className="form-control calender mr-1"
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
                                localStorage.setItem(
                                    "dateRangeMeeting",
                                    JSON.stringify(updatedDateRange)
                                );
                            }}
                        />
                        <button
                            type="button"
                            className="btn btn-default navyblue mx-1 my-1"
                            style={{
                                padding: ".195rem .6rem",
                            }}
                            onClick={() => resetLocalStorage()}
                        >
                            {t("admin.schedule.reset")}
                        </button>
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
                <div className="card " style={{ boxShadow: "none" }}>
                    <div className="card-body pl-0 pr-0 pt-0">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table table-bordered"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <Modal
                size="md"
                className="modal-container"
                show={statusModal}
                onHide={() => setStatusModal(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Change Status</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">{t("global.status")}</label>

                                <select
                                    name="status"
                                    onChange={(e) => setStatus(e.target.value)}
                                    value={status}
                                    className="form-control mb-3"
                                >
                                    <option value="">---select status---</option>
                                    {Object.keys(statusArr).map((s) => (
                                        <option key={s} value={s}>
                                            {statusArr[s]}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => setStatusModal(false)}
                    >
                        {t("modal.close")}
                    </Button>
                    <Button
                        type="button"
                        onClick={handleUpdateStatus}
                        className="btn btn-primary"
                    >
                        {t("global.send")}
                    </Button>
                </Modal.Footer>
            </Modal>

            <FullPageLoader visible={isLoading} />
        </div>
    );
}
