import React, { useState, useEffect } from "react";
import ReactPaginate from "react-paginate";
import { Link } from "react-router-dom";
import axios from "axios";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import i18next from "i18next";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";
import Moment from "moment";

import ClientSidebar from "../../Layouts/ClientSidebar";

export default function Invoices() {
    const [invoices, setInvoices] = useState([]);
    const [pageCount, setPageCount] = useState(0);
    const [currentPage, setCurrentPage] = useState(0);
    const [searchVal, setSearchVal] = useState("");

    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };
    const [loading, setLoading] = useState(i18next.t("common.loading"));

    const getInvoices = () => {
        let _filters = {};

        if (searchVal) {
            _filters.keyword = searchVal;
        }

        axios
            .get("/api/client/invoices", {
                headers,
                params: {
                    page: currentPage,
                    ..._filters,
                },
            })
            .then((response) => {
                i18next.changeLanguage(response.data.lng);
                if (response.data.lng == "heb") {
                    import("../../../Assets/css/rtl.css");
                    document.querySelector("html").setAttribute("dir", "rtl");
                } else {
                    document.querySelector("html").removeAttribute("dir");
                }

                if (response.data.invoices.data.length > 0) {
                    setInvoices(response.data.invoices.data);
                    setPageCount(response.data.invoices.last_page);
                } else {
                    setInvoices([]);
                    setLoading(t("client.invoice.no_invoice_found"));
                }
            });
    };

    useEffect(() => {
        getInvoices();
    }, [currentPage, searchVal]);

    return (
        <div id="container">
            <ClientSidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">
                                {t("client.invoice.title")}
                            </h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <input
                                    type="text"
                                    className="form-control"
                                    onChange={(e) => {
                                        setSearchVal(e.target.value);
                                    }}
                                    placeholder={t("client.search")}
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="table-responsive">
                                {invoices.length > 0 ? (
                                    <Table className="table table-bordered responsiveTable">
                                        <Thead>
                                            <Tr>
                                                <Th
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(e, "id");
                                                    }}
                                                >
                                                    {" "}
                                                    {t(
                                                        "client.invoice.#invoice_id"
                                                    )}{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(e, "amount");
                                                    }}
                                                >
                                                    {t(
                                                        "client.invoice.total_amount"
                                                    )}{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(e, "amount");
                                                    }}
                                                >
                                                    {t(
                                                        "client.invoice.paid_amount"
                                                    )}{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(
                                                            e,
                                                            "created_at"
                                                        );
                                                    }}
                                                >
                                                    {t(
                                                        "client.invoice.created_date"
                                                    )}{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(
                                                            e,
                                                            "due_date"
                                                        );
                                                    }}
                                                >
                                                    {t(
                                                        "client.invoice.due_date"
                                                    )}{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th
                                                    style={{
                                                        cursor: "pointer",
                                                    }}
                                                    onClick={(e) => {
                                                        sortTable(e, "status");
                                                    }}
                                                >
                                                    {t("client.invoice.status")}{" "}
                                                    <span className="arr">
                                                        {" "}
                                                        &darr;
                                                    </span>
                                                </Th>
                                                <Th>
                                                    {t(
                                                        "client.invoice.transaction_id_ref"
                                                    )}
                                                </Th>
                                                <Th>
                                                    {t(
                                                        "client.invoice.payment_mode"
                                                    )}
                                                </Th>
                                                <Th>
                                                    {t("client.invoice.action")}
                                                </Th>
                                            </Tr>
                                        </Thead>
                                        <Tbody>
                                            {invoices.map((item, i) => {
                                                return (
                                                    <Tr key={i}>
                                                        <Td>
                                                            #{item.invoice_id}
                                                        </Td>
                                                        <Td>
                                                            {item.amount} ILS
                                                        </Td>
                                                        <Td>
                                                            {item.paid_amount}{" "}
                                                            ILS
                                                        </Td>
                                                        <Td>
                                                            {Moment(
                                                                item.created_at
                                                            ).format(
                                                                "DD, MMM Y"
                                                            )}
                                                        </Td>
                                                        <Td>
                                                            {item.due_date !=
                                                            null
                                                                ? Moment(
                                                                      item.due_date
                                                                  ).format(
                                                                      "DD, MMM Y"
                                                                  )
                                                                : "NA"}
                                                        </Td>
                                                        <Td>{item.status}</Td>
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
                                                                        href={
                                                                            item.doc_url
                                                                        }
                                                                        className="dropdown-item"
                                                                    >
                                                                        {t(
                                                                            "client.invoice.view_invoice"
                                                                        )}
                                                                    </a>
                                                                    {item.receipt && (
                                                                        <a
                                                                            target="_blank"
                                                                            href={
                                                                                item
                                                                                    .receipt
                                                                                    .docurl
                                                                            }
                                                                            className="dropdown-item"
                                                                        >
                                                                            {t(
                                                                                "client.invoice.view_receipt"
                                                                            )}
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
                                    <p className="text-center mt-5">
                                        {loading}
                                    </p>
                                )}
                            </div>

                            {invoices.length > 0 && (
                                <ReactPaginate
                                    previousLabel={t("client.previous")}
                                    nextLabel={t("client.next")}
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
    );
}
