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

import Sidebar from "../../Layouts/WorkerSidebar";
import { leadStatusColor } from "../../../Utils/client.utils";

export default function ManageRefundClaim() {
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
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false, // Prevent automatic column width adjustments
                ajax: {
                    url: "/api/refund-claims",
                    type: "GET",
                    headers: headers,
                    data: function (d) {
                        return {
                            draw: d.draw,
                            length: d.length,
                            start: d.start,
                            column: d.order[0].column,
                            dir: d.order[0].dir,
                            search: d.search.value,
                        };
                    },
                    dataSrc: 'data',
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: "ID",
                        data: "id",
                        visible: false,
                    },
    
                    {
                        title: "Date",
                        data: "date",
                    },
                    {
                        title: "Amount",
                        data: "amount",
                        render: function (data) {
                            return formatCurrency(data);
                        },
                    },
                    {
                        title: t("worker.status"),
                        data: "status",
                        render: function (data, type, row, meta) {
                            const style = leadStatusColor(data);
                            return `<p style="background-color: ${style.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                            ${data}
                        </p>`;
                        },
                       
                    },
                    {
                        title: "Reject Reason",
                        data: "rejection_comment",
                       
                    },
                    {
                        title: t("worker.action"),
                        data: null,
                        orderable: false,
                        render: function (data, type, row, meta) {
                            if (row.status === 'approved') {
                                return ''; // Return an empty string to hide the action buttons
                            }
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
            });

            $(tableRef.current).css('table-layout', 'fixed');
        }
    };
    
    const initializeTableActions = () => {
        // Handle Edit Button Click
        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const id = $(this).data("id");
            navigate(`/worker/refund-claim/${id}/edit`);
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
            confirmButtonText: "Yes, Delete Request!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/refund-claims/${id}`, { headers })
                    .then((response) => {
                        if (response.status === 204) {
                            Swal.fire("Deleted!", "Request has been deleted.", "success");
                            $(tableRef.current).DataTable().draw(); // Refresh the DataTable
                        } else {
                            Swal.fire("Error!", "Something went wrong.", "error");
                        }
                    })
                    .catch((error) => {
                        if (error.response && error.response.status === 403) {
                            Swal.fire("Error!", error.response.data.error, "error");
                        } else {
                            Swal.fire("Error!", "An unexpected error occurred.", "error");
                        }
                    });
            }
        });
    };
    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order([parseInt(colIdx), "asc"]).draw();
    };

    const formatCurrency = (amount) => {
        if (amount === null || amount === undefined) {
            return '-';
        }
        return `₪${parseFloat(amount).toFixed(2)}`;
    };
    
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Refund Claim</h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <Link to="/worker/refund-claim/create" className="btn navyblue no-hover addButton">
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    {t("global.addNew")}
                                </Link>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select className="form-control" onChange={(e) => sortTable(e.target.value)}>
                                <option value="">{t("admin.refundClaim.Options.sortBy")}</option>
                                <option value="1">{t("admin.refundClaim.workerName")}</option>
                                <option value="2">{t("admin.refundClaim.startDate")}</option>
                                <option value="3">{t("admin.refundClaim.endDate")}</option>
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
