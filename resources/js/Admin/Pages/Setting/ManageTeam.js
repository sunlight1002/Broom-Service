import React, { useEffect, useRef } from "react";
import { Link, useNavigate } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";

export default function ManageTeam() {
    const navigate = useNavigate();
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
                url: "/api/admin/teams",
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
                    title: "ID",
                    data: "id",
                    visible: false,
                },
                {
                    title: "Name",
                    data: "name",
                },
                {
                    title: "Email",
                    data: "email",
                },
                {
                    title: "Phone",
                    data: "phone",
                },
                {
                    title: "Status",
                    data: "status",
                    render: function (data, type, row, meta) {
                        return row.status == 1 ? "Active" : "Inactive";
                    },
                },
                {
                    title: "Action",
                    data: "action",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let _html =
                            '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                        _html += `<button type="button" class="dropdown-item dt-availability-btn" data-id="${row.id}">Availability</button>`;

                        _html += `<button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">Edit</button>`;

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
            },
        });

        $(tableRef.current).on("click", ".dt-row", function (e) {
            if (
                !e.target.closest(".dropdown-toggle") &&
                !e.target.closest(".dropdown-menu") &&
                !e.target.closest(".dtr-control")
            ) {
                const _id = $(this).data("id");
                navigate(`/admin/teams/${_id}/edit`);
            }
        });

        $(tableRef.current).on("click", ".dt-availability-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/team-member/availability/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/teams/${_id}/edit`);
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
            confirmButtonText: "Yes, Delete Team member!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/teams/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Team member has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            $(tableRef.current).DataTable().draw();
                        }, 1000);
                    });
            }
        });
    };

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
                            <h1 className="page-title">Team</h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <Link
                                    to="/admin/teams/create"
                                    className="btn btn-pink addButton"
                                >
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    Add New
                                </Link>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="0">Name</option>
                                <option value="1">Email</option>
                                <option value="2">Phone</option>
                                <option value="3">Status</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="dashBox p-4">
                    <table
                        ref={tableRef}
                        className="display table table-bordered"
                    />
                </div>
            </div>
        </div>
    );
}
