import React, { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import ContractCommentModal from "../../Components/Modals/ContractCommentModal";

export default function Contract() {
    const [selectedContract, setSelectedContract] = useState(null);
    const [isOpenCommentModal, setIsOpenCommentModal] = useState(false);

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
                url: "/api/admin/contract",
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
                    title: "Client",
                    data: "client_name",
                    render: function (data, type, row, meta) {
                        return `<a href="/admin/view-client/${row.client_id}" target="_blank" class="dt-client-name"> ${data} </a>`;
                    },
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
                    title: "Service",
                    data: "services",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        if (data == null) {
                            return "-";
                        }

                        return data.map((s, j) => {
                            return data.length - 1 != j
                                ? s.service == "10"
                                    ? s.other_title + " | "
                                    : s.name + " | "
                                : s.name;
                        });
                    },
                },
                {
                    title: "Status",
                    data: "status",
                    render: function (data, type, row, meta) {
                        let color = "";
                        if (data == "un-verified" || data == "not-signed") {
                            color = "purple";
                        } else if (data == "verified") {
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
                        return data ? `${data} ILS + VAT` : "NA";
                    },
                },
                {
                    title: "Job Status",
                    data: "job_status",
                    render: function (data, type, row, meta) {
                        return data ? "Inactive" : "Active";
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

                        if (row.status == "verified") {
                            _html += `<button type="button" class="dropdown-item dt-create-job-btn" data-id="${row.id}">Create Job</button>`;
                        }

                        if (row.job_status == 1 && row.status == "verified") {
                            _html += `<button type="button" class="dropdown-item dt-cancel-job-btn" data-id="${row.id}">Cancel Job</button>`;
                        }

                        if (row.job_status == 0 && row.status == "verified") {
                            _html += `<button type="button" class="dropdown-item dt-resume-job-btn" data-id="${row.id}">Resume Job</button>`;
                        }

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
                    !e.target.closest(".dt-client-name") &&
                    (!tableRef.current.classList.contains("collapsed") ||
                        !e.target.closest(".dtr-control"))
                ) {
                    _id = $(this).data("id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-client-name")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                navigate(`/admin/view-contract/${_id}`);
            }
        });

        $(tableRef.current).on("click", ".dt-create-job-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/create-job/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-cancel-job-btn", function () {
            const _id = $(this).data("id");
            cancelJob(_id, "disable");
        });

        $(tableRef.current).on("click", ".dt-resume-job-btn", function () {
            const _id = $(this).data("id");
            cancelJob(_id, "enable");
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/view-contract/${_id}`);
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
            confirmButtonText: "Yes, Delete Contract!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/contract/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Contract has been deleted.",
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

    const cancelJob = (id, job) => {
        let stext = job == "disable" ? "Yes, Cancel Jobs" : "Yes, Resume Jobs";
        Swal.fire({
            title: "Are you sure ?",
            text: "",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: "Cancel",
            confirmButtonText: stext,
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .post(
                        `/api/admin/cancel-contract-jobs`,
                        { id, job },
                        { headers }
                    )
                    .then((response) => {
                        Swal.fire(response.data.msg, "", "success");
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
                            <h1 className="page-title">Contracts</h1>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="4">Status</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="card">
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

            {isOpenCommentModal && (
                <ContractCommentModal
                    isOpen={isOpenCommentModal}
                    setIsOpen={() => {
                        setIsOpenCommentModal(false);
                    }}
                    contract={selectedContract}
                    onSuccess={() => {
                        $(tableRef.current).DataTable().draw();
                    }}
                />
            )}
        </div>
    );
}
