import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import ReactPaginate from "react-paginate";
import axios from "axios";
import Swal from "sweetalert2";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useNavigate } from "react-router-dom";

import Sidebar from "../../Layouts/Sidebar";
import LeaveJobWorkerModal from "../../Components/Modals/LeaveJobWorkerModal";

export default function AllWorkers() {
    const [workers, setWorkers] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState("Loading...");
    const [isOpenLeaveJobWorker, setIsOpenLeaveJobWorker] = useState(false);
    const [selectedWorkerId, setSelectedWorkerId] = useState(null);
    const [currentPage, setCurrentPage] = useState(0);
    const [filters, setFilters] = useState({
        status: "",
        q: "",
        manpower_company_id: null,
    });
    const [manpowerCompanies, setManpowerCompanies] = useState([]);

    const navigate = useNavigate();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getWorkers = async () => {
        let _filters = {};

        if (filters.status) {
            _filters.status = filters.status;
        }

        if (filters.q) {
            _filters.q = filters.q;
        }

        if (filters.manpower_company_id) {
            _filters.manpower_company_id = filters.manpower_company_id;
        }

        await axios
            .get("/api/admin/workers", {
                headers,
                params: {
                    page: currentPage,
                    ..._filters,
                },
            })
            .then((response) => {
                if (response.data.workers.data.length > 0) {
                    setWorkers(response.data.workers.data);
                    setPageCount(response.data.workers.last_page);
                } else {
                    setWorkers([]);
                    setLoading("No Workers found");
                }
            });
    };

    useEffect(() => {
        getWorkers();
    }, [currentPage, filters]);

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
                            getWorkers();
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

    const handleNavigate = (e, id) => {
        e.preventDefault();
        navigate(`/admin/view-worker/${id}`);
    };

    const copy = [...workers];
    const [order, setOrder] = useState("ASC");
    const sortTable = (e, col) => {
        let n = e.target.nodeName;
        if (n != "SELECT") {
            if (n == "TH") {
                let q = e.target.querySelector("span");
                if (q.innerHTML === "↑") {
                    q.innerHTML = "↓";
                } else {
                    q.innerHTML = "↑";
                }
            } else {
                let q = e.target;
                if (q.innerHTML === "↑") {
                    q.innerHTML = "↓";
                } else {
                    q.innerHTML = "↑";
                }
            }
        }

        if (order == "ASC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? 1 : -1
            );
            setWorkers(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setWorkers(sortData);
            setOrder("ASC");
        }
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6 d-flex justify-content-between">
                            <h1 className="page-title d-none d-md-block">Workers</h1>
                            <h1 className="page-title p-0 d-block d-md-none">Workers</h1>
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
                                <input
                                    type="text"
                                    className="form-control"
                                    onChange={(e) => {
                                        setFilters({
                                            status: "",
                                            q: e.target.value,
                                        });
                                    }}
                                    placeholder="Search"
                                />
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
                                onChange={(e) => sortTable(e, e.target.value)}
                            >
                                <option value="">-- Sort By--</option>
                                <option value="id">ID</option>
                                <option value="firstname">Worker Name</option>
                                <option value="address">Address</option>
                                <option value="email">Email</option>
                                <option value="phone">Phone</option>
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
                                    status: "active",
                                    q: "",
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
                                    status: "past",
                                    q: "",
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
                            ))}
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        {/* <WorkerFilter getWorkerFilter={getWorkerFilter}/> */}
                        <div className="boxPanel">
                            <div className="Table-responsive">
                                {workers.length > 0 ? (
                                    <Table className="table table-bordered">
                                        <Thead>
                                            <Tr style={{ cursor: "pointer" }}>
                                                <Th
                                                    onClick={(e) => {
                                                        sortTable(e, "id");
                                                    }}
                                                >
                                                    ID{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;{" "}
                                                    </span>
                                                </Th>
                                                <Th
                                                    onClick={(e) => {
                                                        sortTable(
                                                            e,
                                                            "firstname"
                                                        );
                                                    }}
                                                >
                                                    Worker{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;{" "}
                                                    </span>
                                                </Th>
                                                <Th
                                                    onClick={(e) => {
                                                        sortTable(e, "email");
                                                    }}
                                                >
                                                    Email{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;{" "}
                                                    </span>
                                                </Th>
                                                <Th
                                                    onClick={(e) => {
                                                        sortTable(e, "address");
                                                    }}
                                                >
                                                    Address{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;{" "}
                                                    </span>
                                                </Th>
                                                <Th
                                                    onClick={(e) => {
                                                        sortTable(e, "phone");
                                                    }}
                                                >
                                                    Phone{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;{" "}
                                                    </span>
                                                </Th>
                                                <Th>Status</Th>
                                                <Th>Action</Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {workers.map((item, index) => {
                                                let cords =
                                                    item.latitude &&
                                                    item.longitude
                                                        ? item.latitude +
                                                          "," +
                                                          item.longitude
                                                        : "";

                                                return (
                                                    <Tr
                                                        style={{
                                                            cursor: "pointer",
                                                        }}
                                                        key={index}
                                                    >
                                                        <Td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                        >
                                                            {item.id}
                                                        </Td>
                                                        <Td>
                                                            <Link
                                                                to={`/admin/view-worker/${item.id}`}
                                                            >
                                                                {item.firstname}{" "}
                                                                {item.lastname}
                                                            </Link>
                                                        </Td>
                                                        <Td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                        >
                                                            {item.email}
                                                        </Td>
                                                        <Td>
                                                            <a
                                                                href={`https://maps.google.com?q=${cords}`}
                                                                target="_blank"
                                                            >
                                                                {item.address}
                                                            </a>
                                                        </Td>
                                                        <Td>
                                                            <a
                                                                href={`tel:${item.phone}`}
                                                            >
                                                                {item.phone}
                                                            </a>
                                                        </Td>
                                                        <Td
                                                            onClick={(e) =>
                                                                handleNavigate(
                                                                    e,
                                                                    item.id
                                                                )
                                                            }
                                                        >
                                                            {item.status == 0
                                                                ? "Inactive"
                                                                : "Active"}
                                                        </Td>
                                                        <Td>
                                                            <div className="action-dropdown dropdown">
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-default dropdown-toggle"
                                                                    data-toggle="dropdown"
                                                                >
                                                                    <i className="fa fa-ellipsis-vertical"></i>
                                                                </button>
                                                                <div className="dropdown-menu">
                                                                    <Link
                                                                        to={`/admin/edit-worker/${item.id}`}
                                                                        className="dropdown-item"
                                                                    >
                                                                        Edit
                                                                    </Link>
                                                                    <Link
                                                                        to={`/admin/view-worker/${item.id}`}
                                                                        className="dropdown-item"
                                                                    >
                                                                        View
                                                                    </Link>
                                                                    <Link
                                                                        to={`/admin/freeze-shift/${item.id}`}
                                                                        className="dropdown-item"
                                                                    >
                                                                        Freeze
                                                                        Shift
                                                                    </Link>
                                                                    <button
                                                                        className="dropdown-item"
                                                                        onClick={() =>
                                                                            handleLeaveJob(
                                                                                item.id
                                                                            )
                                                                        }
                                                                    >
                                                                        Leave
                                                                        Job
                                                                    </button>
                                                                    <button
                                                                        className="dropdown-item"
                                                                        onClick={() =>
                                                                            handleDelete(
                                                                                item.id
                                                                            )
                                                                        }
                                                                    >
                                                                        Delete
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </Td>
                                                    </Tr>
                                                );
                                            })}
                                        </Tbody>
                                    </Table>
                                ) : (
                                    <p className="text-center mt-5">
                                        {loading}
                                    </p>
                                )}
                                {workers.length > 0 && (
                                    <ReactPaginate
                                        previousLabel={"Previous"}
                                        nextLabel={"Next"}
                                        breakLabel={"..."}
                                        pageCount={pageCount}
                                        marginPagesDisplayed={2}
                                        pageRangeDisplayed={3}
                                        onPageChange={(data) => {
                                            setCurrentPage(data.selected + 1);
                                        }}
                                        containerClassName={
                                            "pagination justify-content-end mt-3"
                                        }
                                        pageClassName={"page-item"}
                                        pageLinkClassName={"page-link"}
                                        previousClassName={"page-item"}
                                        previousLinkClassName={"page-link"}
                                        nextClassName={"page-item"}
                                        nextLinkClassName={"page-link"}
                                        breakClassName={"page-item"}
                                        breakLinkClassName={"page-link"}
                                        activeClassName={"active"}
                                    />
                                )}
                            </div>
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
