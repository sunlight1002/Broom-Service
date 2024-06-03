import React, { useState, useEffect, useRef } from "react";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import Moment from "moment";
import Swal from "sweetalert2";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import AddPaymentModal from "../../Components/Modals/AddPaymentModal";
import AddCreditCardModal from "../../Components/Modals/AddCreditCardModal";
import FilterButtons from "../../../Components/common/FilterButton";
import FullPageLoader from "../../../Components/common/FullPageLoader";

const thisMonthFilter = {
    start_date: Moment().startOf("month").format("YYYY-MM-DD"),
    end_date: Moment().endOf("month").format("YYYY-MM-DD"),
};

const nextMonthFilter = {
    start_date: Moment().add(1, "month").startOf("month").format("YYYY-MM-DD"),
    end_date: Moment().add(1, "month").endOf("month").format("YYYY-MM-DD"),
};

export default function Payments() {
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
    const [isLoading, setIsLoading] = useState(false);

    const navigate = useNavigate();
    const alert = useAlert();
    const tableRef = useRef(null);
    const paidStatusRef = useRef(null);
    const startDateRef = useRef(null);
    const endDateRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        $(tableRef.current).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/api/admin/client-payments",
                type: "GET",
                beforeSend: function (request) {
                    request.setRequestHeader(
                        "Authorization",
                        `Bearer ` + localStorage.getItem("admin-token")
                    );
                },
                data: function (d) {
                    d.priority_paid_status = paidStatusRef.current.value;
                    d.start_date = startDateRef.current.value;
                    d.end_date = endDateRef.current.value;
                },
            },
            order: [[0, "desc"]],
            columns: [
                {
                    title: "Date",
                    data: "last_activity_date",
                },
                {
                    title: "Client",
                    data: "client_name",
                    render: function (data, type, row, meta) {
                        let _html = `<span class="client-name-badge dt-client-badge" style="color: #FFFFFF; background-color: #D500A6;" data-client-id="${row.client_id}">`;

                        _html += `<i class="fa-solid fa-user"></i>`;

                        _html += data;

                        _html += `</span>`;

                        return _html;
                    },
                },
                {
                    title: "Status",
                    data: "priority_paid_status",
                    render: function (data, type, row, meta) {
                        if (data) {
                            const _statusName = priorityStatus(data);

                            return `<div class="client-payment-status-badge"> ${_statusName} </div>`;
                        } else {
                            return "";
                        }
                    },
                },
                {
                    title: "Done",
                    data: "completed_jobs",
                },
                {
                    title: "Visits",
                    data: "visits",
                },
                {
                    title: "Action",
                    data: "action",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let _html = "";

                        if (row.priority_paid_status) {
                            const _statusName = priorityStatus(data);

                            _html +=
                                '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                            _html += `<button type="button" class="dropdown-item dt-see-document-btn" data-client-id="${row.client_id}">See document</button>`;

                            if (
                                ["unpaid", "undone", "problem"].includes(
                                    _statusName
                                )
                            ) {
                                _html += `<button type="button" class="dropdown-item dt-close-invoice-with-receipt-btn" data-client-id="${row.client_id}">Close invoice with receipt</button>`;
                            }

                            if (_statusName == "problem") {
                                _html += `<button type="button" class="dropdown-item dt-update-new-credit-card-btn" data-client-id="${row.client_id}">Update new Credit Card</button>`;
                            }

                            if (_statusName != "paid") {
                                if (row.payment_method == "cc") {
                                    _html += `<button type="button" class="dropdown-item dt-close-for-payment-btn" data-client-id="${row.client_id}">Close for payment</button>`;
                                }

                                if (_statusName != "unpaid") {
                                    _html += `<button type="button" class="dropdown-item dt-generate-invoice-btn" data-client-id="${row.client_id}">Generate Invoice</button>`;
                                }
                            }

                            if (_statusName && _statusName != "paid") {
                                _html += `<button type="button" class="dropdown-item dt-close-without-payment-btn" data-client-id="${row.client_id}">Close without payment</button>`;
                            }

                            _html += "</div> </div>";
                        }

                        return _html;
                    },
                },
            ],
            ordering: true,
            searching: true,
            responsive: true,
        });

        $(tableRef.current).on("click", ".dt-see-document-btn", function () {
            const _clientID = $(this).data("client-id");
            navigate(`/admin/view-client/${_clientID}#tab-invoice`);
        });

        $(tableRef.current).on(
            "click",
            ".dt-close-invoice-with-receipt-btn",
            function () {
                const _clientID = $(this).data("client-id");

                handleCloseWithReceipt(_clientID);
            }
        );

        $(tableRef.current).on(
            "click",
            ".dt-update-new-credit-card-btn",
            function () {
                const _clientID = $(this).data("client-id");

                handleAddNewCard(_clientID);
            }
        );

        $(tableRef.current).on(
            "click",
            ".dt-close-for-payment-btn",
            function () {
                const _clientID = $(this).data("client-id");

                handleCloseForPayment(_clientID);
            }
        );

        $(tableRef.current).on(
            "click",
            ".dt-generate-invoice-btn",
            function () {
                const _clientID = $(this).data("client-id");

                handleGenerateInvoice(_clientID);
            }
        );

        $(tableRef.current).on(
            "click",
            ".dt-close-without-payment-btn",
            function () {
                const _clientID = $(this).data("client-id");

                handleCloseWithoutPayment(_clientID);
            }
        );

        return function cleanup() {
            $(tableRef.current).DataTable().destroy(true);
        };
    }, []);

    const handleAddNewCard = (_clientID) => {
        setSelectedClientID(_clientID);
        setAddCardModalOpen(true);
    };

    const handleCloseWithReceipt = (_clientID) => {
        setSelectedClientID(_clientID);
        setAddPaymentModalOpen(true);
    };

    useEffect(() => {
        $(tableRef.current).DataTable().draw();
    }, [dateRange, paidStatusFilter]);

    const handleCloseForPayment = async (_clientID) => {
        setIsLoading(true);
        await axios
            .post(
                `/api/admin/client/${_clientID}/close-for-payment`,
                {},
                {
                    headers,
                }
            )
            .then((response) => {
                setIsLoading(false);
                Swal.fire(
                    "Payment Closed!",
                    "Invoice receipt has been created.",
                    "success"
                );
                $(tableRef.current).DataTable().draw();
            })
            .catch((e) => {
                setIsLoading(false);
                $(tableRef.current).DataTable().draw();

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

    const handleGenerateInvoice = async (_clientID) => {
        setIsLoading(true);
        await axios
            .post(
                `/api/admin/client/${_clientID}/generate-invoice`,
                {},
                {
                    headers,
                }
            )
            .then((response) => {
                setIsLoading(false);
                Swal.fire(
                    "Invoice Generated!",
                    "Invoice has been created.",
                    "success"
                );
                $(tableRef.current).DataTable().draw();
            })
            .catch((e) => {
                setIsLoading(false);
                $(tableRef.current).DataTable().draw();

                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const handleCloseWithoutPayment = async (_clientID) => {
        Swal.fire({
            title: "Are you sure to close without payment?",
            showDenyButton: true,
            confirmButtonText: "Yes",
            denyButtonText: `No`,
        }).then(async (result) => {
            if (result.isConfirmed) {
                setIsLoading(true);
                await axios
                    .post(
                        `/api/admin/client/${_clientID}/close-without-payment`,
                        {},
                        {
                            headers,
                        }
                    )
                    .then((response) => {
                        setIsLoading(false);
                        Swal.fire("Closed without payment!", "", "success");
                        $(tableRef.current).DataTable().draw();
                    })
                    .catch((e) => {
                        setIsLoading(false);
                        $(tableRef.current).DataTable().draw();

                        Swal.fire({
                            title: "Error!",
                            text: e.response.data.message,
                            icon: "error",
                        });
                    });
            }
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
                    </div>
                </div>
                <div className="payment-filter mb-3">
                    <div className="row mb-2">
                        <div className="col-sm-12 d-md-flex align-items-center">
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
                        <div className="col-sm-12 d-md-flex align-items-center">
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

                            <input
                                type="hidden"
                                value={paidStatusFilter}
                                ref={paidStatusRef}
                            />

                            <input
                                type="hidden"
                                value={dateRange.start_date}
                                ref={startDateRef}
                            />

                            <input
                                type="hidden"
                                value={dateRange.end_date}
                                ref={endDateRef}
                            />
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel-th-border-none">
                            <table
                                ref={tableRef}
                                className="display table table-bordered"
                            />
                        </div>
                    </div>
                </div>
            </div>

            {addPaymentModalOpen && (
                <AddPaymentModal
                    isOpen={addPaymentModalOpen}
                    setIsOpen={setAddPaymentModalOpen}
                    onSuccess={() => $(tableRef.current).DataTable().draw()}
                    clientId={selectedClientID}
                    handleAddNewCard={handleAddNewCard}
                />
            )}

            {addCardModalOpen && (
                <AddCreditCardModal
                    isOpen={addCardModalOpen}
                    setIsOpen={setAddCardModalOpen}
                    onSuccess={() => $(tableRef.current).DataTable().draw()}
                    clientId={selectedClientID}
                />
            )}

            <FullPageLoader visible={isLoading} />
        </div>
    );
}
