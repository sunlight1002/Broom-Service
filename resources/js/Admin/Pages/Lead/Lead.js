import React, { useState, useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import i18next from "i18next";
import { Tooltip } from "react-tooltip";
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

export default function Lead() {
    const { t, i18n } = useTranslation();
    const statusArr = {
        pending: t("admin.leads.Pending"),
        // potential: t("admin.leads.Potential"),
        irrelevant: t("admin.leads.Irrelevant"),
        uninterested: t("admin.leads.Uninterested"),
        unanswered: t("admin.leads.Unanswered"),
        "reschedule call": t("admin.leads.Reschedule_call"),
        // "potential client": t("admin.leads.Potential_client"),
        // "pending client": t("admin.leads.Pending_client"),
        // "freeze client": t("admin.leads.Freeze_client"),
        // "active client": t("admin.leads.Active_client"),
    };
    const [filter, setFilter] = useState("All");
    const [changeStatusModal, setChangeStatusModal] = useState({
        isOpen: false,
        id: 0,
    });

    const tableRef = useRef(null);

    const navigate = useNavigate();
    const leadStatuses = [
        t("admin.leads.Pending"),
        t("admin.leads.Potential"),
        t("admin.leads.Irrelevant"),
        t("admin.leads.Uninterested"),
        t("admin.leads.Unanswered"),
        t("admin.leads.Potential_client"),
        t("admin.leads.Reschedule_call"),
    ];

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            const table = $(tableRef.current).DataTable({
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
                        title: t("global.date"),
                        data: "created_at",
                        responsivePriority: 1,
                        width: "10%",
                    },
                    {
                        title: t("admin.global.Name"),
                        data: "name",
                        responsivePriority: 2,
                        width: "15%",
                    },
                    { title: t("admin.global.Email"), data: "email" },
                    {
                        title: t("admin.global.Phone"),
                        data: "phone",
                        render: function (data) {
                            return `+${data}`;
                        },
                    },
                    {
                        title: t("admin.global.Status"),
                        data: "lead_status",
                        orderable: false,
                        render: function (data, type, row) {
                            const _statusColor = leadStatusColor(data);
                            let _html = ``;

                            // Add reschedule details with tooltip if the lead status is 'reschedule call'
                            if (row.lead_status === 'reschedule call' && row.reschedule_date && row.reschedule_time) {
                                const tooltipContent = `${row.reschedule_date} ${row.reschedule_time}<br>${row.reason}`;
                                _html += `<p 
                                    class="badge dt-change-status-btn" 
                                    data-tooltip-id="reschedule" 
                                    data-id="${row.id}" 
                                    data-tooltip-html="${tooltipContent}" 
                                    style="background-color: ${_statusColor.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 125px; text-align: center;">
                                    ${data}
                                </p>`;
                            } else if (row.reason) {
                                const reason = `${row.reason}`;
                                _html = `<p 
                                    class="badge dt-change-status-btn" 
                                    data-tooltip-id="reschedule" 
                                    data-tooltip-html="${reason}" 
                                    data-id="${row.id}" 
                                    style="background-color: ${_statusColor.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 125px; text-align: center;">
                                    ${data}
                                </p>`;
                            } else {
                                _html = `<p 
                                    class="badge dt-change-status-btn" 
                                    data-id="${row.id}" 
                                    style="background-color: ${_statusColor.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 125px; text-align: center;">
                                    ${data}
                                </p>`;
                            }
                            return _html;
                        },
                    },

                    {
                        title: t("admin.global.Action"),
                        data: "action",
                        orderable: false,
                        responsivePriority: 3,
                        render: function (data, type, row) {
                            return `
                                <div class="action-dropdown dropdown">
                                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">${t("admin.leads.Edit")}</button>
                                        <button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>
                                        <button type="button" class="dropdown-item dt-change-status-btn" data-id="${row.id}">${t("admin.leads.change_status")}</button>
                                        <button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>
                                    </div>
                                </div>`;
                        },
                    },
                ],
                pageLength: 10,
                responsive: true,
                autoWidth: true,
                createdRow: function (row, data) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
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
                navigate(`/admin/leads/view/${_id}`);
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

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/leads/${_id}/edit`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/leads/view/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-change-status-btn", function () {
            const _id = $(this).data("id");
            toggleChangeStatusModal(_id);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        i18n.on("languageChanged", () => {
            $(tableRef.current).DataTable().destroy(); // Destroy the table
            initializeDataTable(initialPage);
        });

        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true); // Ensure proper cleanup
                // $(tableRef.current).off("click");
                $(tableRef.current).off("page.dt");
            }
        };
    }, []);


    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order(parseInt(colIdx), "asc").draw();
    };

    const updateData = () => {
        setTimeout(() => {
            const table = $(tableRef.current).DataTable();
            table.draw();

            // Check if the current page has data
            const pageInfo = table.page.info();
            if (pageInfo.recordsDisplay === 0 && pageInfo.page > 0) {
                // Set the page to 1 if the current page is empty
                table.page(1).draw("page");

                // Update the URL to reflect the first page
                const url = new URL(window.location);
                url.searchParams.set("page", 1);
                window.history.replaceState({}, "", url);
            }
        }, 1000);
    };

    const toggleChangeStatusModal = (clientId = 0) => {
        setChangeStatusModal((prev) => {
            return {
                isOpen: !prev.isOpen,
                id: clientId,
            };
        });
    };

    useEffect(() => {
        const table = $(tableRef.current).DataTable();
        const url = new URL(window.location);
        url.searchParams.delete("page");
        window.history.replaceState({}, "", url);

        if (filter == "All") {
            table.column(4).search(null).draw();
        } else {
            // table.ajax.reload();
            table.column(4).search(filter).draw();
        }
    }, [filter]);

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
                        updateData();
                        // setTimeout(() => {
                        //     $(tableRef.current).DataTable().draw();
                        // }, 1000);
                    });
            }
        });
    };



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

                                <Link
                                    to="/admin/leads/create"
                                    className="btn navyblue no-hover add-btn"
                                >
                                    <i className="fas fa-plus-circle"></i>
                                    <span className="d-lg-block ml-2 d-none">
                                        {t("admin.leads.AddNew")}
                                    </span>
                                </Link>
                                <div className="action-dropdown dropdown mt-md-4 mx-2 d-lg-none">
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
                                    <div className="dropdown-menu">
                                        <button
                                            className="dropdown-item "
                                            onClick={(e) => {
                                                setFilter("All");
                                            }}
                                        >
                                            {t("admin.leads.All")}
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
                            text={t("admin.leads.All")}
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
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body px-0">
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
            <Tooltip id="reschedule" place="top" type="dark" effect="solid" style={{ zIndex: "99999" }} />
        </div>
    );
}
