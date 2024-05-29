import React, { useState, useEffect } from "react";
import ReactPaginate from "react-paginate";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { Link, useParams } from "react-router-dom";
import axios from "axios";
import Moment from "moment";
import { Base64 } from "js-base64";
import Swal from "sweetalert2";
import { render } from "react-dom";
import AceEditor from "react-ace";

import "ace-builds/src-noconflict/mode-java";
import "ace-builds/src-noconflict/theme-github";
import "ace-builds/src-noconflict/ext-language_tools";

import AddCreditCardModal from "../Modals/AddCreditCardModal";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function Invoice() {
    const [invoices, setInvoices] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [res, setRes] = useState("");
    const [payId, setPayID] = useState(0);
    const [amount, setAmount] = useState();
    const [txn, setTxn] = useState("");
    const [bt, setBt] = useState(true);
    const [ch, setCh] = useState(false);
    const [cancelDoc, setCancelDoc] = useState("");
    const [dtype, setDtype] = useState("");
    const [reason, setReason] = useState("");
    const [cbvalue, setCbvalue] = useState("");
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [addCardModalOpen, setAddCardModalOpen] = useState(false);
    const [currentPage, setCurrentPage] = useState(0);
    const [filters, setFilters] = useState({
        status: "",
        icount_status: "",
        type: "",
    });
    const [isLoading, setIsLoading] = useState(false);

    const params = useParams();
    const id = params.id;
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getInvoices = () => {
        let _filters = {};

        if (filters.type) {
            _filters.type = filters.type;
        }

        if (filters.status) {
            _filters.status = filters.status;
        }

        if (filters.icount_status) {
            _filters.icount_status = filters.icount_status;
        }

        axios
            .get(`/api/admin/client/${id}/invoices`, {
                headers,
                params: {
                    page: currentPage,
                    ..._filters,
                },
            })
            .then((res) => {
                setRes(res.data);
                if (res.data.invoices.data.length > 0) {
                    setInvoices(res.data.invoices.data);
                    setPageCount(res.data.invoices.last_page);
                } else {
                    setInvoices([]);
                }
            });
    };

    const copy = [...invoices];
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
            setInvoices(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setInvoices(sortData);
            setOrder("ASC");
        }
    };

    const handlePayment = () => {
        const m = document.querySelector(".mode").value;
        const pm = {
            cc: "Credit Card",
            mt: "Bank Transfer",
            cash: "Cash",
            cheque: "Cheque",
        };
        const mdata = {
            paid_amount: amount,
            pay_method: pm[m],
            txn_id: txn,
            status: "Paid",
        };

        let data = {};

        if (m == "mt") {
            const btd = document.querySelector(".btd").value;
            const ba = document.querySelector(".ba").value;
            if (btd == "") {
                window.alert("Please select bank transfer date");
                return;
            }
            if (ba == "") {
                window.alert("please enter bank account");
                return;
            }
            data = {
                ...mdata,
                date: btd,
                account: ba,
            };
        } else if (m == "cheque") {
            const cd = document.querySelector(".cd").value;
            const cbk = document.querySelector(".cbk").value;
            const cb = document.querySelector(".cb").value;
            const ca = document.querySelector(".ca").value;
            const cno = document.querySelector(".cno").value;
            if (cd == "") {
                window.alert("please select cheque date");
                return;
            }
            if (cbk == "") {
                window.alert("please enter cheque bank");
                return;
            }
            if (cb == "") {
                window.alert("please enter cheque branch");
                return;
            }
            if (ca == "") {
                window.alert("please enter cheque account");
                return;
            }
            if (cno == "") {
                window.alert("please enter cheque number");
                return;
            }

            data = {
                ...mdata,
                date: cd,
                bank: cbk,
                branch: cb,
                account: ca,
                number: cno,
            };
        } else {
            data = { ...mdata };
        }

        setIsSubmitting(true);

        axios
            .post(`/api/admin/update-invoice/${payId}`, data, { headers })
            .then((res) => {
                document.querySelector(".closeb1").click();
                getInvoices();
                setPayID(0);
                setIsSubmitting(false);
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                    showCancelButton: true,
                    confirmButtonText: "Add New Credit Card",
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleAddNewCard();
                    }
                });
                setIsSubmitting(false);
            });
    };

    const handleAddNewCard = () => {
        setAddCardModalOpen(true);
    };

    const closeDoc = (id, type) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Close Invoice!",
        }).then((result) => {
            if (result.isConfirmed) {
                setIsLoading(true);

                axios
                    .get(`/api/admin/close-doc/${id}/${type}`, { headers })
                    .then((response) => {
                        setIsLoading(false);

                        Swal.fire("Closed", response.data.message, "success");
                        setTimeout(() => {
                            getInvoices();
                        }, 1000);
                    })
                    .catch((e) => {
                        setIsLoading(false);

                        Swal.fire({
                            title: "Error!",
                            text: e.response.data.message,
                            icon: "error",
                        });
                    });
            }
        });
    };

    const handleCancel = (e) => {
        e.preventDefault();
        setIsLoading(true);

        const data = {
            doctype: dtype,
            docnum: cancelDoc,
            reason: reason,
        };

        axios
            .post(`/api/admin/cancel-doc`, data, { headers })
            .then((res) => {
                setIsLoading(false);

                $(".closeb11").click();
                Swal.fire(res.data.message, "", "info");
                getInvoices();
            })
            .catch((e) => {
                setIsLoading(false);

                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const handleMethod = (e) => {
        let v = e.target.value;
        if (v == "mt") {
            setBt(true);
            setCh(false);
        } else if (v == "cheque") {
            setBt(false);
            setCh(true);
        } else {
            setBt(false);
            setCh(false);
        }
    };

    const displayCallback = (cb) => {
        $(".ace-tm").css({ backgroundColor: "black", color: "#5cc527" });
        setCbvalue(cb);
    };

    useEffect(() => {
        getInvoices();
    }, [currentPage, filters]);

    return (
        <>
            <div className="boxPanel">
                <div
                    className="InCards container mb-3"
                    style={{ cursor: "pointer" }}
                >
                    <div className="row">
                        <div
                            onClick={(e) => {
                                setFilters({
                                    ...filters,
                                    status: "",
                                    icount_status: "",
                                });
                            }}
                            className="col-sm-2 bg-secondary p-1 m-1 text-white rounded text-center"
                        >
                            <div className="card-body">
                                <p className="lead">{res.all} - Total</p>
                                <hr />
                                <p className="lead"> {res.ta} ILS</p>
                            </div>
                        </div>

                        <div
                            onClick={(e) => {
                                setFilters({
                                    ...filters,
                                    status: "Paid",
                                    icount_status: "",
                                });
                            }}
                            className="col-sm-3 bg-success p-1 m-1 text-white rounded text-center"
                        >
                            <div className="card-body">
                                <p className="lead">{res.paid} - Paid</p>
                                <hr />
                                <p className="lead"> {res.pa} ILS</p>
                            </div>
                        </div>

                        <div
                            onClick={(e) => {
                                setFilters({
                                    ...filters,
                                    status: "Unpaid",
                                    icount_status: "",
                                });
                            }}
                            className="col-sm-3 bg-dark p-1 m-1 text-white rounded text-center"
                        >
                            <div className="card-body">
                                <p className="lead">{res.unpaid} - Unpaid</p>
                                <hr />
                                <p className="lead"> {res.ua} ILS</p>
                            </div>
                        </div>

                        <div
                            onClick={(e) => {
                                setFilters({
                                    ...filters,
                                    status: "Partially Paid",
                                    icount_status: "",
                                });
                            }}
                            className="col-sm-3 bg-warning p-1 m-1 text-white rounded text-center"
                        >
                            <div className="card-body">
                                <p className="lead">
                                    {res.partial} - Partial Paid
                                </p>
                                <hr />
                                <p className="lead">{res.ppa} ILS</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="col-md-12 hidden-xs d-sm-flex justify-content-between mt-2">
                    <div className="d-flex align-items-center">
                        <div style={{ fontWeight: "bold" }}>Filter</div>
                        <div className="mx-3 d-flex align-items-center border rounded">
                            <div className="mx-2 text-nowrap">By Type</div>
                            <select
                                className="form-control"
                                value={filters.type}
                                onChange={(e) => {
                                    setFilters({
                                        ...filters,
                                        type: e.target.value,
                                    });
                                }}
                            >
                                <option value="">All</option>
                                <option value="invoice">Invoice</option>
                                <option value="invrec">Receipt</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div className="action-dropdown dropdown order_drop mb-3 text-right">
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
                                    ...filters,
                                    status: "",
                                    icount_status: "",
                                });
                            }}
                        >
                            All - {res.all}
                        </button>
                        <button
                            className="dropdown-item"
                            onClick={(e) => {
                                setFilters({
                                    ...filters,
                                    status: "",
                                    icount_status: "Open",
                                });
                            }}
                        >
                            Open - {res.open}
                        </button>
                        <button
                            className="dropdown-item"
                            onClick={(e) => {
                                setFilters({
                                    ...filters,
                                    status: "",
                                    icount_status: "Closed",
                                });
                            }}
                        >
                            Closed - {res.closed}
                        </button>
                        <button
                            className="dropdown-item"
                            onClick={(e) => {
                                setFilters({
                                    ...filters,
                                    status: "Paid",
                                    icount_status: "",
                                });
                            }}
                        >
                            Paid - {res.paid}
                        </button>
                        <button
                            className="dropdown-item"
                            onClick={(e) => {
                                setFilters({
                                    ...filters,
                                    status: "Unpaid",
                                    icount_status: "",
                                });
                            }}
                        >
                            Unpaid - {res.unpaid}{" "}
                        </button>
                        <button
                            className="dropdown-item"
                            onClick={(e) => {
                                setFilters({
                                    ...filters,
                                    status: "Partially Paid",
                                    icount_status: "",
                                });
                            }}
                        >
                            Partial paid - {res.partial}{" "}
                        </button>
                    </div>
                </div>

                <div className="table-responsive">
                    {invoices.length > 0 ? (
                        <Table className="table table-bordered">
                            <Thead>
                                <Tr>
                                    <Th
                                        scope="col"
                                        style={{ cursor: "pointer" }}
                                        onClick={(e) => {
                                            sortTable(e, "id");
                                        }}
                                    >
                                        {" "}
                                        #Invoice ID{" "}
                                        <span className="arr"> &darr;</span>
                                    </Th>
                                    <Th
                                        scope="col"
                                        style={{ cursor: "pointer" }}
                                        onClick={(e) => {
                                            sortTable(e, "amount");
                                        }}
                                    >
                                        Total Amount{" "}
                                        <span className="arr"> &darr;</span>
                                    </Th>
                                    <Th
                                        scope="col"
                                        style={{ cursor: "pointer" }}
                                        onClick={(e) => {
                                            sortTable(e, "amount");
                                        }}
                                    >
                                        Paid Amount{" "}
                                        <span className="arr"> &darr;</span>
                                    </Th>
                                    <Th
                                        scope="col"
                                        style={{ cursor: "pointer" }}
                                        onClick={(e) => {
                                            sortTable(e, "created_at");
                                        }}
                                    >
                                        Created Date{" "}
                                        <span className="arr"> &darr;</span>
                                    </Th>
                                    <Th
                                        scope="col"
                                        style={{ cursor: "pointer" }}
                                        onClick={(e) => {
                                            sortTable(e, "due_date");
                                        }}
                                    >
                                        Due Date{" "}
                                        <span className="arr"> &darr;</span>
                                    </Th>
                                    <Th scope="col">Customer </Th>
                                    <Th
                                        scope="col"
                                        style={{ cursor: "pointer" }}
                                        onClick={(e) => {
                                            sortTable(e, "status");
                                        }}
                                    >
                                        Status{" "}
                                        <span className="arr"> &darr;</span>
                                    </Th>
                                    <Th scope="col">Transaction ID/Ref.</Th>
                                    <Th scope="col">Payment Mode</Th>
                                    <Th scope="col">Action</Th>
                                </Tr>
                            </Thead>
                            <Tbody>
                                {invoices.map((item, index) => {
                                    return (
                                        <Tr key={index}>
                                            <Td>#{item.invoice_id}</Td>
                                            <Td>{item.amount} ILS</Td>
                                            <Td>{item.paid_amount} ILS</Td>
                                            <Td>
                                                {Moment(item.created_at).format(
                                                    "DD, MMM Y"
                                                )}
                                            </Td>
                                            <Td>
                                                {item.due_date != null
                                                    ? Moment(
                                                          item.due_date
                                                      ).format("DD, MMM Y")
                                                    : "NA"}
                                            </Td>
                                            <Td>
                                                <Link
                                                    to={`/admin/view-client/${
                                                        item.client
                                                            ? item.client.id
                                                            : "NA"
                                                    }`}
                                                >
                                                    {item.client
                                                        ? item.client
                                                              .firstname +
                                                          " " +
                                                          item.client.lastname
                                                        : "NA"}
                                                </Link>
                                            </Td>
                                            <Td
                                                onClick={(e) =>
                                                    displayCallback(
                                                        item.callback
                                                    )
                                                }
                                                style={{
                                                    cursor: "pointer",
                                                }}
                                                data-toggle="modal"
                                                data-target="#callBack"
                                            >
                                                <a href="#"> {item.status} </a>
                                            </Td>
                                            <Td>
                                                {item.txn_id
                                                    ? item.txn_id
                                                    : "NA"}
                                            </Td>
                                            <Td>
                                                {item.pay_method
                                                    ? item.pay_method
                                                    : "Credit Card"}
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
                                                        <a
                                                            target="_blank"
                                                            href={item.doc_url}
                                                            className="dropdown-item"
                                                        >
                                                            View Invoice
                                                        </a>
                                                        {item.status !=
                                                            "Paid" && (
                                                            <button
                                                                onClick={(
                                                                    e
                                                                ) => {
                                                                    setPayID(
                                                                        item.id
                                                                    );
                                                                    setAmount(
                                                                        item.amount
                                                                    );
                                                                }}
                                                                data-toggle="modal"
                                                                data-target="#exampleModaPaymentAdd"
                                                                className="dropdown-item"
                                                            >
                                                                Add Payment
                                                            </button>
                                                        )}

                                                        {item.invoice_icount_status ==
                                                            "Open" && (
                                                            <button
                                                                onClick={(
                                                                    e
                                                                ) => {
                                                                    closeDoc(
                                                                        item.invoice_id,
                                                                        item.type
                                                                    );
                                                                }}
                                                                className="dropdown-item"
                                                            >
                                                                Close Doc
                                                            </button>
                                                        )}
                                                        {item.invoice_icount_status !=
                                                            "Cancelled" &&
                                                            item.invoice_icount_status !=
                                                                "Closed" && (
                                                                <button
                                                                    onClick={(
                                                                        e
                                                                    ) => {
                                                                        setCancelDoc(
                                                                            item.invoice_id
                                                                        );
                                                                        setDtype(
                                                                            item.type
                                                                        );
                                                                    }}
                                                                    data-toggle="modal"
                                                                    data-target="#exampleModalCancel"
                                                                    className="dropdown-item"
                                                                >
                                                                    Cancel Doc
                                                                </button>
                                                            )}
                                                        {item.receipt && (
                                                            <a
                                                                target="_blank"
                                                                href={
                                                                    item.receipt
                                                                        .docurl
                                                                }
                                                                className="dropdown-item"
                                                            >
                                                                View Receipt
                                                            </a>
                                                        )}
                                                    </div>
                                                </div>
                                            </Td>
                                        </Tr>
                                    );
                                })}
                            </Tbody>
                        </Table>
                    ) : (
                        <div className="form-control text-center">
                            No record found
                        </div>
                    )}

                    {invoices.length > 0 ? (
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
                    ) : (
                        ""
                    )}
                </div>

                <div
                    className="modal fade"
                    id="exampleModaPaymentAdd"
                    tabIndex="-1"
                    role="dialog"
                    aria-labelledby="exampleModaPaymentAdd"
                    aria-hidden="true"
                >
                    <div className="modal-dialog" role="document">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5
                                    className="modal-title"
                                    id="exampleModaPaymentAdd"
                                >
                                    Add Payment
                                </h5>
                                <button
                                    type="button"
                                    className="close"
                                    data-dismiss="modal"
                                    aria-label="Close"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div className="modal-body">
                                <div className="row">
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Amount
                                            </label>
                                            <input
                                                type="text"
                                                value={amount}
                                                className="form-control"
                                                readOnly
                                            ></input>
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Transaction / Reference ID
                                                <small>
                                                    {" "}
                                                    (Optional in credit card
                                                    mode)
                                                </small>
                                            </label>
                                            <input
                                                type="text"
                                                value={txn}
                                                onChange={(e) =>
                                                    setTxn(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter Transaction / Refrence ID"
                                            ></input>
                                        </div>
                                    </div>
                                </div>

                                <div className="row">
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Payment Mode
                                            </label>
                                            <select
                                                name="mode"
                                                className="form-control mode"
                                                onChange={(e) =>
                                                    handleMethod(e)
                                                }
                                            >
                                                <option value="mt">
                                                    Bank Transfer
                                                </option>
                                                <option value="cash">
                                                    By Cash
                                                </option>
                                                <option value="cc">
                                                    Credit Card
                                                </option>
                                                <option value="cheque">
                                                    By Cheque
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                {bt == true && (
                                    <div>
                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Bank Transfer Date
                                                    </label>
                                                    <input
                                                        type="date"
                                                        className="form-control btd"
                                                        required
                                                    ></input>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Account
                                                    </label>
                                                    <input
                                                        type="number"
                                                        className="form-control ba"
                                                        placeholder="Bank account ID where BankTransfer was deposited"
                                                        required
                                                    ></input>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {ch == true && (
                                    <div>
                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Cheque Date
                                                    </label>
                                                    <input
                                                        type="date"
                                                        className="form-control cd"
                                                        required
                                                    ></input>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Cheque Bank
                                                    </label>
                                                    <input
                                                        type="text"
                                                        className="form-control cbk"
                                                        required
                                                        placeholder="Cheque Bank"
                                                    ></input>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Cheque Branch
                                                    </label>
                                                    <input
                                                        type="text"
                                                        className="form-control cb"
                                                        required
                                                        placeholder="Cheque Branch"
                                                    ></input>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Cheque account
                                                    </label>
                                                    <input
                                                        type="number"
                                                        className="form-control ca"
                                                        required
                                                        placeholder="Cheque account"
                                                    ></input>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="form-group">
                                                    <label className="control-label">
                                                        Cheque number
                                                    </label>
                                                    <input
                                                        type="number"
                                                        className="form-control cno"
                                                        required
                                                        placeholder="Cheque number"
                                                    ></input>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                            <div className="modal-footer">
                                <button
                                    type="button"
                                    className="btn btn-secondary closeb1"
                                    data-dismiss="modal"
                                >
                                    Close
                                </button>
                                <button
                                    type="button"
                                    onClick={handlePayment}
                                    disabled={isSubmitting}
                                    className="btn btn-primary sbtn"
                                >
                                    Save Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    className="modal fade"
                    id="exampleModalCancel"
                    tabIndex="-1"
                    role="dialog"
                    aria-labelledby="exampleModalCancel"
                    aria-hidden="true"
                >
                    <div className="modal-dialog" role="document">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5
                                    className="modal-title"
                                    id="exampleModalCancel"
                                >
                                    Cancel Reason
                                </h5>
                                <button
                                    type="button"
                                    className="close"
                                    data-dismiss="modal"
                                    aria-label="Close"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div className="modal-body">
                                <div className="row">
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <textarea
                                                onChange={(e) =>
                                                    setReason(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter Reason(optional)"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button
                                    type="button"
                                    className="btn btn-secondary closeb11"
                                    data-dismiss="modal"
                                >
                                    Close
                                </button>
                                <button
                                    type="button"
                                    onClick={(e) => handleCancel(e)}
                                    className="btn btn-primary sbtn1"
                                >
                                    Cancel Doc
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    className="modal fade"
                    id="callBack"
                    tabIndex="-1"
                    role="dialog"
                    aria-labelledby="callBack"
                    aria-hidden="true"
                >
                    <div className="modal-dialog modal-lg" role="document">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title" id="callBack">
                                    Payment Response
                                </h5>
                                <button
                                    type="button"
                                    className="close"
                                    data-dismiss="modal"
                                    aria-label="Close"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div className="modal-body">
                                <div className="row">
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            {
                                                <AceEditor
                                                    mode="json"
                                                    theme="terminal"
                                                    width="100%"
                                                    name="cbfield"
                                                    fontSize="20px"
                                                    showPrintMargin={false}
                                                    value={
                                                        cbvalue
                                                            ? JSON.stringify(
                                                                  JSON.parse(
                                                                      cbvalue
                                                                  ),
                                                                  null,
                                                                  2
                                                              )
                                                            : ""
                                                    }
                                                    editorProps={{
                                                        $blockScrolling: true,
                                                    }}
                                                    setOptions={{
                                                        useWorker: false,
                                                    }}
                                                />
                                            }
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button
                                    type="button"
                                    className="btn btn-secondary closeb11"
                                    data-dismiss="modal"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {addCardModalOpen && (
                <AddCreditCardModal
                    isOpen={addCardModalOpen}
                    setIsOpen={setAddCardModalOpen}
                    onSuccess={() => getInvoices()}
                    clientId={id}
                />
            )}

            <FullPageLoader visible={isLoading || isSubmitting} />
        </>
    );
}
