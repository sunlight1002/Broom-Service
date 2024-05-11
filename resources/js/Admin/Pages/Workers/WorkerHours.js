import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import ReactPaginate from "react-paginate";
import axios from "axios";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";

import Sidebar from "../../Layouts/Sidebar";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";

export default function WorkerHours() {
    const [workers, setWorkers] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState("Loading...");
    const [searchVal, setSearchVal] = useState("");
    const [currentPage, setCurrentPage] = useState(0);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getWorkers = () => {
        let _filters = {};

        if (searchVal) {
            _filters.keyword = searchVal;
        }

        axios
            .get("/api/admin/workers/working-hours", {
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

    const handlePageClick = async (data) => {
        setCurrentPage(currentPage + 1);
    };

    useEffect(() => {
        getWorkers();
    }, [searchVal]);

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
                        <div className="col-sm-6">
                            <h1 className="page-title">Worker Hours</h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <input
                                    type="text"
                                    className="form-control"
                                    onChange={(e) => {
                                        setSearchVal(e.target.value);
                                    }}
                                    placeholder="Search"
                                />
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
                <div className="card">
                    <div className="card-body">
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
                                                <Th>Hours</Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {workers.map((item, index) => {
                                                return (
                                                    <Tr
                                                        style={{
                                                            cursor: "pointer",
                                                        }}
                                                        key={index}
                                                    >
                                                        <Td>{item.id}</Td>
                                                        <Td>
                                                            <Link
                                                                to={`/admin/view-worker/${item.id}`}
                                                            >
                                                                {item.firstname}{" "}
                                                                {item.lastname}
                                                            </Link>
                                                        </Td>
                                                        <Td>{item.email}</Td>
                                                        <Td>
                                                            {item.minutes
                                                                ? convertMinsToDecimalHrs(
                                                                      item.minutes
                                                                  )
                                                                : "NA"}
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
                                        onPageChange={handlePageClick}
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
