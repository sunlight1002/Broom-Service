import React, { useEffect, useRef, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import { getDataTableStateConfig, TABLE_IDS } from '../../../Utils/datatableStateManager';

import Sidebar from "../../Layouts/WorkerSidebar";
import { getMobileStatusBadgeHtml } from '../../../Utils/common.utils';

export default function ManageSickLeaves() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const tableRef = useRef(null);
    const [loading, setLoading] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const initializeDataTable = () => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            const baseConfig = {
                processing: true,
                serverSide: true,
                autoWidth: false, // Prevent automatic column width adjustments
                ajax: {
                    url: "/api/sick-leaves",
                    type: "GET",
                    headers: headers,
                    data: function (d) {
                        d.length = d.length || 10;
                        d.start = d.start || 0;
                        d.column = d.order[0].column;
                        d.dir = d.order[0].dir;
                        d.search = d.search.value;
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
                        title: t("global.startDate"),
                        data: "start_date",
                        className: "text-left",
                    },
                    {
                        title: t("worker.endDate"),
                        data: "end_date",
                        className: "text-left",
                    },
                    {
                        title: t("worker.status"),
                        data: "status",
                        render: function (data, type, row, meta) {
                            const style = getStatusStyle(data);
                            const badge = getMobileStatusBadgeHtml(data);
                            return `<span style="color: ${style.color}; font-weight: ${style.fontWeight};">${data} ${badge}</span>`;
                        }

                    },
                    {
                        title: t("worker.reason"),
                        data: "rejection_comment",

                    },
                    {
                        title: t("worker.action"),
                        data: null,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            return `
                                <div class="action-dropdown dropdown">
                                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">${t("admin.leads.Edit")}</button>
                                        <button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>
                                    </div>
                                </div>`;
                        },
                    },
                ],
                responsive: true,
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
                drawCallback: function () {
                    initializeTableActions();
                },
            };

            // Add state management configuration
            const stateConfig = getDataTableStateConfig(TABLE_IDS.WORKER_SICK_LEAVES, {
                onStateLoad: (settings, data) => {
                    console.log('Worker sick leaves table state loaded:', data);
                },
                onStateSave: (settings, data) => {
                    console.log('Worker sick leaves table state saved:', data);
                }
            });

            const fullConfig = { ...baseConfig, ...stateConfig };

            $(tableRef.current).DataTable(fullConfig);

            $(tableRef.current).css('table-layout', 'fixed');
        }
    };

    const initializeTableActions = () => {
        // Handle Edit Button Click
        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const id = $(this).data("id");
            navigate(`/worker/sick-leaves/${id}/edit`);
        });

        // Handle Delete Button Click
        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const id = $(this).data("id");
            handleDelete(id);
        });
    };

    useEffect(() => {
        initializeDataTable();

        // Cleanup on unmount
        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true);
            }
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
            confirmButtonText: "Yes, Delete Sick Leave!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/sick-leaves/${id}`, { headers })
                    .then((response) => {
                        if (response.status === 204) {
                            Swal.fire("Deleted!", "Sick Leave has been deleted.", "success");
                            $(tableRef.current).DataTable().draw(); // Refresh the DataTable
                        } else {
                            Swal.fire("Error!", "Something went wrong.", "error");
                        }
                    })
                    .catch((error) => {
                        Swal.fire("Error!", "An unexpected error occurred.", "error");
                    });
            }
        });
    };
    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order([parseInt(colIdx), "asc"]).draw();
    };

    const getStatusStyle = (status) => {
        switch (status) {
            case 'approved':
                return {
                    color: 'green',
                    fontWeight: 'bold'
                };
            case 'pending':
                return { color: 'orange', fontWeight: 'bold' };
            case 'rejected':
                return { color: 'red', fontWeight: 'bold' };
            default:
                return {};
        }
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("worker.sidebar.leaves")}</h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <Link to="/worker/sick-leaves/create" className="btn navyblue no-hover addButton">
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    {t("global.addNew")}
                                </Link>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select className="form-control" onChange={(e) => sortTable(e.target.value)}>
                                <option value="">{t("admin.sickLeaves.Options.sortBy")}</option>
                                <option value="1">{t("admin.sickLeaves.workerName")}</option>
                                <option value="2">{t("admin.sickLeaves.startDate")}</option>
                                <option value="3">{t("admin.sickLeaves.endDate")}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="dashBox p-4" style={{ backgroundColor: "inherit", border: "none" }}>
                    <table ref={tableRef} className="display table table-bordered w-100" />
                </div>
            { loading && <FullPageLoader visible={loading}/>}
        </div>
        </div>

    );
}