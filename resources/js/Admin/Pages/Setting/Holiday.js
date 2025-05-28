import React, { useEffect, useRef,useState } from "react";
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

import Sidebar from "../../Layouts/Sidebar";

export default function ManageHolidays() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const tableRef = useRef(null);
    const [loading, setLoading] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const adminLng = localStorage.getItem("admin-lng");

    const initializeDataTable = () => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                autoWidth: true, // Prevent automatic column width adjustments
                ajax: {
                    url: "/api/admin/holidays",
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
                        title: t("admin.holidays.holidayName"),
                        data: "holiday_name",
                    },
                    {
                        title: t("global.startDate"),
                        data: "start_date",
                    },
                    {
                        title: t("admin.holidays.endDate"),
                        data: "end_date",
                    },
                    {
                        title: t("admin.holidays.halfDay"),
                        data: null,
                        render: function (data, type, row, meta) {
                            // Check the language preference
                            const isHebrew = adminLng === 'he'; // Assuming 'he' is for Hebrew language
                            const yesText = isHebrew ? "כן" : "Yes";
                            const noText = isHebrew ? "לא" : "No";
                            
                            // Return 'Yes' or 'No' based on the 'half_day' value
                            return row.half_day == 1 ? yesText : noText;
                        },
                    },
                    {
                        title: t("admin.holidays.firstHalf"),
                        data: null,
                        render: function (data, type, row, meta) {
                            const isHebrew = adminLng === 'he'; // Assuming 'he' is for Hebrew language
                            const yesText = isHebrew ? "כן" : "Yes";
                            const noText = isHebrew ? "לא" : "No";

                            return row.first_half == 1 ? yesText : noText;
                        },
                    },
                    {
                        title: t("admin.holidays.secondHalf"),
                        data: null,
                        render: function (data, type, row, meta) {
                            const isHebrew = adminLng === 'he'; // Assuming 'he' is for Hebrew language
                            const yesText = isHebrew ? "כן" : "Yes";
                            const noText = isHebrew ? "לא" : "No";

                            return row.second_half == 1 ? yesText : noText;
                        },
                    },
                    {
                        title: t("admin.global.Action"),
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
                width: "100%",
                scrollX: true,
                createdRow: function (row, data) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
                drawCallback: function () {
                    // Reinitialize actions after the table is redrawn
                    initializeTableActions();
                },
            });

            $(tableRef.current).css('table-layout', 'fixed');
        }
    };

    const initializeTableActions = () => {
        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const id = $(this).data("id");
            navigate(`/admin/holidays/${id}/edit`);
        });
        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const id = $(this).data("id");
            handleDelete(id);
        });
    };

    useEffect(() => {
        initializeDataTable();

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
            confirmButtonText: "Yes, Delete Holiday!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/holidays/${id}`, { headers })
                    .then(() => {
                        Swal.fire("Deleted!", "Holiday has been deleted.", "success");
                        $(tableRef.current).DataTable().draw();
                    });
            }
        });
    };

    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order([parseInt(colIdx), "asc"]).draw();
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("admin.sidebar.settings.holidays")}</h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <Link to="/admin/holidays/create" className="btn navyblue no-hover addButton">
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    {t("global.addNew")}
                                </Link>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select className="form-control" onChange={(e) => sortTable(e.target.value)}>
                                <option value="">{t("admin.leads.Options.sortBy")}</option>
                                <option value="1">{t("admin.holidays.holidayName")}</option>
                                <option value="2">{t("admin.holidays.startDate")}</option>
                                <option value="3">{t("admin.holidays.endDate")}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="dashBox p-0 p-md-4" style={{ backgroundColor: "inherit", border: "none" }}>
                    <table ref={tableRef} className="display table table-bordered w-100" />
                </div>
                </div>
            { loading && <FullPageLoader visible={loading}/>}
        </div>

    );
}
