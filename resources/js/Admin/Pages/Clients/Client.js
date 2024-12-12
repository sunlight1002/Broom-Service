import React, { useState, useEffect, useRef } from "react";
import { Link, useLocation } from "react-router-dom";
import axios from "axios";
import { useNavigate } from "react-router-dom";
import { CSVLink } from "react-csv";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
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

export default function Clients() {

    const location = useLocation();
    const params = new URLSearchParams(location.search);
    const type = params.get("type");
    const [show, setShow] = useState(false);
    const [importFile, setImportFile] = useState("");
    const [changeStatusModal, setChangeStatusModal] = useState({
        isOpen: false,
        id: 0,
    });

    const tableRef = useRef(null);
    const actionRef = useRef(null);

    const alert = useAlert();
    const { t, i18n } = useTranslation();

    const [filter, setFilter] = useState('');

    console.log(filter);
    
    const leadStatuses = [
        t("admin.client.Potential"),
        t("admin.client.Waiting_client"),
        "active client",
        t("admin.client.Freeze_client"),
        t("admin.client.Past_client"),
    ];

    const [filters, set_Filters] = useState({
        action: "past",
    });

    const statusArr = type === "past"
        ? {
            "unhappy": t("admin.client.Unhappy"),
            "price issue": t("admin.client.Price_issue"),
            "moved": t("admin.client.Moved"),
            "one-time": t("admin.client.One_Time"),
        }
        : {
            "pending client": "Waiting",
            "freeze client": t("admin.client.Freeze_client"),
            "active client": t("admin.client.Active_client"),
        };

    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/clients",
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                    data: function (d) {
                        d.type = type;
                        d.action = filters.action;
                        d.lead_status = filter === "All" ? "" : filter;
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: t("global.date"),
                        data: "created_at",
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
                        title: t("admin.global.Status"),
                        data: "lead_status",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            const _statusColor = leadStatusColor(data);
                            // console.log(data);

                            // return `<span class="badge" style="background-color: ${_statusColor.backgroundColor}; color: #fff;" > ${data} </span>`;
                            return `<p style="background-color: ${_statusColor.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                            ${data}
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

                            if (row.has_contract == 1) {
                                _html += `<button type="button" class="dropdown-item dt-create-job-btn" data-id="${row.id}">Create Job</button>`;
                            }

                            _html += `<button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">${t('admin.leads.Edit')}</button>`;

                            _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>`;

                            _html += `<button type="button" class="dropdown-item dt-change-status-btn" data-id="${row.id}">${t("admin.leads.change_status")}</button>`;

                            _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>`;

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
                navigate(`/admin/clients/view/${_id}`);
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

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/clients/${_id}/edit`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/clients/view/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-change-status-btn", function () {
            const _id = $(this).data("id");
            toggleChangeStatusModal(_id);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });


        // Handle language changes
        i18n.on("languageChanged", () => {
            $(tableRef.current).DataTable().destroy(); // Destroy the table
            initializeDataTable(initialPage); // Reinitialize the table with updated language
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

    const handleClose = () => {
        setImportFile("");
        setShow(false);
    };
    const handleShow = () => {
        setImportFile("");
        setShow(true);
    };

    const navigate = useNavigate();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const formHeaders = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleImportSubmit = () => {
        const formData = new FormData();
        formData.append("file", importFile);
        axios
            .post("/api/admin/import-clients", formData, {
                headers: formHeaders,
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

    useEffect(() => {
        if (type == "past") {
            $(tableRef.current).DataTable().column(4).search(filters.action).draw();
        } else if (type == null) {
            // $(tableRef.current).DataTable().column(4).search('').draw();
            $(tableRef.current).DataTable().column(4).search(filter).draw();
        } else {
            $(tableRef.current).DataTable().column(4).search(type).draw();
        }
    }, [type, filters]);

    console.log(type, filters,);
    

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Client!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/clients/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Client has been deleted.",
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

    const [Alldata, setAllData] = useState([]);

    const handleReport = (e) => {
        e.preventDefault();

        let cn =
            filters.action == "booked" || filters.action == "notbooked"
                ? "action="
                : "f=";

        axios
            .get("/api/admin/clients_export?" + cn + type, {
                headers,
            })
            .then((response) => {
                if (response.data.clients.length > 0) {
                    let r = response.data.clients;

                    if (r.length > 0) {
                        for (let k in r) {
                            delete r[k]["extra"];
                            delete r[k]["jobs"];
                        }
                    }
                    setAllData(r);
                    document.querySelector("#csv").click();
                } else {
                }
            });
    };

    const csvReport = {
        data: Alldata,
        filename: "clients",
    };

    const toggleChangeStatusModal = (clientId = 0) => {
        setChangeStatusModal((prev) => {
            return {
                isOpen: !prev.isOpen,
                id: clientId,
            };
        });
    };

    const updateData = () => {
        setTimeout(() => {
            $(tableRef.current).DataTable().draw();
        }, 1000);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex flex-column flex-lg-row">
                        <div className="d-flex mt-2 d-lg-none justify-content-between no-hover">
                            <h1 className="page-title p-0">
                                {t("admin.sidebar.clients")}
                            </h1>
                            <Link
                                to="/admin/clients/create"
                                className="btn navyblue align-content-center addButton no-hover"
                            >
                                <i className="btn-icon fas fa-plus-circle"></i>
                                {t("admin.client.AddNew")}
                            </Link>
                        </div>

                        <div className="clearfix w-100 justify-content-between align-items-center">
                            <h1 className="page-title d-none d-lg-block float-left">
                                {t("admin.sidebar.clients")}
                            </h1>
                            <div className="search-data">
                                <div
                                    className="App"
                                    style={{ display: "none" }}
                                >
                                    <CSVLink {...csvReport} id="csv">
                                        {t("admin.global.Export")}
                                    </CSVLink>
                                </div>
                                <div className="action-dropdown dropdown mt-4 mr-2">
                                    <button
                                        className="btn navyblue"
                                        onClick={handleShow}
                                    >
                                        {t("admin.global.Import")}
                                    </button>
                                </div>
                                <div className="action-dropdown dropdown mt-4 mr-2 d-none d-lg-block">
                                    <button
                                        className="btn navyblue ml-2"
                                        onClick={(e) => handleReport(e)}
                                    >
                                        {t("admin.client.Export")}
                                    </button>
                                </div>

                                <div className="action-dropdown dropdown mt-4 mr-2 d-lg-none">
                                    <button
                                        type="button"
                                        className="btn navyblue dropdown-toggle"
                                        data-toggle="dropdown"
                                    >
                                        <i className="fa fa-filter"></i>
                                    </button>
                                    <div className="dropdown-menu dropdown-menu-right">
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => {
                                                set_Filters({
                                                    action: "past",
                                                });
                                            }}
                                        >
                                            Past
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                set_Filters({
                                                    action: "unhappy",
                                                });
                                            }}
                                        >
                                            Unhappy
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                set_Filters({
                                                    action: "price issue",
                                                });
                                            }}
                                        >
                                            Price Issue
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                set_Filters({
                                                    action: "moved",
                                                });
                                            }}
                                        >
                                            Moved
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                set_Filters({
                                                    action: "one-Time",
                                                });
                                            }}
                                        >
                                            One-Time
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => handleReport(e)}
                                        >
                                            {t("admin.client.Export")}
                                        </button>

                                        <input
                                            type="hidden"
                                            value={filters.action}
                                            ref={actionRef}
                                        />
                                    </div>
                                </div>

                                <Link
                                    to="/admin/clients/create"
                                    className="btn navyblue addButton d-none d-lg-block  action-dropdown dropdown mt-4 mr-2 no-hover"
                                >
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    {t("admin.client.AddNew")}
                                </Link>
                            </div>
                        </div>
                        <div className="hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="0">ID</option>
                                <option value="1">Name</option>
                                <option value="2">Email</option>
                                <option value="3">Phone</option>
                            </select>
                        </div>
                    </div>
                </div>
                {
                    type == "past" && (
                        <div className="row mb-2 d-none d-lg-block">
                            <div className="col-sm-12 d-flex align-items-center">
                                <div className="mr-3" style={{ fontWeight: "bold" }}>
                                    Status
                                </div>
                                <Filter_Buttons
                                    text="Past"
                                    className="px-3 mr-1"
                                    value="past"
                                    onClick={() => {
                                        set_Filters({
                                            action: "past",
                                        });
                                    }}
                                    selectedFilter={filters.action}
                                />

                                <Filter_Buttons
                                    text="Unhappy"
                                    value="unhappy"
                                    className="px-3 mr-1"
                                    onClick={() => {
                                        set_Filters({
                                            action: "unhappy",
                                        });
                                    }}
                                    selectedFilter={filters.action}
                                />
                                <Filter_Buttons
                                    text="Price issue"
                                    className="px-3 mr-1"
                                    value="price issue"
                                    onClick={() => {
                                        set_Filters({
                                            action: "price issue",
                                        });
                                    }}
                                    selectedFilter={filters.action}
                                />
                                <Filter_Buttons
                                    text="Moved"
                                    className="px-3 mr-1"
                                    value="moved"
                                    onClick={() => {
                                        set_Filters({
                                            action: "moved",
                                        });
                                    }}
                                    selectedFilter={filters.action}
                                />
                                <Filter_Buttons
                                    text="One-Time"
                                    className="px-3 mr-1"
                                    value="one-Time"
                                    onClick={() => {
                                        set_Filters({
                                            action: "one-Time",
                                        });
                                    }}
                                    selectedFilter={filters.action}
                                />
                            </div>
                        </div>
                    )
                }
                {/* Integrating new FilterButtons section here */}
                {/* {
                    type != "past" && (
                        <div className="col-sm-12">
                            <FilterButtons
                                text={t("admin.leads.All")}
                                className="px-3 mr-1"
                                selectedFilter={filter}
                                setselectedFilter={(status) => {
                                    setFilter(status);
                                }}
                            />
                            {leadStatuses.map((_status, _index) => {
                                return (
                                    <FilterButtons
                                        text={_status}
                                        className="px-3 mr-1"
                                        key={_index}
                                        selectedFilter={filter}
                                        setselectedFilter={(status) => {
                                            setFilter(status);
                                        }}
                                    />
                                );
                            })}
                        </div>
                    )
                } */}
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
            <Modal show={show} onHide={handleClose}>
                <Modal.Header closeButton>
                    <Modal.Title>Import File</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <a href="/api/admin/clients-sample-file">
                        Download sample file
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
                        Close
                    </Button>
                    <Button
                        className="btn btn-pink"
                        onClick={handleImportSubmit}
                    >
                        Submit
                    </Button>
                </Modal.Footer>
            </Modal>
            {changeStatusModal.isOpen && (
                <ChangeStatusModal
                    handleChangeStatusModalClose={toggleChangeStatusModal}
                    isOpen={changeStatusModal.isOpen}
                    clientId={changeStatusModal.id}
                    getUpdatedData={updateData}
                    statusArr={statusArr}
                />
            )}
        </div>
    );
}
const Filter_Buttons = ({ text, className, selectedFilter, onClick, value }) => (
    <button
        className={`btn border rounded ${className}`}
        style={
            selectedFilter === value
                ? { background: "white" }
                : {
                    background: "#2c3f51",
                    color: "white",
                }
        }
        onClick={() => {
            onClick?.();
        }}
    >
        {text}
    </button>
);
