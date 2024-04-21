import React, { useState, useEffect } from "react";
import ReactPaginate from "react-paginate";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useAlert } from "react-alert";
import { Link } from "react-router-dom";
import axios from "axios";
import Moment from "moment";
import Swal from "sweetalert2";
import { Base64 } from "js-base64";

import Sidebar from "../../Layouts/Sidebar";
import AddPaymentModal from "../../Components/Modals/AddPaymentModal";

const thisMonthFilter = {
    start_date: Moment().startOf("month").format("YYYY-MM-DD"),
    end_date: Moment().endOf("month").format("YYYY-MM-DD"),
};

const nextMonthFilter = {
    start_date: Moment().add(1, "month").startOf("month").format("YYYY-MM-DD"),
    end_date: Moment().add(1, "month").endOf("month").format("YYYY-MM-DD"),
};

export default function Payments() {
    const [loading, setLoading] = useState("Loading...");
    const [pageCount, setPageCount] = useState(0);
    const [clients, setClients] = useState([]);
    const [currentPage, setCurrentPage] = useState(0);
    const [dateRange, setDateRange] = useState({
        start_date: thisMonthFilter.start_date,
        end_date: thisMonthFilter.end_date,
    });
    const [selectedDateFilter, setSelectedDateFilter] = useState("This month");
    const [paidStatusFilter, setPaidStatusFilter] = useState("all");
    const [addPaymentModalOpen, setAddPaymentModalOpen] = useState(false);
    const [selectedClientID, setSelectedClientID] = useState(null);
    const [clientCardSessionID, setClientCardSessionID] = useState(null);
    const [sessionURL, setSessionURL] = useState("");
    const [checkingClientIDForCard, setCheckingClientIDForCard] =
        useState(null);

    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getClientPayments = () => {
        let _filters = {};

        if (paidStatusFilter) {
            _filters.last_paid_status = paidStatusFilter;
        }

        _filters.start_date = dateRange.start_date;
        _filters.end_date = dateRange.end_date;

        axios
            .get("/api/admin/client-payments", {
                headers,
                params: {
                    page: currentPage,
                    ..._filters,
                },
            })
            .then((res) => {
                if (res.data.data.data.length > 0) {
                    setClients(res.data.data.data);
                    setPageCount(res.data.data.last_page);
                } else {
                    setClients([]);
                    setLoading("No record found");
                }
            });
    };

    const copy = [...clients];
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
            setClients(sortData);
            setOrder("DESC");
        }
        if (order == "DESC") {
            const sortData = [...copy].sort((a, b) =>
                a[col] < b[col] ? -1 : 1
            );
            setClients(sortData);
            setOrder("ASC");
        }
    };

    const handlePageClick = async (data) => {
        setCurrentPage(currentPage + 1);
    };

    const handleCloseWithReceipt = (_clientID) => {
        setSelectedClientID(_clientID);
        setAddPaymentModalOpen(true);
    };

    useEffect(() => {
        getClientPayments();
    }, [currentPage, dateRange, paidStatusFilter]);

    const handleCloseForPayment = (_clientID) => {
        axios
            .post(
                `/api/admin/client/${_clientID}/close-for-payment`,
                {},
                {
                    headers,
                }
            )
            .then((response) => {
                Swal.fire(
                    "Payment Closed!",
                    "Invoice receipt has been created.",
                    "success"
                );
                getClientPayments();
            })
            .catch((e) => {
                getClientPayments();

                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const handleAddingClientCard = (_clientID) => {
        if (checkingClientIDForCard) {
            alert.info("Adding card is already in-progress");
            return false;
        }

        alert.info("Adding card in progress");

        axios
            .post(
                `/api/admin/client/${_clientID}/initialize-card`,
                {},
                { headers }
            )
            .then((response) => {
                setCheckingClientIDForCard(_clientID);

                setClientCardSessionID(response.data.session_id);
                setSessionURL(response.data.redirect_url);
                $("#addCardModal").modal("show");
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    useEffect(() => {
        let _intervalID;

        if (checkingClientIDForCard && clientCardSessionID) {
            _intervalID = setInterval(() => {
                if (checkingClientIDForCard) {
                    axios
                        .post(
                            `/api/admin/client/${checkingClientIDForCard}/check-card-by-session`,
                            { session_id: clientCardSessionID },
                            { headers }
                        )
                        .then((response) => {
                            if (response.data.status == "completed") {
                                alert.success("Card added successfully");
                                setCheckingClientIDForCard(null);
                                setClientCardSessionID(null);
                                clearInterval(_intervalID);
                                getClientPayments();
                            }
                        })
                        .catch((e) => {
                            setCheckingClientIDForCard(null);
                            setClientCardSessionID(null);
                            clearInterval(_intervalID);

                            Swal.fire({
                                title: "Error!",
                                text: e.response.data.message,
                                icon: "error",
                            });
                        });
                }
            }, 2000);
        }

        return () => clearInterval(_intervalID);
    }, [checkingClientIDForCard, clientCardSessionID]);

    useEffect(() => {
        console.log("clientCardSessionID", clientCardSessionID);
    }, [clientCardSessionID]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Payments</h1>
                        </div>
                    </div>
                </div>
                <div className="payment-filter mb-3">
                    <div className="row">
                        <div className="col-sm-6">
                            <FilterButtons
                                text="all"
                                className="px-3 mr-1"
                                selectedFilter={paidStatusFilter}
                                setselectedFilter={setPaidStatusFilter}
                            />

                            <FilterButtons
                                text="unpaid"
                                className="px-3 mr-1"
                                selectedFilter={paidStatusFilter}
                                setselectedFilter={setPaidStatusFilter}
                            />

                            <FilterButtons
                                text="paid"
                                className="px-3 mr-1"
                                selectedFilter={paidStatusFilter}
                                setselectedFilter={setPaidStatusFilter}
                            />

                            <FilterButtons
                                text="problem"
                                className="px-3 mr-1"
                                selectedFilter={paidStatusFilter}
                                setselectedFilter={setPaidStatusFilter}
                            />

                            <FilterButtons
                                text="undone"
                                className="px-3 mr-1"
                                selectedFilter={paidStatusFilter}
                                setselectedFilter={setPaidStatusFilter}
                            />
                        </div>
                        <div className="col-sm-6">
                            <div className="float-right">
                                <FilterButtons
                                    text="This month"
                                    className="px-3 mr-1"
                                    onClick={() =>
                                        setDateRange({
                                            start_date:
                                                thisMonthFilter.start_date,
                                            end_date: thisMonthFilter.end_date,
                                        })
                                    }
                                    selectedFilter={selectedDateFilter}
                                    setselectedFilter={setSelectedDateFilter}
                                />

                                <FilterButtons
                                    text="Next month"
                                    className="px-3 mr-1"
                                    onClick={() =>
                                        setDateRange({
                                            start_date:
                                                nextMonthFilter.start_date,
                                            end_date: nextMonthFilter.end_date,
                                        })
                                    }
                                    selectedFilter={selectedDateFilter}
                                    setselectedFilter={setSelectedDateFilter}
                                />

                                <FilterButtons
                                    text="All time"
                                    className="px-3 mr-1"
                                    onClick={() =>
                                        setDateRange({
                                            start_date: null,
                                            end_date: null,
                                        })
                                    }
                                    selectedFilter={selectedDateFilter}
                                    setselectedFilter={setSelectedDateFilter}
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="table-responsive">
                                <Table className="table table-bordered">
                                    <Thead>
                                        <Tr>
                                            <Th
                                                scope="col"
                                                style={{
                                                    cursor: "pointer",
                                                }}
                                                onClick={(e) => {
                                                    sortTable(e, "date");
                                                }}
                                            >
                                                Date
                                                <span className="arr">
                                                    {" "}
                                                    &darr;
                                                </span>
                                            </Th>
                                            <Th scope="col">Client </Th>
                                            <Th
                                                scope="col"
                                                style={{
                                                    cursor: "pointer",
                                                }}
                                                onClick={(e) => {
                                                    sortTable(e, "client_name");
                                                }}
                                            >
                                                Status
                                                <span className="arr">
                                                    {" "}
                                                    &darr;
                                                </span>
                                            </Th>
                                            <Th scope="col">Visits</Th>
                                            <Th scope="col">Action</Th>
                                        </Tr>
                                    </Thead>
                                    <Tbody>
                                        {clients.map((item, index) => {
                                            return (
                                                <Tr key={index}>
                                                    <Td>
                                                        {item.last_activity_date ??
                                                            "NA"}
                                                    </Td>
                                                    <Td>
                                                        <Link
                                                            to={`/admin/view-client/${item.client_id}`}
                                                        >
                                                            {item.client_name}
                                                        </Link>
                                                    </Td>
                                                    <Td>
                                                        {item.last_paid_status}
                                                    </Td>
                                                    <Td>{item.visits}</Td>
                                                    <Td>
                                                        {(item.last_order_doc_url ||
                                                            item.last_paid_status) && (
                                                            <div className="action-dropdown dropdown">
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-default dropdown-toggle"
                                                                    data-toggle="dropdown"
                                                                >
                                                                    <i className="fa fa-ellipsis-vertical"></i>
                                                                </button>
                                                                <div className="dropdown-menu">
                                                                    {item.last_order_doc_url && (
                                                                        <Link
                                                                            className="dropdown-item"
                                                                            to={`/admin/view-client/${item.client_id}#tab-order`}
                                                                        >
                                                                            See
                                                                            document
                                                                        </Link>
                                                                    )}
                                                                    {item.last_paid_status ==
                                                                        "unpaid" && (
                                                                        <button
                                                                            className="dropdown-item"
                                                                            onClick={() =>
                                                                                handleCloseWithReceipt(
                                                                                    item.client_id
                                                                                )
                                                                            }
                                                                        >
                                                                            Close
                                                                            invoice
                                                                            with
                                                                            receipt
                                                                        </button>
                                                                    )}
                                                                    {item.last_paid_status ==
                                                                        "problem" && (
                                                                        <button
                                                                            className="dropdown-item"
                                                                            onClick={() =>
                                                                                handleAddingClientCard(
                                                                                    item.client_id
                                                                                )
                                                                            }
                                                                        >
                                                                            Update
                                                                            new
                                                                            Credit
                                                                            Card
                                                                        </button>
                                                                    )}
                                                                    {
                                                                        (item.payment_method =
                                                                            "cc" &&
                                                                                item.last_paid_status &&
                                                                                item.last_paid_status !=
                                                                                    "paid" && (
                                                                                    <button
                                                                                        className="dropdown-item"
                                                                                        onClick={() =>
                                                                                            handleCloseForPayment(
                                                                                                item.client_id
                                                                                            )
                                                                                        }
                                                                                    >
                                                                                        Close
                                                                                        for
                                                                                        payment
                                                                                    </button>
                                                                                ))
                                                                    }
                                                                </div>
                                                            </div>
                                                        )}
                                                    </Td>
                                                </Tr>
                                            );
                                        })}

                                        {clients.length == 0 && (
                                            <Tr>
                                                <Td colSpan={5}>
                                                    <p className="text-center">
                                                        No data found
                                                    </p>
                                                </Td>
                                            </Tr>
                                        )}
                                    </Tbody>
                                </Table>

                                {clients.length > 0 && (
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

            {addPaymentModalOpen && (
                <AddPaymentModal
                    isOpen={addPaymentModalOpen}
                    setIsOpen={setAddPaymentModalOpen}
                    onSuccess={() => getClientPayments()}
                    clientId={selectedClientID}
                />
            )}

            <div
                className="modal fade"
                id="addCardModal"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="addCardModalLabel"
                aria-hidden="true"
            >
                <div
                    className="modal-dialog modal-dialog-centered modal-lg"
                    role="document"
                >
                    <div className="modal-content">
                        <div className="modal-header">
                            <button
                                type="button"
                                className="btn btn-secondary"
                                data-dismiss="modal"
                                aria-label="Close"
                            >
                                Back
                            </button>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <iframe
                                            src={sessionURL}
                                            title="Pay Card Transaction"
                                            width="100%"
                                            height="800"
                                        ></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
