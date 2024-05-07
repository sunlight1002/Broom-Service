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
    const [jobs, setJobs] = useState([]);
    const [loading, setLoading] = useState("Loading...");
    const [jres, setJres] = useState("");

    const [filtered, setFiltered] = useState("");
    const [pageCount, setPageCount] = useState(0);
    const params = useParams();
    const [wait, setWait] = useState(true);
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getJobs = (filter) => {
        axios
            .post(
                `/api/admin/clients/${params.id}/jobs?` + filter,
                {},
                { headers }
            )
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

    const handlePageClick = async (data) => {
        let currentPage = data.selected + 1;
        axios
            .post(
                `/api/admin/clients/${params.id}/jobs?` +
                    filtered +
                    "&page=" +
                    currentPage,
                {},
                { headers }
            )
            .then((response) => {
                if (response.data.jobs.data.length > 0) {
                    setJobs(response.data.jobs.data);
                    setPageCount(response.data.jobs.last_page);
                } else {
                    setJobs([]);
                    setLoading("No Job Found");
                }
            });
    };

    useEffect(() => {
        setFiltered("f=all");
        getJobs("f=all");
    }, []);

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
    //             getJobs(filtered);
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
                getJobs(filtered);
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
                            setFiltered("f=all");
                            getJobs("f=all");
                        }}
                    >
                        All - {jres.all}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFiltered("status=scheduled");
                            getJobs("status=scheduled");
                        }}
                    >
                        Scheduled - {jres.scheduled}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFiltered("status=unscheduled");
                            getJobs("status=unscheduled");
                        }}
                    >
                        Unscheduled - {jres.unscheduled}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFiltered("status=progress");
                            getJobs("status=progress");
                        }}
                    >
                        Progress - {jres.progress}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFiltered("status=completed");
                            getJobs("status=completed");
                        }}
                    >
                        completed - {jres.completed}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFiltered("status=canceled");
                            getJobs("status=canceled");
                        }}
                    >
                        Canceled - {jres.canceled}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFiltered("q=ordered");
                            getJobs("q=ordered");
                        }}
                    >
                        Ordered - {jres.ordered}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFiltered("q=unordered");
                            getJobs("q=unordered");
                        }}
                    >
                        unordered - {jres.unordered}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFiltered("q=invoiced");
                            getJobs("q=invoiced");
                        }}
                    >
                        Invoiced - {jres.invoiced}
                    </button>
                    <button
                        className="dropdown-item"
                        onClick={(e) => {
                            setFiltered("q=uninvoiced");
                            getJobs("q=uninvoiced");
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
                            {jobs &&
                                jobs.map((j, i) => {
                                    // let services = (j.offer.services) ? JSON.parse(j.offer.services) : [];
                                    let pstatus = null;

                                    return (
                                        <tr key={i}>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="cb"
                                                    value={j.id}
                                                    oid={
                                                        j.order
                                                            ? j.order.id
                                                            : ""
                                                    }
                                                    className="form-control cb"
                                                />
                                            </td>
                                            <td>#{j.id}</td>
                                            <td>
                                                {j.jobservice &&
                                                j.jobservice.name
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
                                                            to={
                                                                j.invoice
                                                                    .doc_url
                                                            }
                                                            className="jinv"
                                                        >
                                                            Invoice -
                                                            {
                                                                j.invoice
                                                                    .invoice_id
                                                            }
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
    );
}
