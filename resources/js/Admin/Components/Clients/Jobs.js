import axios from "axios";
import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { useParams } from "react-router-dom";
import Moment from "moment";
import ReactPaginate from "react-paginate";
import { RotatingLines } from "react-loader-spinner";
import { useAlert } from "react-alert";

export default function Jobs({ contracts, client }) {
    const [jobs, setJobs] = useState([]);
    const [loading, setLoading] = useState("Loading...");
    const [jres, setJres] = useState("");

    const [filtered, setFiltered] = useState("");
    const [pageCount, setPageCount] = useState(0);
    const params = useParams();
    const [wait, setWait] = useState(true);
    const alert = useAlert();

    const [AllFreq, setAllFreq] = useState([]);
    const [service, setService] = useState([]);
    const [workers, setWorkers] = useState([]);
    const [cshift, setCshift] = useState({
        contract: "",
        client: "",
        repetency: "",
        job: "",
        from: "",
        to: "",
        worker: "",
        service: "",
        shift_date: "",
        frequency: "",
        cycle: "",
        period: "",
        shift_time: "",
    });

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

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Job!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/jobs/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Job has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getJobs();
                        }, 1000);
                    });
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

    const genOrder = () => {
        let cb = document.querySelectorAll(".cb");
        let ar = [];
        cb.forEach((c, i) => {
            if (c.checked == true) {
                ar.push(c.value);
            }
        });
        if (ar.length == 0) {
            alert.error("Please check job");
            return;
        }

        axios
            .post(`/api/admin/multiple-orders`, { ar }, { headers })
            .then((res) => {
                getJobs(filtered);
                alert.success("Job Order(s) created successfully");
            });
    };

    const genInvoice = () => {
        let cb = document.querySelectorAll(".cb");
        let ar = [];
        cb.forEach((c, i) => {
            if (c.checked == true) {
                let id = c.getAttribute("oid");
                if (id != "") ar.push(id);
            }
        });
        if (ar.length == 0) {
            alert.error("Please check job");
            return;
        }

        axios
            .post(`/api/admin/multiple-invoices`, { ar }, { headers })
            .then((res) => {
                getJobs(filtered);
                alert.success("Job Invoice(s) created successfully");
            });
    };

    const slot = [
        ["fullday-8am-16pm"],
        ["morning1-8am-9am"],
        ["morning2-9am-10am"],
        ["morning3-10am-11am"],
        ["morning4-11am-12pm"],
        ["morning-8am-12pm"],
        ["afternoon1-12pm-13pm"],
        ["afternoon2-13pm-14pm"],
        ["afternoon3-14pm-15pm"],
        ["afternoon4-15pm-16pm"],
        ["afternoon-12pm-16pm"],
        ["evening1-16pm-17pm"],
        ["evening2-17pm-18pm"],
        ["evening3-18pm-19pm"],
        ["evening4-19pm-20pm"],
        ["evening-16pm-20pm"],
        ["night1-20pm-21pm"],
        ["night2-21pm-22pm"],
        ["night3-22pm-23pm"],
        ["night4-23pm-24am"],
        ["night-20pm-24am"],
    ];

    const getFrequency = (lng) => {
        axios
            .post("/api/admin/all-service-schedule", { lng }, { headers })
            .then((res) => {
                setAllFreq(res.data.schedules);
            });
    };

    const shiftChange = (e) => {
        $("#edit-shift").modal("show");
    };

    const resetShift = () => {
        setCshift({
            contract: "",
            client: "",
            repetency: "",
            job: "",
            from: "",
            to: "",
            worker: "",
            service: "",
            shift_date: "",
            frequency: "",
            cycle: "",
            period: "",
            shift_time: "",
        });
    };

    const handleShift = (e) => {
        let newvalues = { ...cshift };

        if (e.target.name == "job" && e.target.value) {
            let j = e.target.options[e.target.selectedIndex];

            newvalues["contract"] = j.getAttribute("contract");
            newvalues["service"] = j.getAttribute("schedule_id");
            newvalues["client"] = j.getAttribute("client");
            //getWorker( j.getAttribute('schedule_id') );
        }

        if (e.target.name == "shift_date" || e.target.name == "shift_time") {
            getWorker(cshift.service);
        }

        // if (e.target.name == 'contract' && e.target.value) {

        //     setService(JSON.parse(contracts.find((c) => c.id == e.target.value).offer.services));
        // }
        if (e.target.name == "repetency" && e.target.value != "one_time") {
            getFrequency(client.lng);
        }

        if (e.target.name == "frequency") {
            newvalues["cycle"] =
                e.target.options[e.target.selectedIndex].getAttribute("cycle");
            newvalues["period"] =
                e.target.options[e.target.selectedIndex].getAttribute("period");
        }
        newvalues[e.target.name] = e.target.value;
        console.log(newvalues);
        setCshift(newvalues);
    };

    const getWorker = (sid) => {
        axios
            .get(`/api/admin/shift-change-worker/${sid}/${cshift.shift_date}`, {
                headers,
            })
            .then((res) => {
                setWorkers(res.data.data);
            });
    };

    const isEmptyOrSpaces = (str) => {
        return str === null || str.match(/^ *$/) !== null;
    };

    const changeShift = (e) => {
        e.preventDefault();

        if (isEmptyOrSpaces(cshift.job)) {
            window.alert("Please select job");
            return;
        }
        if (isEmptyOrSpaces(cshift.shift_date)) {
            window.alert("Please choose new shift date");
            return;
        }
        if (isEmptyOrSpaces(cshift.shift_time)) {
            window.alert("Please choose new shift time");
            return;
        }

        if (isEmptyOrSpaces(cshift.repetency)) {
            window.alert("Please select repetency");
            return;
        }

        if (
            cshift.repetency == "untill_date" &&
            (isEmptyOrSpaces(cshift.from) || isEmptyOrSpaces(cshift.to))
        ) {
            window.alert("Please select From and To date");
            return;
        }

        if (
            cshift.repetency == "forever" &&
            isEmptyOrSpaces(cshift.frequency)
        ) {
            window.alert("Please select frequency");
            return;
        }

        axios
            .post(`/api/admin/update-shift`, { cshift }, { headers })
            .then((res) => {
                getJobs("f=all");
                resetShift();
                $("#edit-shift").modal("hide");
                alert.success(res.data.success);
            });
    };

    return (
        <div className="boxPanel">
            <div className="action-dropdown dropdown order_drop text-right mb-3">
                <button
                    className="btn btn-info mr-3"
                    onClick={(e) => shiftChange(e)}
                >
                    Shift Change
                </button>
                <button
                    className="btn btn-pink mr-3"
                    onClick={(e) => genOrder(e)}
                >
                    Generate Orders
                </button>
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
                                <th>Service Name</th>
                                <th>Worker Name</th>
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
                                                        j.order.length > 0
                                                            ? j.order[0].id
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

                                                {j.order &&
                                                    j.order.map((o, i) => {
                                                        return (
                                                            <React.Fragment
                                                                key={i}
                                                            >
                                                                {" "}
                                                                <br />
                                                                <Link
                                                                    target="_blank"
                                                                    to={
                                                                        o.doc_url
                                                                    }
                                                                    className="jorder"
                                                                >
                                                                    {" "}
                                                                    order -
                                                                    {
                                                                        o.order_id
                                                                    }{" "}
                                                                </Link>
                                                                <br />
                                                            </React.Fragment>
                                                        );
                                                    })}

                                                {j.invoice &&
                                                    j.invoice.map((inv, i) => {
                                                        if (i == 0) {
                                                            pstatus =
                                                                inv.status;
                                                        }

                                                        return (
                                                            <React.Fragment
                                                                key={i}
                                                            >
                                                                {" "}
                                                                <br />
                                                                <Link
                                                                    target="_blank"
                                                                    to={
                                                                        inv.doc_url
                                                                    }
                                                                    className="jinv"
                                                                >
                                                                    {" "}
                                                                    Invoice -
                                                                    {
                                                                        inv.invoice_id
                                                                    }{" "}
                                                                </Link>
                                                                <br />
                                                            </React.Fragment>
                                                        );
                                                    })}

                                                {pstatus != null && (
                                                    <>
                                                        {" "}
                                                        <br />
                                                        <span className="jorder">
                                                            {pstatus}
                                                        </span>
                                                        <br />
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
                                                        {!j.worker && (
                                                            <Link
                                                                to={`/admin/create-job/${j.contract_id}`}
                                                                className="dropdown-item"
                                                            >
                                                                Create Job
                                                            </Link>
                                                        )}
                                                        <Link
                                                            to={`/admin/view-job/${j.id}`}
                                                            className="dropdown-item"
                                                        >
                                                            View Job
                                                        </Link>

                                                        <Link
                                                            to={`/admin/add-order/?j=${j.id}&c=${params.id}`}
                                                            className="dropdown-item"
                                                        >
                                                            Create Order
                                                        </Link>
                                                        <Link
                                                            to={`/admin/add-invoice/?j=${j.id}&c=${params.id}`}
                                                            className="dropdown-item"
                                                        >
                                                            Create Invoice
                                                        </Link>

                                                        <button
                                                            className="dropdown-item"
                                                            onClick={() =>
                                                                handleDelete(
                                                                    j.id
                                                                )
                                                            }
                                                        >
                                                            Delete
                                                        </button>
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

                {jobs.length > 0 ? (
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
                    ""
                )}
            </div>

            <div
                className="modal fade"
                id="edit-shift"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">
                                Change Shift
                            </h5>
                            <button
                                type="button"
                                className="close"
                                data-dismiss="modal"
                                aria-label="Close"
                                onClick={(e) => resetShift()}
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="col-sm-12">
                                    {/* <label className="control-label">
                                        Contract
                                    </label>
                                    <select name="contract" value={cshift.contract} onChange={(e) => {
                                        handleShift(e);
                                    }}
                                        className="form-control mb-3">
                                        <option value="">--- Please Select contract ---</option>
                                        {contracts &&
                                            contracts.map((item, index) => {

                                                return (
                                                    <option value={item.id}> # {item.id} |  {Moment(item.created_at).format('DD MMM, Y')}  </option>
                                                )

                                            }
                                            )}
                                    </select> */}

                                    <label className="control-label">Job</label>

                                    <select
                                        className="form-control mb-3"
                                        name="job"
                                        value={cshift.job}
                                        onChange={(e) => handleShift(e)}
                                    >
                                        <option value="">
                                            {" "}
                                            --- Please select Job ---
                                        </option>
                                        {jobs &&
                                            jobs.map((j, i) => {
                                                return (
                                                    <option
                                                        contract={j.contract_id}
                                                        client={j.client_id}
                                                        value={j.id}
                                                        schedule_id={
                                                            j.schedule_id
                                                        }
                                                        key={i}
                                                    >
                                                        #{j.id} |{" "}
                                                        {Moment(
                                                            j.start_date
                                                        ).format("DD MMM, Y")}
                                                    </option>
                                                );
                                            })}
                                    </select>

                                    <label className="control-label">
                                        New Shift date
                                    </label>

                                    <input
                                        className="form-control mb-3"
                                        name="shift_date"
                                        type="date"
                                        value={cshift.shift_date}
                                        onChange={(e) => handleShift(e)}
                                    />

                                    <label className="control-label">
                                        New Shift time
                                    </label>

                                    <select
                                        className="form-control mb-3"
                                        name="shift_time"
                                        value={cshift.shift_time}
                                        onChange={(e) => handleShift(e)}
                                    >
                                        <option value="">
                                            {" "}
                                            --- Please select new shift time ---
                                        </option>
                                        {slot?.map((s, i) => {
                                            return (
                                                <option value={s} key={i}>
                                                    {s}
                                                </option>
                                            );
                                        })}
                                    </select>

                                    {/*
                                        cshift.contract != '' &&
                                        <>
                                            <label className="control-label">
                                                Service
                                            </label>
                                            <select name="service" value={cshift.service} onChange={(e) => { handleShift(e); getWorker(e) }
                                            } className="form-control mb-3">
                                                <option value="">--- Please Select service ---</option>
                                                {service &&
                                                    service.map((item, index) => {

                                                        return (
                                                            <option value={item.service} > {item.name} | {item.freq_name}   </option>
                                                        )

                                                    }
                                                    )}
                                            </select>
                                        </>
                                    */}

                                    {cshift.job != "" && (
                                        <>
                                            <label className="control-label">
                                                Worker
                                            </label>

                                            <select
                                                className="form-control mb-3"
                                                name="worker"
                                                value={cshift.worker}
                                                onChange={(e) => handleShift(e)}
                                            >
                                                <option value="">
                                                    {" "}
                                                    --- Please select available
                                                    workers ---
                                                </option>
                                                {workers &&
                                                    workers.map(
                                                        (item, index) => {
                                                            return (
                                                                <option
                                                                    value={
                                                                        item.id
                                                                    }
                                                                    key={index}
                                                                >
                                                                    {" "}
                                                                    {
                                                                        item.firstname
                                                                    }{" "}
                                                                    {
                                                                        item.lastname
                                                                    }{" "}
                                                                </option>
                                                            );
                                                        }
                                                    )}
                                            </select>
                                        </>
                                    )}

                                    <label className="control-label">
                                        Repetnacy
                                    </label>

                                    <select
                                        name="repetency"
                                        onChange={(e) => handleShift(e)}
                                        value={cshift.repetency}
                                        className="form-control mb-3"
                                    >
                                        <option value="">
                                            {" "}
                                            --- Please select repetnacy ---
                                        </option>
                                        <option value="one_time">
                                            {" "}
                                            One Time ( for single job )
                                        </option>
                                        <option value="forever">
                                            {" "}
                                            Forever{" "}
                                        </option>
                                        <option value="untill_date">
                                            {" "}
                                            Until Date{" "}
                                        </option>
                                    </select>

                                    {/*cshift.repetency == 'one_time' &&

                                        <>
                                            <label className="control-label">
                                                Job
                                            </label>

                                            <select className='form-control mb-3'
                                                name="job"
                                                value={cshift.job}
                                                onChange={e => handleShift(e)}
                                            >
                                                <option value=""> --- Please select Job --- </option>
                                                {jobs && jobs.map((j) => {
                                                    return <option value={j.id}> #{j.id} | {Moment(j.start_date).format('DD MMM, Y')}  </option>
                                                })}

                                            </select>
                                        </>
                                   */}

                                    {cshift.repetency &&
                                        cshift.repetency != "one_time" && (
                                            <>
                                                <label className="control-label">
                                                    New Frequency
                                                </label>

                                                <select
                                                    name="frequency"
                                                    className="form-control mb-3"
                                                    value={
                                                        cshift.frequency || ""
                                                    }
                                                    onChange={(e) =>
                                                        handleShift(e)
                                                    }
                                                >
                                                    <option value="">
                                                        {" "}
                                                        -- Please select
                                                        frequency --
                                                    </option>
                                                    {AllFreq &&
                                                        AllFreq.map((s, i) => {
                                                            return (
                                                                <option
                                                                    cycle={
                                                                        s.cycle
                                                                    }
                                                                    period={
                                                                        s.period
                                                                    }
                                                                    name={
                                                                        s.name
                                                                    }
                                                                    value={s.id}
                                                                    key={i}
                                                                >
                                                                    {" "}
                                                                    {
                                                                        s.name
                                                                    }{" "}
                                                                </option>
                                                            );
                                                        })}
                                                </select>
                                            </>
                                        )}

                                    {cshift.repetency == "untill_date" && (
                                        <>
                                            <label className="control-label">
                                                From
                                            </label>

                                            <input
                                                className="form-control mb-3"
                                                type="date"
                                                placeholder="From date"
                                                name="from"
                                                value={cshift.from}
                                                onChange={(e) => handleShift(e)}
                                            />

                                            <label className="control-label">
                                                To
                                            </label>

                                            <input
                                                className="form-control mb-3"
                                                type="date"
                                                placeholder="To date"
                                                name="to"
                                                value={cshift.to}
                                                onChange={(e) => handleShift(e)}
                                            />
                                        </>
                                    )}

                                    <button
                                        className="btn btn-success form-control"
                                        onClick={(e) => changeShift(e)}
                                    >
                                        {" "}
                                        Change Shift{" "}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
