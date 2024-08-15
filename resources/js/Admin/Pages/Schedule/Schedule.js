import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Moment from "moment";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import FilterButtons from "../../../Components/common/FilterButton";


export default function Schedule() {
    const { t, i18n } = useTranslation();
    const navigate = useNavigate();
    const [isLoading, setIsLoading] = useState(false);
    const [filter, setFilter] = useState("All");
    const tableRef = useRef(null);

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


    const initializeDataTable = () => {
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
                            return `<a href="/admin/clients/view/${row.client_id}" target="_blank" class="dt-client-link"> ${data} </a>`;
                        },
                    },
                    {
                        title: t("admin.dashboard.pending.contact"),
                        data: "phone",
                    },
                    {
                        title: t("admin.global.Address"),
                        data: "address_name",
                        render: function (data, type, row, meta) {
                            if (data) {
                                return `<a href="https://maps.google.com?q=${row.geo_address}" target="_blank" class="" style="color: black; text-decoration: underline;"> ${data} </a>`;
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

                            // return `<span style="color: ${color};">${data}</span>`;
                            return `<p style="background-color: #efefef; color: ${color}; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        ${data}
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
            let _clientID = null;
            if (e.target.closest("tr.dt-row")) {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-client-link") &&
                    !e.target.closest(".dt-address-link") &&
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
                    !e.target.closest(".dt-address-link")
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

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            const _clientID = $(this).data("client-id");
            navigate(`/admin/schedule/view/${_clientID}?sid=${_id}`);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
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
    }, []);

    // useEffect(() => {
    //     $(tableRef.current).DataTable({
    //         processing: true,
    //         serverSide: true,
    //         ajax: {
    //             url: "/api/admin/schedule",
    //             type: "GET",
    //             beforeSend: function (request) {
    //                 request.setRequestHeader(
    //                     "Authorization",
    //                     `Bearer ` + localStorage.getItem("admin-token")
    //                 );
    //             },
    //         },
    //         order: [[0, "desc"]],
    //         columns: [
    //             {
    //                 title:  t("admin.dashboard.pending.scheduled"),
    //                 data: "start_date",
    //                 render: function (data, type, row, meta) {
    //                     let _html = "";

    //                     if (row.start_date) {
    //                         _html += `<span class="text-blue"> ${Moment(
    //                             row.start_date
    //                         ).format("DD/MM/Y")} </span>`;

    //                         _html += `<br /> <span class="text-blue"> ${Moment(
    //                             row.start_date
    //                         ).format("dddd")} </span>`;

    //                         if (row.start_time && row.end_time) {
    //                             _html += `<br /> <span class="text-green"> Start : ${row.start_time} </span>`;
    //                             _html += `<br /> <span class="text-danger"> End : ${row.end_time} </span>`;
    //                         }
    //                     }

    //                     return _html;
    //                 },
    //             },
    //             {
    //                 title:  t("admin.global.Name"),
    //                 data: "name",
    //                 render: function (data, type, row, meta) {
    //                     return `<a href="/admin/clients/view/${row.client_id}" target="_blank" class="dt-client-link"> ${data} </a>`;
    //                 },
    //             },
    //             {
    //                 title:  t("admin.dashboard.pending.contact"),
    //                 data: "phone",
    //             },
    //             {
    //                 title:  t("admin.global.Address"),
    //                 data: "address_name",
    //                 render: function (data, type, row, meta) {
    //                     if (data) {
    //                         return `<a href="https://maps.google.com?q=${row.geo_address}" target="_blank" class="" style="color: black; text-decoration: underline;"> ${data} </a>`;
    //                     } else {
    //                         return "NA";
    //                     }
    //                 },
    //             },
    //             {
    //                 title: t("client.meeting.attender"),
    //                 data: "attender_name",
    //             },
    //             {
    //                 title:  t("admin.global.Status"),
    //                 data: "booking_status",
    //                 render: function (data, type, row, meta) {
    //                     let color = "";
    //                     if (data == "pending") {
    //                         color = "purple";
    //                     } else if (data == "confirmed" || data == "completed") {
    //                         color = "green";
    //                     } else {
    //                         color = "red";
    //                     }

    //                     // return `<span style="color: ${color};">${data}</span>`;
    //                     return `<p style="background-color: #efefef; color: ${color}; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
    //                                 ${data}
    //                             </p>`;
    //                 },
    //             },
    //             {
    //                 title: t("admin.global.Action"),
    //                 data: "action",
    //                 orderable: false,
    //                 responsivePriority: 1,
    //                 render: function (data, type, row, meta) {
    //                     let _html =
    //                         '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

    //                     _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}" data-client-id="${row.client_id}">${t("admin.leads.view")}</button>`;

    //                     _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>`;

    //                     _html += "</div> </div>";

    //                     return _html;
    //                 },
    //             },
    //         ],
    //         ordering: true,
    //         searching: true,
    //         responsive: true,
    //         createdRow: function (row, data, dataIndex) {
    //             $(row).addClass("dt-row custom-row-class");
    //             $(row).attr("data-id", data.id);
    //             $(row).attr("data-client-id", data.client_id);
    //         },
    //         columnDefs: [
    //             {
    //                 targets: '_all',
    //                 createdCell: function (td, cellData, rowData, row, col) {
    //                     $(td).addClass('custom-cell-class ');
    //                 }
    //             }
    //         ]
    //     });

    //        // Customize the search input
    //        const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
    //        $("div.dt-search").append(searchInputWrapper);
    //        $("div.dt-search").addClass("position-relative");


    //     $(tableRef.current).on("click", "tr.dt-row,tr.child", function (e) {
    //         let _id = null;
    //         let _clientID = null;
    //         if (e.target.closest("tr.dt-row")) {
    //             if (
    //                 !e.target.closest(".dropdown-toggle") &&
    //                 !e.target.closest(".dropdown-menu") &&
    //                 !e.target.closest(".dt-client-link") &&
    //                 !e.target.closest(".dt-address-link") &&
    //                 (!tableRef.current.classList.contains("collapsed") ||
    //                     !e.target.closest(".dtr-control"))
    //             ) {
    //                 _id = $(this).data("id");
    //                 _clientID = $(this).data("client-id");
    //             }
    //         } else {
    //             if (
    //                 !e.target.closest(".dropdown-toggle") &&
    //                 !e.target.closest(".dropdown-menu") &&
    //                 !e.target.closest(".dt-client-link") &&
    //                 !e.target.closest(".dt-address-link")
    //             ) {
    //                 _id = $(e.target).closest("tr.child").prev().data("id");
    //                 _clientID = $(e.target)
    //                     .closest("tr.child")
    //                     .prev()
    //                     .data("client-id");
    //             }
    //         }

    //         if (_id) {
    //             navigate(`/admin/schedule/view/${_clientID}?sid=${_id}`);
    //         }
    //     });

    //     $(tableRef.current).on("click", ".dt-view-btn", function () {
    //         const _id = $(this).data("id");
    //         const _clientID = $(this).data("client-id");
    //         navigate(`/admin/schedule/view/${_clientID}?sid=${_id}`);
    //     });

    //     $(tableRef.current).on("click", ".dt-delete-btn", function () {
    //         const _id = $(this).data("id");
    //         handleDelete(_id);
    //     });

    //     return function cleanup() {
    //         $(tableRef.current).DataTable().destroy(true);
    //     };
    // }, []);

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
        if (filter == "All") {
            $(tableRef.current).DataTable().column(5).search(null).draw();
        } else {
            $(tableRef.current).DataTable().column(5).search(filter).draw();
        }
    }, [filter]);
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("client.common.meetings")}</h1>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">{t("admin.leads.Options.sortBy")}</option>
                                <option value="0">{t("j_status.scheduled")}</option>
                                <option value="5">{t("global.status")}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className=" mb-4 d-none d-lg-block">
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
                                className="px-3 mr-1 ml-4"
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
                                    />
                                );
                            })}
                        </div>
                    </div>
                </div>
                <div className="card " style={{ boxShadow: "none" }}>
                    <div className="card-body pl-0 pr-0">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table table-bordered"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <FullPageLoader visible={isLoading} />
        </div>
    );
}
