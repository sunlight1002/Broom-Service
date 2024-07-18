import React, { useState, useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import ChangeStatusModal from "../../Components/Modals/ChangeStatusModal";
import { leadStatusColor } from "../../../Utils/client.utils";
import FilterButtons from "../../../Components/common/FilterButton";

const statusArr = {
    pending: "Pending",
    potential: "Potential",
    irrelevant: "Irrelevant",
    uninterested: "Uninterested",
    unanswered: "Unanswered",
    "potential client": "Potential client",
    "pending client": "Pending client",
    "freeze client": "Freeze client",
    "active client": "Active client",
};

export default function Lead() {
    const [filter, setFilter] = useState("All");
    const [changeStatusModal, setChangeStatusModal] = useState({
        isOpen: false,
        id: 0,
    });

    const tableRef = useRef(null);

    const navigate = useNavigate();
    const { t } = useTranslation();
    const leadStatuses = [
        "pending",
        "potential",
        "irrelevant",
        "uninterested",
        "unanswered",
        "potential client",
    ];

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
                url: "/api/admin/leads",
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
                    title: "Date",
                    data: "created_at",
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
                    data: "lead_status",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        const _statusColor = leadStatusColor(data);

                        // return `<span class="badge dt-change-status-btn" data-id="${row.id}" style="background-color: ${_statusColor.backgroundColor}; color: #fff;" > ${data} </span>`;
                        return `<p class="badge dt-change-status-btn" data-id="${row.id}" style="background-color: ${_statusColor.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                        ${data}
                    </p>`;
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

                        _html += `<button type="button" class="dropdown-item dt-change-status-btn" data-id="${row.id}">Change status</button>`;

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
                        !e.target.closest(".dtr-control")) &&
                    !e.target.closest(".dt-change-status-btn")
                ) {
                    _id = $(this).data("id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-change-status-btn")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                navigate(`/admin/view-lead/${_id}`);
            }
        });

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/leads/${_id}/edit`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/view-lead/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-change-status-btn", function () {
            const _id = $(this).data("id");
            toggleChangeStatusModal(_id);
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
            confirmButtonText: "Yes, Delete Lead!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/leads/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Lead has been deleted.",
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

    const toggleChangeStatusModal = (clientId = 0) => {
        setChangeStatusModal((prev) => {
            return {
                isOpen: !prev.isOpen,
                id: clientId,
            };
        });
    };

    const updateData = () => {
        setTimeout(() => {
            $(tableRef.current).DataTable().draw();
        }, 1000);
    };

    useEffect(() => {
        if (filter == "All") {
            $(tableRef.current).DataTable().column(4).search(null).draw();
        } else {
            $(tableRef.current).DataTable().column(4).search(filter).draw();
        }
    }, [filter]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row align-items-center">
                        <div className="col-sm-6">
                            <h1 className="page-title">
                                {t("admin.sidebar.leads")}
                            </h1>
                        </div>

                        <div className="col-sm-6">
                            <div className="search-data">
                                <div className="action-dropdown dropdown mt-md-4 mr-2 d-lg-none">
                                    <button
                                        type="button"
                                        className="btn btn-default navyblue dropdown-toggle"
                                        data-toggle="dropdown"
                                    >
                                        <i className="fa fa-filter"></i>
                                    </button>
                                    <div className="dropdown-menu">
                                        <button
                                            className="dropdown-item "
                                            onClick={(e) => {
                                                setFilter("All");
                                            }}
                                        >
                                            All
                                        </button>
                                        {leadStatuses.map((_status, _index) => {
                                            return (
                                                <button
                                                    className="dropdown-item"
                                                    onClick={(e) => {
                                                        setFilter(_status);
                                                    }}
                                                    key={_index}
                                                >
                                                    {_status}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>

                                <Link
                                    to="/admin/leads/create"
                                    className="btn navyblue add-btn"
                                >
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    <span className="d-lg-block d-none">
                                        {t("admin.leads.AddNew")}
                                    </span>
                                </Link>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">
                                    {t("admin.leads.Options.sortBy")}
                                </option>
                                <option value="0">
                                    {t("admin.leads.Options.ID")}
                                </option>
                                <option value="1">
                                    {t("admin.leads.Options.Name")}
                                </option>
                                <option value="2">
                                    {t("admin.leads.Options.Email")}
                                </option>
                                <option value="3">
                                    {t("admin.leads.Options.Phone")}
                                </option>
                                <option value="4">
                                    {t("admin.leads.Options.Status")}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="row mb-2 d-none d-lg-block">
                    <div className="col-sm-12">
                        <FilterButtons
                            text="All"
                            className="px-3 mr-1"
                            selectedFilter={filter}
                            setselectedFilter={setFilter}
                        />
                        {leadStatuses.map((_status, _index) => {
                            return (
                                <FilterButtons
                                    text={_status}
                                    className="px-3 mr-1"
                                    key={_index}
                                    selectedFilter={filter}
                                    setselectedFilter={setFilter}
                                />
                            );
                        })}
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
            {changeStatusModal.isOpen && (
                <ChangeStatusModal
                    handleChangeStatusModalClose={toggleChangeStatusModal}
                    isOpen={changeStatusModal.isOpen}
                    clientId={changeStatusModal.id}
                    getUpdatedData={updateData}
                    statusArr={statusArr}
                />
            )}
        </div>
    );
}
