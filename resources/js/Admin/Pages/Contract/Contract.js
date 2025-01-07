import React, { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import i18next from "i18next";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import FilterButtons from "../../../Components/common/FilterButton";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function Contract() {
    const { t, i18n } = useTranslation();
    const navigate = useNavigate();
    const tableRef = useRef(null);
    const statusRef = useRef(null);
    const [loading, setLoading] = useState(false)

    const [filter, setFilter] = useState("All");

    // const contractStatuses = [
    //     t("global.verified"),
    //     t("global.unverified"),
    //     t("global.notSigned"),
    //     t("admin.schedule.options.meetingStatus.Declined"),
    // ];

    const contractStatuses = [
        "verified",
        "un-verified",
        "not-signed",
        "declined",
    ];

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const adminLng = localStorage.getItem("admin-lng");

    useEffect(() => {
        i18next.changeLanguage(adminLng);

        if (adminLng == "heb") {
            import("../../../Assets/css/rtl.css");
            document.querySelector("html").setAttribute("dir", "rtl");
        } else {
            document.querySelector("html").removeAttribute("dir");
        }
    }, [])



    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
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
                    data: function (d) {
                        d.status = statusRef.current.value;
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: t("global.date"),
                        data: "created_at",
                        visible: false,
                    },
                    {
                        title: t("client.dashboard.client"),
                        data: "client_name",
                        render: function (data, type, row, meta) {
                            return `<a href="/admin/clients/view/${row.client_id}" target="_blank" class="dt-client-name"> ${data} </a>`;
                        },
                    },
                    {
                        title: t("admin.global.Email"),
                        data: "email",
                    },
                    {
                        title: t("admin.global.Phone"),
                        data: "phone",
                        render: function (data) {
                            return `+${data}`;
                        }
                    },
                    {
                        title: t("global.service"),
                        data: "services",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            if (data == null) {
                                return "-";
                            }
                            return data
                                .map((s, j) => {
                                    // Determine the service name based on conditions
                                    const serviceName = s.template === "airbnb"
                                        ? s.sub_services?.sub_service_name || "NA"
                                        : s.template === "others"
                                            ? s.other_title
                                            : s.name;

                                    // Add separator for all but the last item
                                    return data.length - 1 !== j ? serviceName + " | " : serviceName;
                                })
                                .join("");
                        },
                    },

                    {
                        title: t("admin.global.Status"),
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

                            // return `<span style="color: ${color};">${data}</span>`;

                            return `<p style="background-color: #efefef; color: ${color}; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        ${data}
                                    </p>`;
                        },
                    },
                    {
                        title: t("client.dashboard.total"),
                        data: "subtotal",
                        render: function (data, type, row, meta) {
                            return data ? `${data} ILS + VAT` : "NA";
                        },
                    },
                    {
                        title: t("client.jobs.view.job_status"),
                        data: "job_status",
                        render: function (data, type, row, meta) {
                            // return data ? "Inactive" : "Active";
                            return data ? `<p style="background-color: #efefef; color: red; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        Inactive
                                    </p>` : `<p style="background-color: #efefef; color: green; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        Active
                                    </p>`;
                        },
                    },
                    {
                        title: t("admin.global.Action"),
                        data: "action",
                        orderable: false,
                        responsivePriority: 1,
                        render: function (data, type, row, meta) {
                            let _html =
                                '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';
                            // console.log(row);

                            // Check conditions for "Create Job" button
                            const services = Array.isArray(row.services) ? row.services : [];
                            const allOneTime = services.every((service) => service.is_one_time === true);
                            const hasMultipleServices = services.length > 1;

                            if (row.status === "verified" && (!allOneTime || hasMultipleServices)) {
                                _html += `<button type="button" class="dropdown-item dt-create-job-btn" data-id="${row.id}">${t("admin.client.createJob")}</button>`;
                            }

                            if (row.job_status == 1 && row.status == "verified") {
                                _html += `<button type="button" class="dropdown-item dt-cancel-job-btn" data-id="${row.id}">${t("admin.global.cancelJob")}</button>`;
                            }

                            if (row.job_status == 0 && row.status == "verified") {
                                _html += `<button type="button" class="dropdown-item dt-resume-job-btn" data-id="${row.id}">${t("admin.global.resumejob")}</button>`;
                            }

                            // _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">View</button>`;

                            _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("global.delete")}</button>`;

                            _html += "</div> </div>";

                            return _html;
                        },
                    },
                ],
                ordering: true,
                searching: true,
                responsive: true,
                autoWidth: true,
                width: "100%",
                scrollX: true,
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
                ],
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

        // Customize the search input
        const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
        $("div.dt-search").append(searchInputWrapper);
        $("div.dt-search").addClass("position-relative");

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
                const url = `/admin/view-contract/${_id}`;
                window.open(url, '_blank');
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

        // Handle language changes
        i18n.on("languageChanged", () => {
            $(tableRef.current).DataTable().destroy(); // Destroy the table
            initializeDataTable(initialPage);
        });

        // Cleanup event listeners and destroy DataTable when unmounting
        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true); // Ensure proper cleanup
                $(tableRef.current).off("click");
                $(tableRef.current).off("page.dt");
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
            confirmButtonText: "Yes, Delete Contract!",
        }).then((result) => {
            setLoading(true)
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/contract/${id}`, { headers })
                    .then((response) => {
                        setLoading(false)
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
            setLoading(false)
            if (result.isConfirmed) {
                axios
                    .post(
                        `/api/admin/cancel-contract-jobs`,
                        { id, job },
                        { headers }
                    )
                    .then((response) => {
                        setLoading(false)
                        Swal.fire(response.data.msg, "", "success");
                        setTimeout(() => {
                            $(tableRef.current).DataTable().draw();
                        }, 1000);
                    });
            }
        });
    };

    useEffect(() => {
        $(tableRef.current).DataTable().draw();
    }, [filter]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("client.sidebar.contracts")}</h1>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">{t("admin.leads.Options.sortBy")}</option>
                                <option value="5">{t("admin.leads.Options.Status")}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="d-none d-lg-block">
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
                                text="All"
                                className="px-3 mr-1 ml-4"
                                selectedFilter={filter}
                                setselectedFilter={setFilter}
                            />
                            {contractStatuses.map((_status, _index) => {
                                return (
                                    <FilterButtons
                                        text={_status}
                                        className="mr-1 px-3 ml-2"
                                        key={_index}
                                        selectedFilter={filter}
                                        setselectedFilter={setFilter}
                                    />
                                );
                            })}

                            <input
                                type="hidden"
                                value={filter}
                                ref={statusRef}
                            />
                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
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
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
