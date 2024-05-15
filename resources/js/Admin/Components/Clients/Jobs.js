import axios from "axios";
import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { useParams } from "react-router-dom";
import Moment from "moment";
import ReactPaginate from "react-paginate";
import { RotatingLines } from "react-loader-spinner";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";

export default function Jobs({ contracts, client }) {
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

    const [jobs, setJobs] = useState([]);
    const [loading, setLoading] = useState("Loading...");
    const [jres, setJres] = useState("");
    const [pageCount, setPageCount] = useState(0);
    const params = useParams();
    const [wait, setWait] = useState(true);
    const [currentPage, setCurrentPage] = useState(0);
    const [dateRange, setDateRange] = useState({
        start_date: currentWeekFilter.start_date,
        end_date: currentWeekFilter.end_date,
    });
    const [filters, setFilters] = useState({
        status: "",
        q: "",
    });
    const [selectedFilter, setselectedFilter] = useState("Week");

    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getJobs = () => {
        let _filters = {};

        if (filters.status) {
            _filters.status = filters.status;
        }

        if (filters.q) {
            _filters.q = filters.q;
        }

        _filters.start_date = dateRange.start_date;
        _filters.end_date = dateRange.end_date;

        axios
            .get(`/api/admin/clients/${params.id}/jobs`, {
                headers,
                params: {
                    page: currentPage,
                    ..._filters,
                },
            })
            .then((res) => {
                setWait(true);
                setJres(res.data);
                if (res.data.jobs.data.length > 0) {
                    setJobs(res.data.jobs.data);
                    setWait(false);
                    setPageCount(res.data.jobs.last_page);
                } else {
                    setJobs([]);
                    setWait(false);
                    setLoading("No job found");
                }
            });
    };

    useEffect(() => {
        getJobs();
    }, [currentPage, dateRange, filters]);

    const copy = [...jobs];
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
            setJobs(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setJobs(sortData);
            setOrder("ASC");
        }
    };

    const checkAllBox = (e) => {
        let cb = document.querySelectorAll(".cb");

        if (e.target.checked) {
            cb.forEach((c, i) => {
                c.checked = true;
            });
        } else {
            cb.forEach((c, i) => {
                c.checked = false;
            });
        }
    };

    // const genOrder = () => {
    //     let cb = document.querySelectorAll(".cb");
    //     let job_id_arr = [];
    //     cb.forEach((c, i) => {
    //         if (c.checked == true) {
    //             job_id_arr.push(c.value);
    //         }
    //     });
    //     if (job_id_arr.length == 0) {
    //         alert.error("Please check job");
    //         return;
    //     }

    //     axios
    //         .post(`/api/admin/multiple-orders`, job_id_arr, { headers })
    //         .then((res) => {
    //             getJobs();
    //             alert.success("Job Order(s) created successfully");
    //         })
    //         .catch((e) => {
    //             Swal.fire({
    //                 title: "Error!",
    //                 text: e.response.data.message,
    //                 icon: "error",
    //             });
    //         });
    // };

    const genInvoice = () => {
        let cb = document.querySelectorAll(".cb");
        let order_id_arr = [];
        cb.forEach((c, i) => {
            if (c.checked == true) {
                let id = c.getAttribute("oid");
                if (id != "") order_id_arr.push(id);
            }
        });
        if (order_id_arr.length == 0) {
            alert.error("Please check job");
            return;
        }

        axios
            .post(`/api/admin/multiple-invoices`, order_id_arr, { headers })
            .then((res) => {
                getJobs();
                alert.success("Job Invoice(s) created successfully");
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
        <div className="boxPanel">
            <div className="col-md-12 hidden-xs d-sm-flex justify-content-between mt-2">
                <div className="d-flex align-items-center">
                    <div style={{ fontWeight: "bold" }} className="mr-2">
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
                                start_date: currentWeekFilter.start_date,
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
                                start_date: previousWeekFilter.start_date,
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
                    <div className="mr-3" style={{ fontWeight: "bold" }}>
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
                </div>
            </div>

            <div className="action-dropdown dropdown order_drop text-right mb-3">
                {/* <button
                    className="btn btn-pink mr-3"
                    onClick={(e) => genOrder(e)}
                >
                    Generate Orders
                </button> */}
                <button
                    className="btn btn-primary mr-3 ml-3"
                    onClick={(e) => genInvoice(e)}
                >
                    Generate Invoice
                </button>
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
                                status: "",
                                q: "",
                            });
                        }}
                    >
                        All - {jres.all}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFilters({
                                status: "scheduled",
                                q: "",
                            });
                        }}
                    >
                        Scheduled - {jres.scheduled}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFilters({
                                status: "unscheduled",
                                q: "",
                            });
                        }}
                    >
                        Unscheduled - {jres.unscheduled}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFilters({
                                status: "progress",
                                q: "",
                            });
                        }}
                    >
                        Progress - {jres.progress}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFilters({
                                status: "completed",
                                q: "",
                            });
                        }}
                    >
                        completed - {jres.completed}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFilters({
                                status: "canceled",
                                q: "",
                            });
                        }}
                    >
                        Canceled - {jres.canceled}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFilters({
                                status: "",
                                q: "ordered",
                            });
                        }}
                    >
                        Ordered - {jres.ordered}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFilters({
                                status: "",
                                q: "unordered",
                            });
                        }}
                    >
                        unordered - {jres.unordered}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFilters({
                                status: "",
                                q: "invoiced",
                            });
                        }}
                    >
                        Invoiced - {jres.invoiced}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFilters({
                                status: "",
                                q: "uninvoiced",
                            });
                        }}
                    >
                        UnInvoiced - {jres.uninvoiced}
                    </button>
                </div>
            </div>
            <div className="table-responsive text-center">
                <RotatingLines
                    strokeColor="grey"
                    strokeWidth="5"
                    animationDuration="0.75"
                    width="96"
                    visible={wait}
                />

                {wait == false && jobs.length > 0 ? (
                    <table className="table table-bordered">
                        <thead>
                            <tr>
                                <th>
                                    <input
                                        type="checkbox"
                                        name="cbh"
                                        onClick={(e) => checkAllBox(e)}
                                        className="form-control cbh"
                                    />
                                </th>
                                <th
                                    onClick={(e) => sortTable(e, "id")}
                                    style={{ cursor: "pointer" }}
                                >
                                    ID <span className="arr"> &darr; </span>
                                </th>
                                <th>Service</th>
                                <th>Worker</th>
                                <th>Total Price</th>
                                <th
                                    onClick={(e) => sortTable(e, "created_at")}
                                    style={{ cursor: "pointer" }}
                                >
                                    Start Date{" "}
                                    <span className="arr"> &darr; </span>
                                </th>
                                <th
                                    onClick={(e) => sortTable(e, "status")}
                                    style={{ cursor: "pointer" }}
                                >
                                    Status <span className="arr"> &darr; </span>
                                </th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {jobs.map((j, i) => {
                                return (
                                    <tr key={i}>
                                        <td>
                                            <input
                                                type="checkbox"
                                                name="cb"
                                                value={j.id}
                                                oid={j.order ? j.order.id : ""}
                                                className="form-control cb"
                                            />
                                        </td>
                                        <td>#{j.id}</td>
                                        <td>
                                            {j.jobservice && j.jobservice.name
                                                ? j.jobservice.name + " "
                                                : "NA"}
                                        </td>
                                        <td>
                                            {j.worker
                                                ? j.worker.firstname +
                                                  " " +
                                                  j.worker.lastname
                                                : "NA"}
                                        </td>
                                        <td>
                                            {" "}
                                            {j.jobservice &&
                                                j.jobservice.total +
                                                    " ILS + VAT"}
                                        </td>
                                        <td>
                                            {Moment(j.start_date).format(
                                                "DD MMM, Y"
                                            )}
                                        </td>
                                        <td>
                                            {j.status}

                                            {j.order && (
                                                <React.Fragment>
                                                    <br />
                                                    <Link
                                                        target="_blank"
                                                        to={j.order.doc_url}
                                                        className="jorder"
                                                    >
                                                        order -
                                                        {j.order.order_id}
                                                    </Link>
                                                </React.Fragment>
                                            )}

                                            {j.invoice && (
                                                <React.Fragment>
                                                    <br />
                                                    <Link
                                                        target="_blank"
                                                        to={j.invoice.doc_url}
                                                        className="jinv"
                                                    >
                                                        Invoice -
                                                        {j.invoice.invoice_id}
                                                    </Link>
                                                </React.Fragment>
                                            )}

                                            {j.invoice && (
                                                <>
                                                    {" "}
                                                    <br />
                                                    <span className="jorder">
                                                        {j.invoice.status}
                                                    </span>
                                                </>
                                            )}
                                        </td>
                                        <td className="text-center">
                                            <div className="action-dropdown dropdown pb-2">
                                                <button
                                                    type="button"
                                                    className="btn btn-default dropdown-toggle"
                                                    data-toggle="dropdown"
                                                >
                                                    <i className="fa fa-ellipsis-vertical"></i>
                                                </button>

                                                <div className="dropdown-menu">
                                                    {/* {!j.worker && (
                                                            <Link
                                                                to={`/admin/create-job/${j.contract_id}`}
                                                                className="dropdown-item"
                                                            >
                                                                Create Job
                                                            </Link>
                                                        )} */}
                                                    <Link
                                                        to={`/admin/view-job/${j.id}`}
                                                        className="dropdown-item"
                                                    >
                                                        View Job
                                                    </Link>

                                                    {!j.is_order_generated && (
                                                        <Link
                                                            to={`/admin/add-order/?j=${j.id}&c=${params.id}`}
                                                            className="dropdown-item"
                                                        >
                                                            Create Order
                                                        </Link>
                                                    )}
                                                    {/* {!j.is_invoice_generated && (
                                                            <Link
                                                                to={`/admin/add-invoice/?j=${j.id}&c=${params.id}`}
                                                                className="dropdown-item"
                                                            >
                                                                Create Invoice
                                                            </Link>
                                                        )} */}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                ) : (
                    <div className="form-control text-center">{loading}</div>
                )}

                {jobs.length > 0 && (
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
    );
}

const FilterButtons = ({
    text,
    className,
    selectedFilter,
    setselectedFilter,
    onClick,
}) => (
    <button
        className={`btn border rounded ${className}`}
        style={
            selectedFilter === text
                ? { background: "white" }
                : {
                      background: "#2c3f51",
                      color: "white",
                  }
        }
        onClick={() => {
            onClick?.();
            setselectedFilter(text);
        }}
    >
        {text}
    </button>
);
