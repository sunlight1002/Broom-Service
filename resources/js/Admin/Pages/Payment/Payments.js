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
import AddCreditCardModal from "../../Components/Modals/AddCreditCardModal";

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
    const [addCardModalOpen, setAddCardModalOpen] = useState(false);
    const [selectedClientID, setSelectedClientID] = useState(null);
    const [searchVal, setSearchVal] = useState("");

    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getClientPayments = () => {
        let _filters = {};

        if (paidStatusFilter) {
            _filters.priority_paid_status = paidStatusFilter;
        }

        if (searchVal) {
            _filters.keyword = searchVal;
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

    const handleAddNewCard = (_clientID) => {
        setSelectedClientID(_clientID);
        setAddCardModalOpen(true);
    };

    const handleCloseWithReceipt = (_clientID) => {
        setSelectedClientID(_clientID);
        setAddPaymentModalOpen(true);
    };

    useEffect(() => {
        getClientPayments();
    }, [currentPage, dateRange, paidStatusFilter, searchVal]);

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
                    showCancelButton: true,
                    confirmButtonText: "Add New Credit Card",
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleAddNewCard(_clientID);
                    }
                });
            });
    };

    const priorityStatus = (_status) => {
        const _statuses = ["", "", "paid", "undone", "unpaid", "problem"];

        return _statuses[_status];
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Payments</h1>
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
                    </div>
                </div>
                <div className="payment-filter mb-3">
                    <div className="row mb-2">
                        <div className="col-sm-12 d-flex align-items-center">
                            <div
                                className="mr-3"
                                style={{ fontWeight: "bold" }}
                            >
                                Status
                            </div>
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
                            <FilterButtons
                                text="This month"
                                className="px-3 mr-1"
                                onClick={() =>
                                    setDateRange({
                                        start_date: thisMonthFilter.start_date,
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
                                        start_date: nextMonthFilter.start_date,
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
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel-th-border-none">
                            <div className="table-responsive">
                                <Table className="table">
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
                                            <Th scope="col">Done</Th>
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
                                                            to={
                                                                item.client_id
                                                                    ? `/admin/view-client/${item.client_id}`
                                                                    : "#"
                                                            }
                                                            style={{
                                                                color: "white",
                                                                background:
                                                                    "#D500A6",
                                                                padding:
                                                                    "3px 8px",
                                                                borderRadius:
                                                                    "5px",
                                                                display: "flex",
                                                                alignItems:
                                                                    "center",
                                                                width: "max-content",
                                                            }}
                                                        >
                                                            <i
                                                                className="fa-solid fa-user"
                                                                style={{
                                                                    fontSize:
                                                                        "12px",
                                                                    marginRight:
                                                                        "5px",
                                                                }}
                                                            ></i>
                                                            {item.client_name ||
                                                                "NA"}
                                                        </Link>
                                                    </Td>
                                                    <Td>
                                                        <div
                                                            style={{
                                                                color: "white",
                                                                background:
                                                                    item.priority_paid_status
                                                                        ? "#2F4054"
                                                                        : "white",
                                                                padding:
                                                                    "3px 20px",
                                                                borderRadius:
                                                                    "5px",
                                                                display: "flex",
                                                                alignItems:
                                                                    "center",
                                                                width: "max-content",
                                                            }}
                                                        >
                                                            {priorityStatus(
                                                                item.priority_paid_status
                                                            ) || "-"}
                                                        </div>
                                                    </Td>
                                                    <Td>
                                                        {item.completed_jobs}
                                                    </Td>
                                                    <Td>{item.visits}</Td>
                                                    <Td>
                                                        {item.priority_paid_status && (
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
                                                                        className="dropdown-item"
                                                                        to={`/admin/view-client/${item.client_id}#tab-invoice`}
                                                                    >
                                                                        See
                                                                        document
                                                                    </Link>
                                                                    {priorityStatus(
                                                                        item.priority_paid_status
                                                                    ) ==
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
                                                                    {priorityStatus(
                                                                        item.priority_paid_status
                                                                    ) ==
                                                                        "problem" && (
                                                                        <button
                                                                            className="dropdown-item"
                                                                            onClick={() =>
                                                                                handleAddNewCard(
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
                                                                                item.priority_paid_status &&
                                                                                priorityStatus(
                                                                                    item.priority_paid_status
                                                                                ) !=
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
                                        previousLabel={"<"}
                                        nextLabel={">"}
                                        breakLabel={"..."}
                                        pageCount={pageCount}
                                        marginPagesDisplayed={2}
                                        pageRangeDisplayed={3}
                                        onPageChange={handlePageClick}
                                        containerClassName={
                                            "pagination justify-content-end mt-3"
                                        }
                                        pageClassName={"page-item"}
                                        pageLinkClassName={"page-link px-4"}
                                        previousClassName={"page-item"}
                                        previousLinkClassName={
                                            "page-link page-link-prev-link customize-pagination"
                                        }
                                        nextClassName={"page-item"}
                                        nextLinkClassName={
                                            "page-link page-link-next-link customize-pagination"
                                        }
                                        breakClassName={"page-item"}
                                        breakLinkClassName={"page-link"}
                                        activeClassName={"active"}
                                        disabledLinkClassName="disabled-pagination-link"
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
                    handleAddNewCard={handleAddNewCard}
                />
            )}

            {addCardModalOpen && (
                <AddCreditCardModal
                    isOpen={addCardModalOpen}
                    setIsOpen={setAddCardModalOpen}
                    onSuccess={() => getClientPayments()}
                    clientId={selectedClientID}
                />
            )}
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
