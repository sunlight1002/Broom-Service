import React, { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import Sidebar from "../Layouts/Sidebar";
import ReactPaginate from "react-paginate";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import axios from "axios";
import Moment from "moment";

const thisMonthFilter = {
    start_date: Moment().startOf("month").format("YYYY-MM-DD"),
    end_date: Moment().endOf("month").format("YYYY-MM-DD"),
};

const todayFilter = {
    start_date: Moment().format("YYYY-MM-DD"),
    end_date: Moment().format("YYYY-MM-DD"),
};

const thisWeekFilter = {
    start_date: Moment().startOf("week").format("YYYY-MM-DD"),
    end_date: Moment().endOf("week").format("YYYY-MM-DD"),
};

export default function income() {
    const [tasks, setTasks] = useState([]);
    const [loading, setLoading] = useState("Loading...");
    const [totalTask, setTotalTask] = useState(0);
    const [income, setIncome] = useState(0);
    const [role, setRole] = useState();
    const navigate = useNavigate();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const [dateRange, setDateRange] = useState({
        start_date: null,
        end_date: null,
    });
    const getTasks = () => {
        axios
            .post("/api/admin/income", { dateRange }, { headers })
            .then((res) => {
                if (res.data.tasks.length > 0) {
                    setTasks(res.data.tasks);
                    setTotalTask(res.data.total_tasks);
                    setIncome(res.data.income);
                } else {
                    setTasks([]);
                    setLoading("No Completed Tasks found.");
                }
            });
    };
    function toHoursAndMinutes(totalSeconds) {
        const totalMinutes = Math.floor(totalSeconds / 60);
        const s = totalSeconds % 60;
        const h = Math.floor(totalMinutes / 60);
        const m = totalMinutes % 60;
        return decimalHours(h, m, s);
    }

    function decimalHours(h, m, s) {
        var hours = parseInt(h, 10);
        var minutes = m ? parseInt(m, 10) : 0;
        var min = minutes / 60;
        return hours + ":" + min.toString().substring(0, 4);
    }
    const getAdmin = () => {
        axios.get(`/api/admin/details`, { headers }).then((res) => {
            setRole(res.data.success.role);
        });
    };
    useEffect(() => {
        getTasks();
    }, [dateRange]);
    useEffect(() => {
        getTasks();
        getAdmin();
        if (role == "member") {
            navigate("/admin/dashboard");
        }
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox card card-body p-3 m-3">
                    <div className="row">
                        <div className="col-sm-6">
                            <h4 className="page-title">
                                Total Jobs : {totalTask}
                            </h4>
                            <h4 className="page-title">Income : {income}</h4>
                            <h4 className="page-title">Outcome : 0</h4>
                        </div>
                        <div className="col-sm-6">
                            <div
                                className="search-data"
                                style={{ cursor: "pointer" }}
                            >
                                <span
                                    className="p-2"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setDateRange({
                                            start_date: todayFilter.start_date,
                                            end_date: todayFilter.end_date,
                                        });
                                    }}
                                >
                                    Day
                                </span>
                                <span
                                    className="p-2"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setDateRange({
                                            start_date:
                                                thisWeekFilter.start_date,
                                            end_date: thisWeekFilter.end_date,
                                        });
                                    }}
                                >
                                    Week
                                </span>
                                <span
                                    className="p-2"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setDateRange({
                                            start_date:
                                                thisMonthFilter.start_date,
                                            end_date: thisMonthFilter.end_date,
                                        });
                                    }}
                                >
                                    Month
                                </span>
                                <span
                                    className="p-2"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setDateRange({
                                            start_date: null,
                                            end_date: null,
                                        });
                                    }}
                                >
                                    All
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-sm-12 d-flex align-items-center">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                Date Period
                            </div>
                            <input
                                className="form-control"
                                type="date"
                                placeholder="From date"
                                name="from filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.start_date}
                                onChange={(e) => {
                                    setDateRange({
                                        start_date: e.target.value,
                                        end_date: dateRange.end_date,
                                    });
                                }}
                            />
                            <div className="mx-2">to</div>
                            <input
                                className="form-control mr-2"
                                type="date"
                                placeholder="To date"
                                name="to filter"
                                style={{ width: "fit-content" }}
                                value={dateRange.end_date}
                                onChange={(e) => {
                                    setDateRange({
                                        start_date: dateRange.start_date,
                                        end_date: e.target.value,
                                    });
                                }}
                            />
                        </div>
                    </div>
                </div>
                <hr />
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            {tasks.length > 0 ? (
                                <Table className="table table-bordered">
                                    <Thead>
                                        <Tr style={{ cursor: "pointer" }}>
                                            <Th>ID</Th>
                                            <Th>Worker Name</Th>
                                            <Th>Client Name</Th>
                                            <Th>Time Takes</Th>
                                            <Th>Income</Th>
                                            <Th>Outcome</Th>
                                        </Tr>
                                    </Thead>
                                    <Tbody>
                                        {tasks &&
                                            tasks.map((item, index) => {
                                                let time =
                                                    item.total_sec != null
                                                        ? toHoursAndMinutes(
                                                              item.total_sec
                                                          )
                                                        : 0;
                                                return (
                                                    <Tr key={index}>
                                                        <Td>{item.id}</Td>
                                                        <Td>
                                                            {
                                                                item.worker
                                                                    .firstname
                                                            }{" "}
                                                            {
                                                                item.worker
                                                                    .lastname
                                                            }
                                                        </Td>
                                                        <Td>
                                                            {" "}
                                                            {
                                                                item.client
                                                                    .firstname
                                                            }{" "}
                                                            {
                                                                item.client
                                                                    .lastname
                                                            }
                                                        </Td>
                                                        <Td>{time}</Td>
                                                        <Td>
                                                            {item.offer
                                                                ? item.offer
                                                                      .subtotal +
                                                                  " ILS + VAT "
                                                                : ""}
                                                        </Td>
                                                        <Td>{0}</Td>
                                                    </Tr>
                                                );
                                            })}
                                    </Tbody>
                                </Table>
                            ) : (
                                <p className="text-center mt-5">{loading}</p>
                            )}
                            {/*clients.length > 0 ? (
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
                            ) : (
                                <></>
                            )*/}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
