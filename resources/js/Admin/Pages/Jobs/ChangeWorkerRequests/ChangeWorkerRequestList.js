import React, { useState, useEffect } from "react";
import axios from "axios";
import ReactPaginate from "react-paginate";
import { Link } from "react-router-dom";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import Swal from "sweetalert2";
import { useAlert } from "react-alert";

import Sidebar from "../../../Layouts/Sidebar";

export default function ChangeWorkerRequestList() {
    const [requests, setRequests] = useState("");
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState("Loading...");
    const [currentPage, setCurrentPage] = useState(0);

    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getRequests = () => {
        axios
            .get("/api/admin/jobs/change-worker-requests", {
                headers,
                params: {
                    page: currentPage,
                },
            })
            .then((response) => {
                if (response.data.data.data.length > 0) {
                    setRequests(response.data.data.data);
                    setPageCount(response.data.data.last_page);
                } else {
                    setRequests([]);
                    setPageCount(0);
                    setLoading("No service found");
                }
            });
    };

    useEffect(() => {
        getRequests();
    }, [currentPage]);

    const copy = [...requests];
    const [order, setOrder] = useState("ASC");
    const sortTable = (e, col) => {
        let n = e.target.nodeName;

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

        if (order == "ASC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? 1 : -1
            );
            setRequests(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setRequests(sortData);
            setOrder("ASC");
        }
    };

    const handleAcceptRequest = (_requestID) => {
        console.log("accept request");

        axios
            .post(
                `/api/admin/jobs/change-worker-requests/${_requestID}/accept`,
                {},
                { headers }
            )
            .then((response) => {
                alert.success("Request accepted successfully");
                getRequests();
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const handleRejectRequest = (_requestID) => {
        axios
            .post(
                `/api/admin/jobs/change-worker-requests/${_requestID}/reject`,
                {},
                { headers }
            )
            .then((response) => {
                alert.success("Request rejected successfully");
                getRequests();
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-12">
                            <h1 className="page-title">
                                Change Worker Requests
                            </h1>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {requests.length > 0 ? (
                                    <Table className="table table-bordered">
                                        <Thead>
                                            <Tr>
                                                <Th
                                                    scope="col"
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(e, "id");
                                                    }}
                                                >
                                                    Client
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th
                                                    scope="col"
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(e, "id");
                                                    }}
                                                >
                                                    Service
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th scope="col">Job</Th>
                                                <Th scope="col">Changes</Th>
                                                <Th
                                                    scope="col"
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(e, "status");
                                                    }}
                                                >
                                                    Status{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th scope="col">Action</Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {requests.map((item, index) => (
                                                <Tr key={index}>
                                                    <Td>{item.client_name}</Td>
                                                    <Td>{item.service_name}</Td>
                                                    <Td>
                                                        <div>
                                                            {item.worker_name}
                                                            <br />
                                                            {
                                                                item.current_start_date
                                                            }
                                                            <br />
                                                            {
                                                                item.current_shifts
                                                            }
                                                        </div>
                                                    </Td>
                                                    <Td>
                                                        <div>
                                                            {
                                                                item.new_worker_name
                                                            }
                                                            <br />
                                                            {item.date} <br />
                                                            {item.shifts}
                                                            <br />
                                                            {item.repeatancy}
                                                            {item.repeatancy ==
                                                                "until_date" && (
                                                                <>
                                                                    <br />
                                                                    {
                                                                        item.repeat_until_date
                                                                    }
                                                                </>
                                                            )}
                                                        </div>
                                                    </Td>
                                                    <Td>{item.status}</Td>
                                                    <Td>
                                                        {item.status ==
                                                            "pending" && (
                                                            <div className="action-dropdown dropdown pb-2">
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-default dropdown-toggle"
                                                                    data-toggle="dropdown"
                                                                >
                                                                    <i className="fa fa-ellipsis-vertical"></i>
                                                                </button>

                                                                <div className="dropdown-menu">
                                                                    <button
                                                                        className="dropdown-item"
                                                                        onClick={() => {
                                                                            handleAcceptRequest(
                                                                                item.id
                                                                            );
                                                                        }}
                                                                    >
                                                                        Accept
                                                                    </button>
                                                                    <button
                                                                        className="dropdown-item"
                                                                        onClick={() => {
                                                                            handleRejectRequest(
                                                                                item.id
                                                                            );
                                                                        }}
                                                                    >
                                                                        Reject
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        )}
                                                    </Td>
                                                </Tr>
                                            ))}
                                        </Tbody>
                                    </Table>
                                ) : (
                                    <p className="text-center mt-5">
                                        {loading}
                                    </p>
                                )}
                                {requests.length > 0 && (
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
            </div>
        </div>
    );
}
