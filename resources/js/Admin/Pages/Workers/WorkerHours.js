import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import ReactPaginate from "react-paginate";
import axios from "axios";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import Moment from "moment";
import { CSVLink } from "react-csv";

import Sidebar from "../../Layouts/Sidebar";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";
import FilterButtons from "../../../Components/common/FilterButton";
import { useAlert } from "react-alert";

export default function WorkerHours() {
    const alert = useAlert();
    const todayFilter = {
        start_date: Moment().format("YYYY-MM-DD"),
        end_date: Moment().format("YYYY-MM-DD"),
    };
    const currentWeekFilter = {
        start_date: Moment().startOf("week").format("YYYY-MM-DD"),
        end_date: Moment().endOf("week").format("YYYY-MM-DD"),
    };
    const nextWeekFilter = {
        start_date: Moment()
            .add(1, "weeks")
            .startOf("week")
            .format("YYYY-MM-DD"),
        end_date: Moment().add(1, "weeks").endOf("week").format("YYYY-MM-DD"),
    };
    const previousWeekFilter = {
        start_date: Moment()
            .subtract(1, "weeks")
            .startOf("week")
            .format("YYYY-MM-DD"),
        end_date: Moment()
            .subtract(1, "weeks")
            .endOf("week")
            .format("YYYY-MM-DD"),
    };

    const [workers, setWorkers] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [loading, setLoading] = useState("Loading...");
    const [searchVal, setSearchVal] = useState("");
    const [currentPage, setCurrentPage] = useState(0);
    const [dateRange, setDateRange] = useState({
        start_date: currentWeekFilter.start_date,
        end_date: currentWeekFilter.end_date,
    });
    const [selectedFilter, setselectedFilter] = useState("Week");
    const [exportData, setExportData] = useState([]);
    const [filters, setFilters] = useState({
        manpower_company_id: null,
    });
    const [manpowerCompanies, setManpowerCompanies] = useState([]);

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

        if (filters.manpower_company_id) {
            _filters.manpower_company_id = filters.manpower_company_id;
        }

        _filters.start_date = dateRange.start_date;
        _filters.end_date = dateRange.end_date;

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

    const handleExport = async () => {
        let _filters = {};

        if (searchVal) {
            _filters.keyword = searchVal;
        }

        if (filters.manpower_company_id) {
            _filters.manpower_company_id = filters.manpower_company_id;
        }

        _filters.start_date = dateRange.start_date;
        _filters.end_date = dateRange.end_date;

        await axios
            .get("/api/admin/workers/working-hours/export", {
                headers,
                params: {
                    page: currentPage,
                    ..._filters,
                },
            })
            .then((response) => {
                if (
                    response.data &&
                    response.data.workers &&
                    response.data.workers.length > 0
                ) {
                    const mappedData = response.data.workers.map((w) => {
                        return {
                            "Start Date": w.start_date,
                            [w.worker_name]: w.time
                                ? convertMinsToDecimalHrs(w.time)
                                : 0.0,
                        };
                    });
                    setExportData(mappedData);
                    document.querySelector("#csv").click();
                } else {
                    alert.error("Worker data not found!");
                }
            });
    };

    useEffect(() => {
        getWorkers();
    }, [currentPage, searchVal, dateRange, filters]);

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

    const header = [
        { label: "Worker Name", key: "worker_name" },
        { label: "Worker ID", key: "worker_id" },
        { label: "Hours", key: "hours" },
    ];

    const csvReport = {
        data: exportData,
        // headers: header,
        filename:
            "Worker Hours - (" +
            dateRange.start_date +
            " - " +
            dateRange.end_date +
            ")",
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
                <div className="row">
                    <div className="col-md-12 hidden-xs d-sm-flex justify-content-between mt-2">
                        <div className="d-flex align-items-center">
                            <div
                                style={{ fontWeight: "bold" }}
                                className="mr-2"
                            >
                                Date Period
                            </div>
                            <FilterButtons
                                text="Day"
                                className="px-4 mr-1"
                                onClick={() =>
                                    setDateRange({
                                        start_date: todayFilter.start_date,
                                        end_date: todayFilter.end_date,
                                    })
                                }
                                selectedFilter={selectedFilter}
                                setselectedFilter={setselectedFilter}
                            />
                            <FilterButtons
                                text="Week"
                                className="px-4 mr-3"
                                onClick={() =>
                                    setDateRange({
                                        start_date:
                                            currentWeekFilter.start_date,
                                        end_date: currentWeekFilter.end_date,
                                    })
                                }
                                selectedFilter={selectedFilter}
                                setselectedFilter={setselectedFilter}
                            />
                            <FilterButtons
                                text="Previous Week"
                                className="px-3 mr-1"
                                onClick={() =>
                                    setDateRange({
                                        start_date:
                                            previousWeekFilter.start_date,
                                        end_date: previousWeekFilter.end_date,
                                    })
                                }
                                selectedFilter={selectedFilter}
                                setselectedFilter={setselectedFilter}
                            />
                            <FilterButtons
                                text="Next Week"
                                className="px-3"
                                onClick={() =>
                                    setDateRange({
                                        start_date: nextWeekFilter.start_date,
                                        end_date: nextWeekFilter.end_date,
                                    })
                                }
                                selectedFilter={selectedFilter}
                                setselectedFilter={setselectedFilter}
                            />
                        </div>
                    </div>
                    <div className="col-md-12 hidden-xs d-sm-flex justify-content-between my-2">
                        <div className="d-flex align-items-center">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                Custom Date Range
                            </div>

                            <input
                                className="form-control"
                                type="date"
                                placeholder="From date"
                                name="from filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.start_date}
                                onChange={(e) => {
                                    setselectedFilter("Custom Range");
                                    setDateRange({
                                        start_date: e.target.value,
                                        end_date: dateRange.end_date,
                                    });
                                }}
                            />
                            <div className="mx-2">to</div>
                            <input
                                className="form-control"
                                type="date"
                                placeholder="To date"
                                name="to filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.end_date}
                                onChange={(e) => {
                                    setselectedFilter("Custom Range");
                                    setDateRange({
                                        start_date: dateRange.start_date,
                                        end_date: e.target.value,
                                    });
                                }}
                            />
                            <button
                                type="button"
                                className="m-0 ml-4 btn border rounded px-3"
                                onClick={handleExport}
                                style={{
                                    background: "#2c3f51",
                                    color: "white",
                                }}
                            >
                                Export
                            </button>
                        </div>

                        <div className="App" style={{ display: "none" }}>
                            <CSVLink {...csvReport} id="csv">
                                Export to CSV
                            </CSVLink>
                        </div>
                    </div>
                    <div className="col-sm-12 hidden-xs d-sm-flex justify-content-between mt-2">
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
