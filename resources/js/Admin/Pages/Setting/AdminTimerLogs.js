import { useRef, useEffect, useState } from 'react';
import { useTranslation } from "react-i18next";
import { useParams } from 'react-router-dom';
import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import { getDataTableStateConfig, TABLE_IDS } from '../../../Utils/datatableStateManager';
import Sidebar from '../../Layouts/Sidebar';

const AdminTimerLogs = () => {
    const { t, i18n } = useTranslation();
    const params = useParams();
    const tableRef = useRef(null);
    const [adminName, setAdminName] = useState("");

    const getCurrentPageNumber = () => {
        const table = $(tableRef.current).DataTable();
        const pageInfo = table.page.info();
        return pageInfo.page + 1;
    };

    const initializeDataTable = (initialPage = 0) => {
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            const baseConfig = {
                processing: true,
                serverSide: true,
                ajax: {
                    url: `/api/admin/get-time-logs/${params.id}`,
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ${localStorage.getItem("admin-token")}`
                        );
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    { title: "ID", data: "id", visible: false },
                    // {
                    //     title: t("global.date"),
                    //     data: "created_at",
                    //     responsivePriority: 1,
                    //     render: data => data ? data : "-",
                    //     width: "10%",
                    // },
                    // {
                    //     title: t("admin.global.Name"),
                    //     data: "name",
                    //     render: data => data ?? "-",
                    // },
                    // {
                    //     title: t("admin.global.Email"),
                    //     data: "email",
                    //     render: data => data ?? "-",
                    // },
                    {
                        title: t("admin.start_time"),
                        data: "start_timer",
                        render: data => data ?? "-",
                    },
                    {
                        title: t("admin.end_time"),
                        data: "end_timer",
                        render: data => data ?? "-",
                    },
                    {
                        title: t("admin.start_location"),
                        data: "start_location",
                        render: data => data ?? "-",
                    },
                    {
                        title: t("admin.end_location"),
                        data: "end_location",
                        render: data => data ?? "-",
                    },
                    {
                        title: t("admin.time_taken"),
                        data: "difference_minutes",
                        render: data => data ? `${data} min` : "-",
                    },
                ],
                ordering: true,
                searching: true,
                responsive: true,
                createdRow: function (row, data) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
                columnDefs: [
                    {
                        targets: "_all",
                        createdCell: function (td) {
                            $(td).addClass("custom-cell-class");
                        },
                    },
                ],
                initComplete: function () {
                    const table = $(tableRef.current).DataTable();
                    const data = table.data().toArray();

                    if (data.length > 0 && data[0].name) {
                        setAdminName(data[0].name); // ✅ store in state
                    }

                    table.page(initialPage).draw("page");
                },
            };

            // Add state management configuration
            const stateConfig = getDataTableStateConfig(TABLE_IDS.ADMIN_TIMER_LOGS, {
                onStateLoad: (settings, data) => {
                    console.log('Admin timer logs table state loaded:', data);
                },
                onStateSave: (settings, data) => {
                    console.log('Admin timer logs table state saved:', data);
                }
            });

            const fullConfig = { ...baseConfig, ...stateConfig };

            $(tableRef.current).DataTable(fullConfig);
        } else {
            const table = $(tableRef.current).DataTable();
            table.page(initialPage).draw("page");
        }
    };

    useEffect(() => {
        const searchParams = new URLSearchParams(window.location.search);
        const pageFromUrl = parseInt(searchParams.get("page")) || 1;
        const initialPage = pageFromUrl - 1;

        initializeDataTable(initialPage);

        $("div.dt-search").append(`<i class="fa fa-search search-icon"></i>`);
        $("div.dt-search").addClass("position-relative");

        $(tableRef.current).on("page.dt", function () {
            const currentPageNumber = getCurrentPageNumber();
            const url = new URL(window.location);
            url.searchParams.set("page", currentPageNumber);
            window.history.replaceState({}, "", url);
        });

        i18n.on("languageChanged", () => {
            $(tableRef.current).DataTable().destroy();
            initializeDataTable(initialPage);
        });

        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true);
                $(tableRef.current).off("page.dt");
            }
        };
    }, []);

    useEffect(() => {
        const table = $(tableRef.current).DataTable();
        table.ajax.reload(null, false);
        table.columns.adjust().draw();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex justify-content-between">
                        <div className="">
                            <h1 className="page-title">
                                {t("admin.timer_logs")} - {adminName ? adminName : ""}
                            </h1>

                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            {/* <table
                                ref={tableRef}
                                className="table table-striped table-bordered table-hover"
                            ></table> */}
                            <div
                                className="dashBox pb-4 w-100"
                                style={{
                                    backgroundColor: "inherit",
                                    border: "none",
                                    overflowX: "auto",
                                }}
                            >
                                <table
                                    ref={tableRef}
                                    className="display table table-bordered w-100"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default AdminTimerLogs