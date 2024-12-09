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

export default function WorkersRefund() {
    const { t } = useTranslation();
    const tableRef = useRef(null);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState("All");

    const headers = {
        Accept: "application/json",
        Authorization: `Bearer ${localStorage.getItem("admin-token")}`,
    };
    const refundStatuses = [
        "pending",
        "approved",
        "rejected",
    ];

    const initializeDataTable = (initialPage = 0) => {
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                stateSave: true,
                ajax: {
                    url: "/api/admin/refund-claims/list",
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
                        if (!json.data) {
                            console.error('Invalid data format', json);
                            return [];
                        }
                        return json.data;
                    }
                },
                order: [[0, "desc"]],
                columns: [
                    { title: "Name", data: "worker_name" },
                    {
                        title: "Date",
                        data: "date",
                        className: "text-left",
                    },
                    {
                        title: "Amount", data: "amount",
                        render: function (data) {
                            return formatCurrency(data);
                        },
                    },
                    {
                        title: "Bill",
                        data: "bill_file",
                        render: function (data) {
                            return data ? `
                                <a href="${data}" target="_blank" style="margin-right: 15px;"><i class="fas fa-eye" style="font-size: 18px;"></i></a>
                                <a href="${data}" download><i class="fas fa-download" style="font-size: 18px;"></i></a>
                            ` : "Not available";
                        }
                    },

                    {
                        title: "Status",
                        data: "status",
                        render: function (data) {
                            const style = leadStatusColor(data);
                            return `<p style="background-color: ${style.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                            ${data}
                        </p>`;
                        },
                    },
                    {
                        title: "Action",
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

        // Event listener for pagination
        $(tableRef.current).on("page.dt", function () {
            const currentPageNumber = getCurrentPageNumber();

            // Update the URL with the page number
            const url = new URL(window.location);
            url.searchParams.set("page", currentPageNumber);

            // Use replaceState to avoid adding new history entry
            window.history.replaceState({}, "", url);
        });

        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy();
                $(tableRef.current).off("click");
                $(tableRef.current).off("page.dt");
            }
        };
    }, [filter, location.search]);

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

                axios.post(`/api/admin/refund-claims/${id}/approve`, data, { headers })
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


    const formatCurrency = (amount) => {
        if (amount === null || amount === undefined) {
            return '-';
        }
        return `₪${parseFloat(amount).toFixed(2)}`;
    };

    useEffect(() => {
        if (filter == "All") {
            $(tableRef.current).DataTable().column(5).search(null).draw();
        } else {
            $(tableRef.current).DataTable().column(5).search(filter).draw();
        }
    }, [filter]);


    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-12">
                            <h1 className="page-title">{t("worker.refund_request")}</h1>
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
                            {refundStatuses.map((status, index) => {
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
