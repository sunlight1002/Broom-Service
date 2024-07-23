import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import { useParams, useNavigate, Link } from "react-router-dom";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

export default function OfferedPrice() {
    const params = useParams();
    const navigate = useNavigate();
    const { t } = useTranslation();
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
                url: `/api/admin/clients/${params.id}/offers`,
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
                    title: "Status",
                    data: "status",
                    render: function (data, type, row, meta) {
                        let color = "";
                        if (data == "sent") {
                            color = "purple";
                        } else if (data == "accepted") {
                            color = "green";
                        } else {
                            color = "red";
                        }

                        return `<span style="color: ${color};">${data}</span>`;
                    },
                },
                {
                    title: "Total",
                    data: "subtotal",
                    render: function (data, type, row, meta) {
                        return `${data} ILS + VAT`;
                    },
                },
                {
                    title: "Action",
                    data: "action",
                    orderable: false,
                    responsivePriority: 1,
                    render: function (data, type, row, meta) {
                        let _html = '<div class="d-flex">';

                        _html += `<button type="button" class="btn dt-edit-btn" data-id="${row.id}"
                        style="font-size: 15px; color: #2F4054; padding: 5px 8px; background: #E5EBF1; border-radius: 5px;"
                        ><i class="fa fa-edit"></i></button>`;

                        _html += `<button type="button" class="ml-2 btn dt-view-btn" data-id="${row.id}"
                        style="font-size: 15px; color: #2F4054; padding: 5px 8px; background: #E5EBF1; border-radius: 5px;"
                        ><i class="fa fa-eye"></i></button>`;

                        _html += `<button type="button" class="ml-2 btn dt-delete-btn" data-id="${row.id}"
                        style="font-size: 15px; color: #2F4054; padding: 5px 8px; background: #E5EBF1; border-radius: 5px;"
                        ><i class="fa fa-trash"></i></button>`;

                        _html += "</div>";

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
                        $(td).addClass('custom-cell-class');
                    }
                }
            ]
        });

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
                navigate(`/admin/view-offer/${_id}`);
            }
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/view-offer/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/offered-price/edit/${_id}`);
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
            confirmButtonText: "Yes, Delete Offer!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/offers/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Offer has been deleted.",
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
        <div className="boxPanel">
            <table ref={tableRef} className="display table table-bordered w-100" />
        </div>
    );
}
