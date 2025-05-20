import React, { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
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
    const [statusModal, setStatusModal] = useState(false)
    const [selectedContractId, setSelectedContractId] = useState(null)
    const [filter, setFilter] = useState("All");
    const [status, setStatus] = useState("");
    const alert = useAlert();
    const [dateRange, setDateRange] = useState({
        start_date: "",
        end_date: "",
    });

    const startDateRef = useRef(null);
    const endDateRef = useRef(null);

    const contractStatuses = {
        "verified": t("global.verified"),
        "un-verified": t("global.unverified"),
        "not-signed": t("global.notSigned"),
        "declined": t("admin.schedule.options.meetingStatus.Declined"),
    };

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

    const handleModal = (id, status) => {
        setStatus(status);
        setSelectedContractId(id);
        setStatusModal(true);
    };


    const handleUpdateStatus = async () => {
        try {
            const res = await axios.put(`/api/admin/contract-change-status/${selectedContractId}`, { status }, { headers });
            if (res.status === 200) {
                setStatusModal(false);
                alert.success(res?.data?.message);
                $(tableRef.current).DataTable().draw();
            }
        } catch (error) {
            console.log(error);
        }
    }

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
                        d.start_date = startDateRef.current.value;
                        d.end_date = endDateRef.current.value;
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: t("global.date"),
                        data: "created_at",
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

                            return `<p class="dt-job-status" data-id="${row.id}" data-status="${data}" style="background-color: #efefef; color: ${color}; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
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
                        width: "10%",
                        render: function (data, type, row, meta) {
                            let _html =
                                '<div class="action-dropdown dropdown contract-dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';
                            // console.log(row);

                            // Check conditions for "Create Job" button
                            const services = Array.isArray(row.services) ? row.services : [];
                            const allOneTime = services.every((service) => service.is_one_time === true);
                            const hasMultipleServices = services.length > 1;

                            _html += `<button type="button" class="dropdown-item dt-client-contract-btn" data-id="${row.unique_hash}">View Client Contract</button>`;

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
                    !e.target.closest(".dt-job-status") &&
                    (!tableRef.current.classList.contains("collapsed") ||
                        !e.target.closest(".dtr-control"))
                ) {
                    _id = $(this).data("id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-client-name") &&
                    !e.target.closest(".dt-job-status")
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

        $(tableRef.current).on("click", ".dt-client-contract-btn", function () {
            const _id = $(this).data("id");
            navigate(`/work-contract/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        $(tableRef.current).on("click", ".dt-job-status", function () {
            const _id = $(this).data("id");
            const status = $(this).data("status");
            handleModal(_id, status);
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
    }, [filter, dateRange]);

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
                            {Object.entries(contractStatuses).map(([key, value]) => (
                                <FilterButtons
                                    text={value}
                                    name={key}
                                    className="px-3 mr-1"
                                    key={key}
                                    selectedFilter={filter}
                                    setselectedFilter={(status) => setFilter(status)}
                                />
                            ))}
                            <input
                                type="hidden"
                                value={filter}
                                ref={statusRef}
                            />
                        </div>
                    </div>
                </div>
                <div className="col-sm-6 mt-2 pl-0 d-flex">
                    <div className="search-data">
                        <div className="action-dropdown dropdown d-flex align-items-center mt-md-4 mr-2 d-lg-none">
                            <div
                                className=" mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                {t("admin.global.filter")}
                            </div>
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

                            <div className="dropdown-menu dropdown-menu-right">

                                <button
                                    className="dropdown-item"
                                    onClick={() => {
                                        setFilter("All");
                                    }}
                                >
                                    {t("admin.leads.All")}
                                </button>
                                <button
                                    className="dropdown-item"
                                    onClick={() => {
                                        setFilter("verified");
                                    }}
                                >
                                    {t("global.verified")}
                                </button>
                                <button
                                    className="dropdown-item"
                                    onClick={() => {
                                        setFilter("un-verified");
                                    }}
                                >
                                    {t("global.unverified")}
                                </button>
                                <button
                                    className="dropdown-item"
                                    onClick={() => {
                                        setFilter("not-signed");
                                    }}
                                >
                                    {t("global.notSigned")}
                                </button>
                                <button
                                    className="dropdown-item"
                                    onClick={() => {
                                        setFilter("declined");
                                    }}
                                >
                                    {t("admin.schedule.options.meetingStatus.Declined")}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    style={{
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "left",
                    }}
                    className="hide-scrollbar mb-2"
                >
                    <p className="mr-2" style={{ fontWeight: "bold" }}>Date</p>

                    <div className="d-flex align-items-center flex-wrap">
                        <input
                            className="form-control calender"
                            type="date"
                            placeholder="From date"
                            name="from filter"
                            style={{ width: "fit-content" }}
                            value={dateRange.start_date}
                            onChange={(e) => {
                                const updatedDateRange = {
                                    start_date: e.target.value,
                                    end_date: dateRange.end_date,
                                };

                                setDateRange(updatedDateRange);
                                localStorage.setItem(
                                    "dateRange",
                                    JSON.stringify(updatedDateRange)
                                );
                            }}
                        />
                        <div className="mx-2">-</div>
                        <input
                            className="form-control calender mr-1"
                            type="date"
                            placeholder="To date"
                            name="to_filter"
                            style={{ width: "fit-content" }}
                            value={dateRange.end_date}
                            onChange={(e) => {
                                const updatedDateRange = {
                                    start_date: dateRange.start_date,
                                    end_date: e.target.value,
                                };

                                setDateRange(updatedDateRange);
                                // Corrected: JSON.stringify instead of json.stringify
                                localStorage.setItem(
                                    "dateRange",
                                    JSON.stringify(updatedDateRange)
                                );
                            }}
                        />
                        <button
                            type="button"
                            className="btn btn-default navyblue mx-1 my-1"
                            style={{
                                padding: ".195rem .6rem",
                            }}
                            onClick={() => {
                                const updatedDateRange = {
                                    start_date: "",
                                    end_date: "",
                                };
                                setDateRange(updatedDateRange);
                                localStorage.setItem(
                                    "dateRange",
                                    JSON.stringify(updatedDateRange)
                                );
                            }}
                        >
                            Reset
                        </button>
                        <input
                            type="hidden"
                            value={dateRange.start_date}
                            ref={startDateRef}
                        />

                        <input
                            type="hidden"
                            value={dateRange.end_date}
                            ref={endDateRef}
                        />

                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body p-0">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table table-bordered"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <Modal
                size="md"
                className="modal-container"
                show={statusModal}
                onHide={() => setStatusModal(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Change Status</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">{t("global.status")}</label>

                                <select
                                    name="status"
                                    onChange={(e) => setStatus(e.target.value)}
                                    value={status}
                                    className="form-control mb-3"
                                >
                                    <option value="">---select status---</option>
                                    {Object.keys(contractStatuses).map((s) => (
                                        <option key={s} value={s}>
                                            {contractStatuses[s]}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => setStatusModal(false)}
                    >
                        {t("modal.close")}
                    </Button>
                    <Button
                        type="button"
                        onClick={handleUpdateStatus}
                        className="btn btn-primary"
                    >
                        {t("global.send")}
                    </Button>
                </Modal.Footer>
            </Modal>

            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
