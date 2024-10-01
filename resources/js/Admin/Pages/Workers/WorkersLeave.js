import React, { useEffect, useRef, useState } from "react";
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
import FilterButtons from "../../../Components/common/FilterButton";
import { leadStatusColor } from "../../../Utils/client.utils";

export default function WorkerLeave() {
    const { t } = useTranslation();
    const tableRef = useRef(null);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState("All");

    const headers = {
        Accept: "application/json",
        Authorization: `Bearer ${localStorage.getItem("admin-token")}`,
    };
    const leaveStatuses = [
       "pending",
       "approved",
       "rejected",
    ];

    const initializeDataTable = () => {
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                stateSave: true,
                ajax: {
                    url: "/api/admin/sick-leaves/list",
                    type: "GET",
                    headers: headers,
                    data: function (d) {
                        return {
                            ...d,
                            status: filter,
                            search: d.search.value,
                        };
                    },
                    dataSrc: function (json) {
                        // Debugging: Check the structure of the response
                        console.log('DataTable Response:', json);
                        if (!json.data) {
                            console.error('Invalid data format', json);
                            return [];
                        }
                        return json.data;
                    }
                },
                order: [[0, "desc"]],
                columns: [
                    { title: t("worker.workerName"), data: "worker_name" },
                    { title: t("global.startDate"), data: "start_date" },
                    { title: t("worker.endDate"), data: "end_date" },
                    {
                        title: t("worker.doctorReport"),
                        data: "doctor_report_path",
                        render: function (data) {
                            return data ? `
                                <a href="${data}" target="_blank" style="margin-right: 15px;"><i class="fas fa-eye" style="font-size: 18px;"></i></a>
                                <a href="${data}" download><i class="fas fa-download" style="font-size: 18px;"></i></a>
                            ` : "Not available";
                        }
                    },
                    { title: t("worker.leaveReason"), data: "reason_for_leave" },
                    {
                        title: t("worker.status"),
                        data: "status",
                        render: function (data) {
                            const style =  leadStatusColor(data);
                            return `<p style="background-color: ${style.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                            ${data}
                        </p>`;
                        },
                        
                    },
                    {
                        title: t("worker.action"),
                        data: null,
                        orderable: false,
                        render: function (data, type, row) {
                            return `
                                <div class="action-dropdown dropdown">
                                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton-${row.id}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton-${row.id}">
                                        <button type="button" class="dropdown-item dt-approve-btn" data-id="${row.id}">${t("worker.approve")}</button>
                                        <button type="button" class="dropdown-item dt-reject-btn" data-id="${row.id}">${t("worker.reject")}</button>
                                    </div>
                                </div>`;
                        }
                    }
                ],
                ordering: true,
                searching: true,
                responsive: true,
                drawCallback: function () {
                    initializeTableActions();
                    setLoading(false); // Hide loader when data is loaded
                },
            });
        }
    };

    useEffect(() => {
        initializeDataTable();

        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy();
                $(tableRef.current).off("click");
            }
        };
    }, [filter]);

    const initializeTableActions = () => {
        $(tableRef.current).on("click", ".dt-approve-btn", function () {
            const id = $(this).data("id");
            handleAction(id, "approved");
        });

        $(tableRef.current).on("click", ".dt-reject-btn", function () {
            const id = $(this).data("id");
            handleAction(id, "rejected");
        });
    };

    const handleAction = (id, status) => {
        Swal.fire({
            title: status === 'rejected' ? t("admin.leaves.confirmation") : t("admin.leaves.approveConfirm"),
            input: status === 'rejected' ? 'textarea' : undefined,
            inputPlaceholder: status === 'rejected' ? t("admin.leaves.rejectionReason") : undefined,
            showCancelButton: true,
            confirmButtonText: t("admin.leaves.confirm"),
            cancelButtonText: t("admin.leaves.cancel"),
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
        }).then((result) => {
            if (result.isConfirmed) {
                const data = {
                    status: status,
                    rejection_comment: status === 'rejected' ? result.value : null,
                };

                axios.post(`/api/admin/sick-leaves/${id}/approve`, data, { headers })
                    .then((response) => {
                        Swal.fire("Status updated", "", "success");
                        $(tableRef.current).DataTable().draw(); // Refresh DataTable
                    })
                    .catch(() => Swal.fire("Can't update status", "", "error"));
            }
        });
    };
    const handleSearch = (e) => {
        $(tableRef.current).DataTable().search(e.target.value).draw();
    };


    useEffect(() => {
        if (filter == "All") {
            $(tableRef.current).DataTable().column(5).search(null).draw();
        } else {
            $(tableRef.current).DataTable().column(5).search(filter).draw();
        }
    }, [filter]);
   
    
   console.log(filter);
    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-12">
                            <h1 className="page-title">{t("worker.leaveRequest")}</h1>
                        </div>
                    </div>
                </div>
                <div className="dashBox p-4" style={{ backgroundColor: "inherit", border: "none" }}>
                    <div className="row">
                        <div
                            style={{
                                fontWeight: "bold",
                                marginTop: 10,
                                marginLeft: 15,
                            }}
                        >
                            {t("global.filter")}
                        </div>
                        <div>
                            <FilterButtons
                                text={t("admin.global.All")}
                                className="px-3 mr-1 ml-4"
                                selectedFilter={filter}
                                setselectedFilter={setFilter}
                            />
                            {leaveStatuses.map((status, index) => {
                                return (
                                    <FilterButtons
                                        text={status}
                                        className="mr-1 px-3 ml-2"
                                        key={index}
                                        selectedFilter={filter}
                                        setselectedFilter={setFilter}
                                    />
                                );
                            })}
                        </div>
                    </div>
                    <table ref={tableRef} className="display table table-bordered w-100" />
                </div>
                {loading && <FullPageLoader visible={loading} />}
            </div>
        </div>
    );
}
