import React, { useEffect, useRef } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";

export default function Services() {
    const { t } = useTranslation();
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
                url: "/api/admin/services",
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
                    title: "Service - En",
                    data: "name",
                },
                {
                    title: "Service - Heb",
                    data: "heb_name",
                },
                {
                    title: "Status",
                    data: "status",
                    render: function (data, type, row, meta) {
                        return data == 0 ? "Inactive" : "Active";
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

                        _html += `<button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">Edit</button>`;

                        _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">View</button>`;

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
                    (!tableRef.current.classList.contains("collapsed") ||
                        !e.target.closest(".dtr-control"))
                ) {
                    _id = $(this).data("id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                navigate(`/admin/services/${_id}`);
            }
        });

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/services/${_id}/edit`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/services/${_id}`);
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
            confirmButtonText: "Yes, Delete Service!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/services/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Service has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            $(tableRef.current).DataTable().draw();
                        }, 1000);
                    });
            }
        });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("client.common.services")}</h1>
                        </div>
                        <div className="col-sm-6">
                            <Link
                                to="/admin/services/create"
                                className="ml-2 btn btn-success addButton"
                            >
                                {t("global.addService")}
                            </Link>
                            <Link
                                to="/admin/service-schedules"
                                className="ml-2 btn btn-warning addButton"
                            >
                                {t("global.schedule")}
                            </Link>
                            <Link
                                to="/admin/templates"
                                className="btn btn-pink addButton"
                            >
                                {t("admin.global.template")}
                            </Link>
                        </div>
                    </div>
                </div>
                <div className="card" style={{boxShadow: "none"}}>
                    <div className="card-body">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table table-bordered"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
