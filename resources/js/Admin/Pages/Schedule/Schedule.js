import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import Moment from "moment";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function Schedule() {
    const navigate = useNavigate();
    const [isLoading, setIsLoading] = useState(false);

    const tableRef = useRef(null);

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
                    title: "Scheduled",
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
                    title: "Name",
                    data: "name",
                    render: function (data, type, row, meta) {
                        return `<a href="/admin/view-client/${row.client_id}" target="_blank" class="dt-client-link"> ${data} </a>`;
                    },
                },
                {
                    title: "Contact",
                    data: "phone",
                },
                {
                    title: "Address",
                    data: "address_name",
                    render: function (data, type, row, meta) {
                        if (data) {
                            return `<a href="https://maps.google.com?q=${row.latitude},${row.longitude}" target="_blank" class="dt-address-link"> ${data} </a>`;
                        } else {
                            return "NA";
                        }
                    },
                },
                {
                    title: "Attender",
                    data: "attender_name",
                },
                {
                    title: "Status",
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

                        return `<span style="color: ${color};">${data}</span>`;
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

                        _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}" data-client-id="${row.client_id}">View</button>`;

                        _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">Delete</button>`;

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
                $(row).attr("data-client-id", data.client_id);
            },
        });

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
                navigate(`/admin/view-schedule/${_clientID}?sid=${_id}`);
            }
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            const _clientID = $(this).data("client-id");
            navigate(`/admin/view-schedule/${_clientID}?sid=${_id}`);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        return function cleanup() {
            $(tableRef.current).DataTable().destroy(true);
        };
    }, []);

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

    const handleFilterByStatus = (_status) => {
        $(tableRef.current).DataTable().column(5).search(_status).draw();
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Meetings</h1>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="0">Scheduled</option>
                                <option value="5">Status</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="row">
                            <div className="col-md-3">
                                <div className="form-group">
                                    <select
                                        className="form-control"
                                        onChange={(e) => {
                                            handleFilterByStatus(
                                                e.target.value
                                            );
                                        }}
                                    >
                                        <option value="">All</option>
                                        <option value="pending">Pending</option>
                                        <option value="confirmed">
                                            Confirmed
                                        </option>
                                        <option value="declined">
                                            Declined
                                        </option>
                                        <option value="completed">
                                            Completed
                                        </option>
                                        <option value="rescheduled">
                                            Rescheduled
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
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
