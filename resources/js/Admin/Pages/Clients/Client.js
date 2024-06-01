import React, { useState, useEffect, useRef, useLayoutEffect } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import ReactPaginate from "react-paginate";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useNavigate } from "react-router-dom";
import { CSVLink } from "react-csv";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";

import Sidebar from "../../Layouts/Sidebar";
import ChangeStatusModal from "../../Components/Modals/ChangeStatusModal";
import { leadStatusColor } from "../../../Utils/client.utils";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

export default function Clients() {
    const [clients, setClients] = useState([]);
    const [show, setShow] = useState(false);
    const [importFile, setImportFile] = useState("");
    const [changeStatusModal, setChangeStatusModal] = useState({
        isOpen: false,
        id: 0,
    });
    const [filters, setFilters] = useState({
        action: "",
    });

    const tableRef = useRef(null);
    const actionRef = useRef(null);

    useEffect(() => {
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
                    d.action = actionRef.current.value;
                },
            },
            order: [[0, "desc"]],
            columns: [
                {
                    title: "ID",
                    data: "id",
                },
                {
                    title: "Name",
                    data: "name",
                },
                {
                    title: "Email",
                    data: "email",
                },
                {
                    title: "Phone",
                    data: "phone",
                },
                {
                    title: "Status",
                    data: "lead_status",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        const _statusColor = leadStatusColor(data);

                        return `<span class="badge" style="background-color: ${_statusColor.backgroundColor}; color: #fff;" > ${data} </span>`;
                    },
                },
                {
                    title: "Action",
                    data: "action",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let _html =
                            '<div class="dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i className="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                        if (row.has_contract == 1) {
                            _html += `<button type="button" class="dropdown-item dt-create-job-btn" data-id="${row.id}">Create Job</button>`;
                        }

                        _html += `<button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">Edit</button>`;

                        _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">View</button>`;

                        _html += `<button type="button" class="dropdown-item dt-change-status-btn" data-id="${row.id}">Change status</button>`;

                        _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">Delete</button>`;

                        _html += "</div> </div>";

                        return _html;
                    },
                },
            ],
            ordering: true,
            searching: true,
            responsive: true,
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
            navigate(`/admin/view-client/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-change-status-btn", function () {
            const _id = $(this).data("id");
            toggleChangeStatusModal(_id);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        return function cleanup() {
            $(tableRef.current).DataTable().destroy(true);
        };
    }, []);

    const alert = useAlert();
    const { t } = useTranslation();

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
        $(tableRef.current).DataTable().draw();
    }, [filters]);

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
            .get("/api/admin/clients_export?" + cn + filters.action, {
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
                        <div className="d-flex mt-2 d-lg-none justify-content-between">
                            <h1 className="page-title p-0">
                                {t("admin.sidebar.Clients")}
                            </h1>
                            <Link
                                to="/admin/clients/create"
                                className="btn btn-pink addButton"
                            >
                                <i className="btn-icon fas fa-plus-circle"></i>
                                Add New
                            </Link>
                        </div>

                        <div className="clearfix w-100 justify-content-between align-items-center">
                            <h1 className="page-title d-none d-lg-block float-left">
                                {t("admin.sidebar.Clients")}
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
                                        className="btn btn-pink"
                                        onClick={handleShow}
                                    >
                                        {t("admin.global.Import")}
                                    </button>
                                </div>
                                <div className="action-dropdown dropdown mt-4 mr-2 d-none d-lg-block">
                                    <button
                                        className="btn btn-pink ml-2"
                                        onClick={(e) => handleReport(e)}
                                    >
                                        {t("admin.client.Export")}
                                    </button>
                                </div>

                                <div className="action-dropdown dropdown mt-4 mr-2 d-lg-none">
                                    <button
                                        type="button"
                                        className="btn btn-default dropdown-toggle"
                                        data-toggle="dropdown"
                                    >
                                        <i className="fa fa-filter"></i>
                                    </button>
                                    <div className="dropdown-menu">
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => {
                                                setFilters({
                                                    action: "",
                                                });
                                            }}
                                        >
                                            {t("admin.global.All")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => {
                                                setFilters({
                                                    action: "booked",
                                                });
                                            }}
                                        >
                                            {t("admin.client.BookedCustomer")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={(e) => {
                                                setFilters({
                                                    action: "notbooked",
                                                });
                                            }}
                                        >
                                            {t(
                                                "admin.client.NotBookedCustomer"
                                            )}
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
                                    className="btn btn-pink addButton d-none d-lg-block  action-dropdown dropdown mt-4 mr-2"
                                >
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    Add New
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
                <div className="row mb-2 d-none d-lg-block">
                    <div className="col-sm-12 d-flex align-items-center">
                        <div className="mr-3" style={{ fontWeight: "bold" }}>
                            Status
                        </div>
                        <FilterButtons
                            text={t("admin.global.All")}
                            className="px-3 mr-1"
                            value=""
                            onClick={() => {
                                setFilters({
                                    action: "",
                                });
                            }}
                            selectedFilter={filters.action}
                        />

                        <FilterButtons
                            text={t("admin.client.BookedCustomer")}
                            value="booked"
                            className="px-3 mr-1"
                            onClick={() => {
                                setFilters({
                                    action: "booked",
                                });
                            }}
                            selectedFilter={filters.action}
                        />
                        <FilterButtons
                            text={t("admin.client.NotBookedCustomer")}
                            className="px-3 mr-1"
                            value="notbooked"
                            onClick={() => {
                                setFilters({
                                    action: "notbooked",
                                });
                            }}
                            selectedFilter={filters.action}
                        />
                    </div>
                </div>
                <div className="card">
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
                />
            )}
        </div>
    );
}
const FilterButtons = ({ text, className, selectedFilter, onClick, value }) => (
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
