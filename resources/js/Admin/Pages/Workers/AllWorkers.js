import React, { useState, useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import LeaveJobWorkerModal from "../../Components/Modals/LeaveJobWorkerModal";

export default function AllWorkers() {
    const { t, i18n } = useTranslation()
    const [isOpenLeaveJobWorker, setIsOpenLeaveJobWorker] = useState(false);
    const [selectedWorkerId, setSelectedWorkerId] = useState(null);
    const [filters, setFilters] = useState({
        status: "",
        manpower_company_id: "",
        is_my_company: false,
        is_manpower: false,
        is_freelancer: false
    });
    const [manpowerCompanies, setManpowerCompanies] = useState([]);
    const [show, setShow] = useState(false);
    const [importFile, setImportFile] = useState("");
    const [isOpen, setIsOpen] = useState(false);
    const [status, setStatus] = useState("");
    const alert = useAlert();
    const navigate = useNavigate();
    const tableRef = useRef(null);
    const statusRef = useRef(null);
    const manpowerCompanyRef = useRef(null);
    const isMyCompanyRef = useRef(null);
    const isManpowerRef = useRef(null);
    const isFreelancerRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const role = localStorage.getItem("admin-role");

    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            const table = $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/workers?role=" + role,
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                    data: function (d) {
                        d.status = statusRef.current.value ? statusRef.current.value : null;
                        d.manpower_company_id = manpowerCompanyRef.current.value ? manpowerCompanyRef.current.value : null;
                        d.is_my_company = isMyCompanyRef.current.value ? isMyCompanyRef.current.value : null;
                        d.is_manpower = isManpowerRef.current.value ? isManpowerRef.current.value : null;
                        d.is_freelancer = isFreelancerRef.current.value ? isFreelancerRef.current.value : null;
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
                        title: t("admin.global.Name"),
                        data: "name",
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
                        title: t("admin.global.Address"),
                        data: "address",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            if (data) {
                                return `<a href="https://maps.google.com?q=${row.address}" target="_blank" class="dt-address-link"> ${data} </a>`;
                            } else {
                                return "NA";
                            }
                        },
                    },
                    {
                        title: t("admin.global.Status"),
                        data: "status",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            // return data == 1 ? "Active" : "Inactive";
                            return data == 1 ? `<p class="dt-status" data-id="${row.id}" style="background-color: #efefef; color: green; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        Active
                                    </p>` : `<p class="dt-status" data-id="${row.id}" style="background-color: #efefef; color: red; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        Inactive
                                    </p>` ;
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

                            if (role == "supervisor") {
                                _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>`;
                            } else {
                                _html += `<button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">${t('admin.leads.Edit')}</button>`;

                                _html += `<button type="button" class="dropdown-item dt-worker-forms-btn" data-id="${Base64.encode(row.id.toString())}">View Worker Forms</button>`;

                                _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>`;

                                _html += `<button type="button" class="dropdown-item dt-freeze-shift-btn" data-id="${row.id}">${t("global.freezeShift")}</button>`;

                                _html += `<button type="button" class="dropdown-item dt-leave-job-btn" data-id="${row.id}">${t("modal.leave_job")}</button>`;

                                _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>`;
                            }

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
                    !e.target.closest(".dt-address-link") &&
                    !e.target.closest(".dt-status") &&
                    (!tableRef.current.classList.contains("collapsed") ||
                        !e.target.closest(".dtr-control"))
                ) {
                    _id = $(this).data("id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-address-link") &&
                    !e.target.closest(".dt-status")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                navigate(`/admin/workers/view/${_id}`);
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
            navigate(`/admin/workers/edit/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/workers/view/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-worker-forms-btn", function () {
            const _id = $(this).data("id");
            window.open(`/worker-forms/${_id}`, "_blank");
        });

        $(tableRef.current).on("click", ".dt-freeze-shift-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/workers/freeze-shift/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-leave-job-btn", function () {
            const _id = $(this).data("id");
            handleLeaveJob(_id);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        if (role != "supervisor") {
            $(tableRef.current).on("click", ".dt-status", function () {
                const _id = $(this).data("id");
                setSelectedWorkerId(_id);
                setIsOpen(true);
            });
        }

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

    useEffect(() => {
        $(tableRef.current).DataTable().draw();
    }, [filters]);

    const handleLeaveJob = (_workerID) => {
        setSelectedWorkerId(_workerID);
        setIsOpenLeaveJobWorker(true);
    };

    const handleChangeStatus = async () => {
        const data = {
            workerID: selectedWorkerId,
            status: status
        }
        try {
            const res = await axios.post(`/api/admin/workers/change-status`, data, { headers });
            setIsOpen(false);
            setTimeout(() => {
                $(tableRef.current).DataTable().draw();
            }, 1000);
            alert.success(res?.data?.message);

        } catch (error) {
            console.error(error);
        }
    }

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Worker!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/workers/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Worker has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            $(tableRef.current).DataTable().draw();
                        }, 1000);
                    });
            }
        });
    };

    const getManpowerCompanies = async () => {
        await axios
            .get("/api/admin/manpower-companies-list", {
                headers,
            })
            .then((response) => {
                if (response?.data?.companies?.length > 0) {
                    setManpowerCompanies(response.data.companies);
                } else {
                    setManpowerCompanies([]);
                }
            });
    };

    useEffect(() => {
        getManpowerCompanies();
    }, []);

    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order(parseInt(colIdx), "asc").draw();
    };

    const handleImportSubmit = () => {
        const formData = new FormData();
        formData.append("file", importFile);
        axios
            .post("/api/admin/workers/import", formData, {
                headers: {
                    Accept: "application/json, text/plain, */*",
                    "Content-Type": "multipart/form-data",
                    Authorization:
                        `Bearer ` + localStorage.getItem("admin-token"),
                },
            })
            .then((response) => {
                handleClose();
                if (response.data.error) {
                    alert.error(response.data.error);
                } else {
                    alert.success(response.data.message);
                    setTimeout(() => {
                        $(tableRef.current).DataTable().draw();
                    }, 1000);
                }
            })
            .catch((error) => {
                handleClose();
                alert.error(error.message);
            });
    };

    const handleClose = () => {
        setImportFile("");
        setShow(false);
    };
    const handleShow = () => {
        setImportFile("");
        setShow(true);
    };

    useEffect(() => {
        const status = localStorage.getItem("worker-status");

        if (localStorage.getItem("company") || status) {
            const company = JSON.parse(localStorage.getItem("company") || "{}");

            setFilters(prev => ({
                ...prev,
                status: status || "",
                is_my_company: company.is_my_company || "",
                is_manpower: company.is_manpower || "",
                is_freelancer: company.is_freelancer || "",
            }));
        }
    }, []);


    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex justify-content-between align-items-center flex-wrap">
                        <div className=" d-flex justify-content-between mt-3">
                            <h1 className="page-title d-none d-md-block">
                                {t("admin.dashboard.workers")}
                            </h1>
                            <h1 className="page-title p-0 d-block d-md-none">
                                {t("admin.dashboard.workers")}
                            </h1>

                        </div>
                        {
                            role != "supervisor" && (
                                <div className="search-data">
                                    <button
                                        className="btn navyblue addButton mr-2 mr-md-2 ml-auto no-hover"
                                        onClick={handleShow}
                                    >
                                        {t("admin.global.Import")}
                                    </button>
                                    <Link
                                        to="/admin/workers/working-hours"
                                        className="btn navyblue addButton mr-md-2 ml-auto no-hover"
                                    >
                                        {t("price_offer.worker_hours")}
                                    </Link>
                                    <Link
                                        to="/admin/add-worker"
                                        className="btn navyblue d-none d-md-block mx-1 addButton no-hover"
                                    >
                                        <i className="btn-icon fas fa-plus-circle"></i>
                                        {t("admin.leads.AddNew")}
                                    </Link>
                                    <Link
                                        to="/admin/add-worker"
                                        className="btn ml-2 navyblue d-block d-md-none addButton no-hover align-content-center"
                                    >
                                        <i className="btn-icon fas fa-plus-circle"></i>
                                        {t("admin.leads.AddNew")}
                                    </Link>
                                </div>
                            )
                        }
                    </div>

                    {
                        role != "supervisor" && (
                            <>
                                <div className="col-sm-6 mt-2 pl-0">
                                    <div className="search-data">
                                        <div className="action-dropdown dropdown mt-md-4 mr-2 d-lg-none">
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
                                            }}>{filters.status || t("admin.leads.All")}</span>

                                            <div className="dropdown-menu dropdown-menu-right">
                                                <button
                                                    className="dropdown-item"
                                                    onClick={() => {
                                                        setFilters({
                                                            ...filters,
                                                            status: "active",
                                                        });
                                                        localStorage.setItem("worker-status", "active");
                                                    }}
                                                >
                                                    {t("admin.global.active")}
                                                </button>
                                                <button
                                                    className="dropdown-item"
                                                    onClick={() => {
                                                        setFilters({
                                                            ...filters,
                                                            status: "inactive",
                                                        });
                                                        localStorage.setItem("worker-status", "inactive");
                                                    }}
                                                >
                                                    {t("admin.global.inactive")}

                                                </button>
                                                <button
                                                    className="dropdown-item"
                                                    onClick={() => {
                                                        setFilters({
                                                            ...filters,
                                                            status: "past",
                                                        });
                                                        localStorage.setItem("worker-status", "past");
                                                    }}
                                                >
                                                    {t("admin.global.past")}

                                                </button>
                                                <button
                                                    className="dropdown-item"
                                                    onClick={() => {
                                                        setFilters({
                                                            ...filters,
                                                            status: "",
                                                        });
                                                        localStorage.setItem("worker-status", "");
                                                    }}
                                                >
                                                    {t("global.all")}

                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="col-sm-6 hidden-xl mt-4">
                                    <select
                                        className="form-control"
                                        onChange={(e) => sortTable(e.target.value)}
                                    >
                                        <option value="">{t("admin.leads.Options.sortBy")}</option>
                                        <option value="0">{t("admin.leads.Options.ID")}</option>
                                        <option value="1">{t("admin.leads.Options.Name")}</option>
                                        <option value="2">{t("admin.leads.Options.Email")}</option>
                                        <option value="3">{t("admin.leads.Options.Phone")}</option>
                                        <option value="4">{t("admin.leads.AddLead.addAddress.Address")}</option>
                                    </select>
                                </div>
                            </>
                        )
                    }
                </div>
                {
                    role != "supervisor" && (
                        <div className="row mb-2 d-none d-lg-block">
                            <div className="col-sm-12 d-flex align-items-center">
                                <div className="mr-3" style={{ fontWeight: "bold" }}>
                                    {t("admin.global.Status")}
                                </div>
                                <button
                                    className={`btn border rounded px-3 mr-1`}
                                    style={
                                        filters.status === "active"
                                            ? { background: "white" }
                                            : {
                                                background: "#2c3f51",
                                                color: "white",
                                            }
                                    }
                                    onClick={() => {
                                        setFilters({
                                            ...filters,
                                            status: "active",
                                        });
                                        localStorage.setItem("worker-status", "active");
                                    }}
                                >
                                    {t("admin.global.active")}
                                </button>
                                <button
                                    className={`btn border rounded px-3 mr-1`}
                                    style={
                                        filters.status === "inactive"
                                            ? { background: "white" }
                                            : {
                                                background: "#2c3f51",
                                                color: "white",
                                            }
                                    }
                                    onClick={() => {
                                        setFilters({
                                            ...filters,
                                            status: "inactive",
                                        });
                                        localStorage.setItem("worker-status", "inactive");
                                    }}
                                >
                                    {t("admin.global.inactive")}
                                </button>
                                <button
                                    className={`btn border rounded px-3 mr-1`}
                                    style={
                                        filters.status === "past"
                                            ? { background: "white" }
                                            : {
                                                background: "#2c3f51",
                                                color: "white",
                                            }
                                    }
                                    onClick={() => {
                                        setFilters({
                                            ...filters,
                                            status: "past",
                                        });
                                        localStorage.setItem("worker-status", "past");
                                    }}
                                >
                                    {t("admin.global.past")}
                                </button>
                                <button
                                    className={`btn border rounded px-3 mr-1`}
                                    style={
                                        filters.status === ""
                                            ? { background: "white" }
                                            : {
                                                background: "#2c3f51",
                                                color: "white",
                                            }
                                    }
                                    onClick={() => {
                                        setFilters({
                                            ...filters,
                                            status: "",
                                        });
                                        localStorage.setItem("worker-status", "");
                                    }}
                                >
                                    {t("global.all")}
                                </button>
                            </div>
                            <div className="col-sm-12 d-flex mt-2">
                                <div
                                    className="mr-3 align-items-center"
                                    style={{ fontWeight: "bold" }}
                                >
                                    {t("admin.global.manpower_company")}
                                </div>
                                <div className="d-flex">
                                    <select
                                        className="form-control"
                                        onChange={(e) => {
                                            setFilters({
                                                ...filters,
                                                manpower_company_id: e.target.value,
                                                is_my_company: false,
                                            });
                                        }}
                                        value={filters.manpower_company_id}
                                    >
                                        <option value="">--- Select ---</option>

                                        {manpowerCompanies?.map((company, _index) => (
                                            <option key={_index} value={company.id}>
                                                {" "}
                                                {company.name}
                                            </option>
                                        ))}
                                    </select>
                                    <button
                                        className={`btn border rounded px-3 mx-1`}
                                        style={
                                            filters.is_my_company === true
                                                ? { background: "white" }
                                                : {
                                                    background: "#2c3f51",
                                                    color: "white",
                                                }
                                        }
                                        onClick={() => {
                                            setFilters({
                                                ...filters,
                                                manpower_company_id: "",
                                                is_my_company: true,
                                                is_manpower: false,
                                                is_freelancer: false
                                            });
                                            const company = {
                                                is_my_company: true,
                                                is_manpower: false,
                                                is_freelancer: false
                                            };

                                            localStorage.setItem("company", JSON.stringify(company));
                                        }}
                                    >
                                        {t("admin.global.myCompany")}
                                    </button>
                                    <button
                                        className={`btn border rounded px-3 mx-1`}
                                        style={
                                            filters.is_manpower === true
                                                ? { background: "white" }
                                                : {
                                                    background: "#2c3f51",
                                                    color: "white",
                                                }
                                        }
                                        onClick={() => {
                                            setFilters({
                                                ...filters,
                                                manpower_company_id: "",
                                                is_manpower: true,
                                                is_my_company: false,
                                                is_freelancer: false
                                            });
                                            const company = {
                                                is_my_company: false,
                                                is_manpower: true,
                                                is_freelancer: false
                                            };

                                            localStorage.setItem("company", JSON.stringify(company));
                                        }}
                                    >
                                        {t("admin.global.manpower_company")}
                                    </button>
                                    <button
                                        className={`btn border rounded px-3 mx-1`}
                                        style={
                                            filters.is_freelancer === true
                                                ? { background: "white" }
                                                : {
                                                    background: "#2c3f51",
                                                    color: "white",
                                                }
                                        }
                                        onClick={() => {
                                            setFilters({
                                                ...filters,
                                                manpower_company_id: "",
                                                is_freelancer: true,
                                                is_my_company: false,
                                                is_manpower: false
                                            });
                                            const company = {
                                                is_my_company: false,
                                                is_manpower: false,
                                                is_freelancer: true
                                            };

                                            localStorage.setItem("company", JSON.stringify(company));
                                        }}
                                    >
                                        {t("admin.global.freelancer")}
                                    </button>
                                    <button
                                        className={`btn border rounded px-3 mx-1`}
                                        style={
                                            (filters.is_my_company !== true) && (filters.is_freelancer !== true) && (filters.is_manpower !== true) &&
                                                filters.manpower_company_id === ""
                                                ? { background: "white" }
                                                : {
                                                    background: "#2c3f51",
                                                    color: "white",
                                                }
                                        }
                                        onClick={() => {
                                            setFilters({
                                                ...filters,
                                                manpower_company_id: "",
                                                is_my_company: false,
                                                is_manpower: false,
                                                is_freelancer: false,
                                            });
                                            const company = {
                                                is_my_company: false,
                                                is_manpower: false,
                                                is_freelancer: false
                                            };

                                            localStorage.setItem("company", JSON.stringify(company));
                                        }}
                                    >
                                        {t("admin.global.All")}
                                    </button>
                                </div>

                            </div>
                        </div>
                    )
                }

                <input
                    type="hidden"
                    value={filters.status}
                    ref={statusRef}
                />

                <input
                    type="hidden"
                    value={filters.manpower_company_id}
                    ref={manpowerCompanyRef}
                />

                <input
                    type="hidden"
                    value={filters.is_my_company}
                    ref={isMyCompanyRef}
                />
                <input
                    type="hidden"
                    value={filters.is_manpower}
                    ref={isManpowerRef}
                />
                <input
                    type="hidden"
                    value={filters.is_freelancer}
                    ref={isFreelancerRef}
                />
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

                {isOpenLeaveJobWorker && (
                    <LeaveJobWorkerModal
                        setIsOpen={setIsOpenLeaveJobWorker}
                        isOpen={isOpenLeaveJobWorker}
                        workerId={selectedWorkerId}
                    />
                )}

                <Modal show={show} onHide={handleClose}>
                    <Modal.Header closeButton>
                        <Modal.Title>{t("admin.global.import_file")}</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <a href="/api/admin/workers/import/sample">
                            {t("admin.global.download_sample_file")}
                        </a>
                        <form encType="multipart/form-data">
                            <div className="row mt-2">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <input
                                            type="file"
                                            onChange={(e) =>
                                                setImportFile(e.target.files[0])
                                            }
                                            className="form-control"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleClose}>
                            {t("global.close")}
                        </Button>
                        <Button
                            className="btn btn-pink"
                            onClick={handleImportSubmit}
                        >
                            {t("admin.global.submit")}
                        </Button>
                    </Modal.Footer>
                </Modal>
            </div>
            <Modal
                size="md"
                className="modal-container"
                show={isOpen}
                onHide={() => setIsOpen(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Change status</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">Status</label>

                                <select
                                    name="status"
                                    onChange={(e) => setStatus(e.target.value)}
                                    value={status}
                                    className="form-control mb-3"
                                >
                                    <option value=""> ---Select Status---</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => setIsOpen(false)}
                    >
                        Close
                    </Button>
                    <Button
                        type="button"
                        onClick={handleChangeStatus}
                        className="btn btn-primary"
                    >
                        Save
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    );
}
