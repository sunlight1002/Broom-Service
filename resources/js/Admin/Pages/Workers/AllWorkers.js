import React, { useState, useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import LeaveJobWorkerModal from "../../Components/Modals/LeaveJobWorkerModal";

export default function AllWorkers() {
    const [isOpenLeaveJobWorker, setIsOpenLeaveJobWorker] = useState(false);
    const [selectedWorkerId, setSelectedWorkerId] = useState(null);
    const [filters, setFilters] = useState({
        status: "",
        manpower_company_id: null,
    });
    const [manpowerCompanies, setManpowerCompanies] = useState([]);

    const navigate = useNavigate();
    const tableRef = useRef(null);
    const statusRef = useRef(null);
    const manpowerCompanyRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        $(tableRef.current).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/api/admin/workers",
                type: "GET",
                beforeSend: function (request) {
                    request.setRequestHeader(
                        "Authorization",
                        `Bearer ` + localStorage.getItem("admin-token")
                    );
                },
                data: function (d) {
                    d.status = statusRef.current.value;
                    d.manpower_company_id = manpowerCompanyRef.current.value;
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
                    title: "Address",
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
                    title: "Status",
                    data: "status",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return data == 1 ? "Active" : "Inactive";
                    },
                },
                {
                    title: "Action",
                    data: "action",
                    orderable: false,
                    responsivePriority: 1,
                    render: function (data, type, row, meta) {
                        let _html =
                            '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                        _html += `<button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">Edit</button>`;

                        _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">View</button>`;

                        _html += `<button type="button" class="dropdown-item dt-freeze-shift-btn" data-id="${row.id}">Freeze Shift</button>`;

                        _html += `<button type="button" class="dropdown-item dt-leave-job-btn" data-id="${row.id}">Leave Job</button>`;

                        _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">Delete</button>`;

                        _html += "</div> </div>";

                        return _html;
                    },
                },
            ],
            ordering: true,
            searching: true,
            responsive: true,
            createdRow: function (row, data, dataIndex) {
                $(row).addClass("dt-row");
                $(row).attr("data-id", data.id);
            },
        });

        $(tableRef.current).on("click", "tr.dt-row,tr.child", function (e) {
            let _id = null;
            if (e.target.closest("tr.dt-row")) {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-address-link") &&
                    (!tableRef.current.classList.contains("collapsed") ||
                        !e.target.closest(".dtr-control"))
                ) {
                    _id = $(this).data("id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-address-link")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                navigate(`/admin/view-worker/${_id}`);
            }
        });

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/edit-worker/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/view-worker/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-freeze-shift-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/freeze-shift/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-leave-job-btn", function () {
            const _id = $(this).data("id");
            handleLeaveJob(_id);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        return function cleanup() {
            $(tableRef.current).DataTable().destroy(true);
        };
    }, []);

    useEffect(() => {
        $(tableRef.current).DataTable().draw();
    }, [filters]);

    const handleLeaveJob = (_workerID) => {
        setSelectedWorkerId(_workerID);
        setIsOpenLeaveJobWorker(true);
    };

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

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6 d-flex justify-content-between">
                            <h1 className="page-title d-none d-md-block">
                                Workers
                            </h1>
                            <h1 className="page-title p-0 d-block d-md-none">
                                Workers
                            </h1>
                            <Link
                                to="/admin/add-worker"
                                className="btn btn-pink d-block d-md-none addButton"
                            >
                                <i className="btn-icon fas fa-plus-circle"></i>
                                Add New
                            </Link>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <Link
                                    to="/admin/workers/working-hours"
                                    className="btn btn-pink addButton mr-0 mr-md-2  ml-auto"
                                >
                                    Worker Hours
                                </Link>
                                <Link
                                    to="/admin/add-worker"
                                    className="btn btn-pink d-none d-md-block addButton"
                                >
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    Add New
                                </Link>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="0">ID</option>
                                <option value="1">Name</option>
                                <option value="2">Email</option>
                                <option value="3">Phone</option>
                                <option value="4">Address</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="row mb-2 d-none d-lg-block">
                    <div className="col-sm-12 d-flex align-items-center">
                        <div className="mr-3" style={{ fontWeight: "bold" }}>
                            Status
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
                            }}
                        >
                            Active
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
                            }}
                        >
                            Past
                        </button>
                    </div>
                    <div className="col-sm-12 d-flex mt-2">
                        <div
                            className="mr-3 align-items-center"
                            style={{ fontWeight: "bold" }}
                        >
                            Manpower Company
                        </div>
                        <div>
                            {/* <button
                                className={`btn border rounded px-3 mr-1 float-left`}
                                style={
                                    filters.manpower_company_id === null
                                        ? { background: "white" }
                                        : {
                                              background: "#2c3f51",
                                              color: "white",
                                          }
                                }
                                onClick={() => {
                                    setFilters({
                                        ...filters,
                                        manpower_company_id: null,
                                    });
                                }}
                            >
                                All
                            </button>
                            {manpowerCompanies.map((company, _index) => (
                                <button
                                    key={_index}
                                    className={`btn border rounded px-3 mr-1 float-left`}
                                    style={
                                        filters.manpower_company_id ===
                                        company.id
                                            ? { background: "white" }
                                            : {
                                                  background: "#2c3f51",
                                                  color: "white",
                                              }
                                    }
                                    onClick={() => {
                                        setFilters({
                                            ...filters,
                                            manpower_company_id: company.id,
                                        });
                                    }}
                                >
                                    {company.name}
                                </button>
                            ))} */}
                            <select
                                className="form-control"
                                onChange={(e) => {
                                    setFilters({
                                        ...filters,
                                        manpower_company_id: e.target.value,
                                    });
                                }}
                            >
                                <option value="">All</option>

                                {manpowerCompanies.map((company, _index) => (
                                    <option key={_index} value={company.id}>
                                        {" "}
                                        {company.name}
                                    </option>
                                ))}
                            </select>
                        </div>

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

                {isOpenLeaveJobWorker && (
                    <LeaveJobWorkerModal
                        setIsOpen={setIsOpenLeaveJobWorker}
                        isOpen={isOpenLeaveJobWorker}
                        workerId={selectedWorkerId}
                    />
                )}
            </div>
        </div>
    );
}
