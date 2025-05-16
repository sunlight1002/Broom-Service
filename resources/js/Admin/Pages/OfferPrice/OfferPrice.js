import React, { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import { Base64 } from "js-base64";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import FilterButtons from "../../../Components/common/FilterButton";
import Sidebar from "../../Layouts/Sidebar";
import ViewOfferModal from "./ViewOfferModal";

export default function OfferPrice() {
    const { t, i18n } = useTranslation();
    const tableRef = useRef(null);
    const [offerId, setOfferId] = useState(null);
    const [statusModal, setStatusModal] = useState(false)
    const [status, setStatus] = useState("");
    const navigate = useNavigate();
    const [filter, setFilter] = useState("All");
    const [isModalOpen, setModalStatus] = useState(false);
    const [selectedOfferId, setSelectedOfferId] = useState(null);
    const alert = useAlert();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const offerStatuses = {
        "Sent": t("global.sent"),
        "Accepted": t("modal.accepted"),
        "Declined": t("admin.schedule.options.meetingStatus.Declined")
    };

    const statusArr = {
        "sent": t("global.sent"),
        "accepted": t("modal.accepted"),
        "declined": t("admin.schedule.options.meetingStatus.Declined")
    };

    const handleModal = (id) => {
        setSelectedOfferId(id);
        setModalStatus(true);
    };

    const handleReopen = async (id) => {

        Swal.fire({
            title: t("global.areYouSure"),
            text: t("global.notAbleToRevert"),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: t("swal.button.reopen_offer"),
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await axios.post(`/api/admin/offer-reopen/${id}`, {}, { headers });
                    if (res.status === 200) {
                        Swal.fire({
                            icon: "success",
                            title: t("swal.offer_reopend"),
                            showConfirmButton: false,
                            timer: 1500,
                        });
                        setTimeout(() => {
                            $(tableRef.current).DataTable().draw();
                        }, 1000);
                    }
                } catch (error) {
                    console.log(error);
                }
            }
        });

    }

    const changeStatusModal = (id, status) => {
        setOfferId(id);
        setStatus(status);
        setStatusModal(true);
    }

    const handleUpdateStatus = async () => {
        try {
            const res = await axios.put(`/api/admin/offer-change-status/${offerId}`, { status }, { headers });
            if (res.status === 200) {
                setStatusModal(false);
                $(tableRef.current).DataTable().draw();
                alert.success(res?.data?.message);
            }
        } catch (error) {
            console.log(error);
        }
    }

    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/offers",
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: t("global.date"),
                        data: "created_at",
                    },
                    {
                        title: t("admin.dashboard.clients"),
                        data: "name",
                        render: function (data, type, row, meta) {
                            return `<a href="/admin/clients/view/${row.client_id}" target="_blank" class="dt-client-name" style="color: black; text-decoration: underline;"> ${data} </a>`;
                        },
                    },
                    {
                        title: t("admin.global.Email"),
                        data: "email",
                    },
                    {
                        title: t("admin.global.Phone"),
                        data: "phone",
                        render: function (data) {
                            return `+${data}`;
                        }
                    },
                    {
                        title: t("admin.global.Status"),
                        data: "status",
                        render: function (data, type, row, meta) {
                            let color = "";
                            if (data == "sent") {
                                color = "purple";
                            } else if (data == "accepted") {
                                color = "green";
                            } else {
                                color = "red";
                            }

                            // return `<span style="color: ${color};">${data}</span>`;
                            return `<p class="dt-status-btn" data-id="${row.id}" data-status="${data}" style="background-color: #efefef; color: ${color}; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                            ${data}
                        </p>`;

                        },
                    },
                    {
                        title: "Total",
                        data: "subtotal",
                        render: function (data, type, row, meta) {
                            return `${data} ILS + VAT`;
                        },
                    },
                    {
                        title: t("admin.global.Action"),
                        data: "action",
                        orderable: false,
                        responsivePriority: 1,
                        render: function (data, type, row, meta) {
                            let _html =
                                '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                            _html += `<button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">${t('admin.leads.Edit')}</button>`;

                            _html += `<button type="button" class="dropdown-item dt-client-offer-btn" data-id="${Base64.encode(row.id.toString())}">View Client Offer</button>`;

                            _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>`;

                            _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>`;

                            // Conditionally include "Reopen" button if the status is not "accepted"
                            if (row.status === "accepted") {
                                _html += `<button type="button" class="dropdown-item dt-reopen-btn" data-id="${row.id}">${t("admin.leads.reopen")}</button>`;
                            }

                            _html += "</div> </div>";

                            return _html;
                        },
                    },
                ],
                ordering: true,
                searching: true,
                responsive: true,
                autoWidth: true,
                width: "100%",
                scrollX: true,
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
                columnDefs: [
                    {
                        targets: '_all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass('custom-cell-class ');
                        }
                    }
                ],
                initComplete: function () {
                    // Explicitly set the initial page after table initialization
                    const table = $(tableRef.current).DataTable();
                    table.page(initialPage).draw("page");
                },
            });
        } else {
            // Reuse the existing table and set the page directly
            const table = $(tableRef.current).DataTable();
            table.page(initialPage).draw("page");
        }
    };

    const getCurrentPageNumber = () => {
        const table = $(tableRef.current).DataTable();
        const pageInfo = table.page.info();
        return pageInfo.page + 1; // Adjusted to return 1-based page number
    };

    useEffect(() => {
        const searchParams = new URLSearchParams(location.search);
        const pageFromUrl = parseInt(searchParams.get("page")) || 1;
        const initialPage = pageFromUrl - 1;

        initializeDataTable(initialPage);

        // Customize the search input
        const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
        $("div.dt-search").append(searchInputWrapper);
        $("div.dt-search").addClass("position-relative");

        $(tableRef.current).on("click", "tr.dt-row,tr.child", function (e) {
            let _id = null;
            if (e.target.closest("tr.dt-row")) {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-client-name") &&
                    !e.target.closest(".dt-status-btn") &&
                    (!tableRef.current.classList.contains("collapsed") ||
                        !e.target.closest(".dtr-control"))
                ) {
                    _id = $(this).data("id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-client-name") &&
                    !e.target.closest(".dt-status-btn")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                handleModal(_id);
            }
        });


        // Event listener for pagination
        $(tableRef.current).on("page.dt", function () {
            const currentPageNumber = getCurrentPageNumber();

            // Update the URL with the page number
            const url = new URL(window.location);
            url.searchParams.set("page", currentPageNumber);

            // Use replaceState to avoid adding new history entry
            window.history.replaceState({}, "", url);
        });

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/offered-price/edit/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-client-offer-btn", function () {
            const _id = $(this).data("id");
            window.open(`/price-offer/${_id}`, '_blank');
            // navigate(`/price-offer/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            // navigate(`/admin/view-offer/${_id}`);
            handleModal(_id);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        $(tableRef.current).on("click", ".dt-reopen-btn", function () {
            const _id = $(this).data("id");
            handleReopen(_id);
        });

        $(tableRef.current).on("click", ".dt-status-btn", function () {
            const _id = $(this).data("id");
            const status = $(this).data("status");
            changeStatusModal(_id, status);
        });


        // Handle language changes
        i18n.on("languageChanged", () => {
            $(tableRef.current).DataTable().destroy(); // Destroy the table
            initializeDataTable(initialPage);
        });

        // Cleanup event listeners and destroy DataTable when unmounting
        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true); // Ensure proper cleanup
                $(tableRef.current).off("click");
                $(tableRef.current).off("page.dt");
            }
        };
    }, []);

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Offer!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/offers/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Offer has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            $(tableRef.current).DataTable().draw();
                        }, 1000);
                    });
            }
        });
    };

    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order(parseInt(colIdx), "asc").draw();
    };

    useEffect(() => {
        if (filter == "All") {
            $(tableRef.current).DataTable().column(4).search(null).draw();
        } else {
            $(tableRef.current).DataTable().column(4).search(filter).draw();
        }
    }, [filter]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-5">
                            <h1 className="page-title">{t("client.common.offers")}</h1>
                        </div>
                        <div className="col-sm-7">
                            <div className="search-data">
                                <Link
                                    to="/admin/offers/create"
                                    className="btn navyblue no-hover addButton"
                                >
                                    <i className="btn-icon fas fa-plus-circle"></i>
                                    {t("admin.global.AddNew")}
                                </Link>
                            </div>
                        </div>
                        <div className="col-sm-12 pl-2 d-flex d-lg-none">
                            <div className="search-data mt-2">
                                <div className="action-dropdown dropdown d-flex align-items-center mt-md-4 mr-2 ">
                                    <div
                                        className=" mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
                                        {t("global.filter")}
                                    </div>
                                    <button
                                        type="button"
                                        className="btn btn-default navyblue dropdown-toggle"
                                        data-toggle="dropdown"
                                    >
                                        <i className="fa fa-filter"></i>
                                    </button>
                                    <span className="ml-2" style={{
                                        padding: "6px",
                                        border: "1px solid #ccc",
                                        borderRadius: "5px"
                                    }}>{filter || t("admin.leads.All")}</span>

                                    <div className="dropdown-menu dropdown-menu-right">
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                setFilter("All");
                                            }}
                                        >
                                            {t("admin.leads.All")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                setFilter("Sent");
                                            }}
                                        >
                                            {t("global.sent")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                setFilter("Accepted");
                                            }}
                                        >
                                            {t("modal.accepted")}
                                        </button>
                                        <button
                                            className="dropdown-item"
                                            onClick={() => {
                                                setFilter("Declined");
                                            }}
                                        >
                                            {t("admin.schedule.options.meetingStatus.Declined")}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-sm-6 hidden-xl mt-4">
                            <select
                                className="form-control"
                                onChange={(e) => sortTable(e.target.value)}
                            >
                                <option value="">{t("admin.leads.Options.sortBy")}</option>
                                <option value="5">{t("client.dashboard.total")}</option>
                                <option value="4">{t("client.dashboard.status")}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div className="d-none d-lg-block">
                    <div className="row">
                        <div
                            style={{
                                fontWeight: "bold",
                                marginTop: 10,
                                marginLeft: 15,
                            }}
                        >
                            {t("global.filter")}
                        </div>
                        <div>
                            <FilterButtons
                                text={t("admin.global.All")}
                                className="px-3 mr-1 ml-4"
                                selectedFilter={filter}
                                setselectedFilter={setFilter}
                            />
                            {Object.entries(offerStatuses).map(([key, value]) => (
                                <FilterButtons
                                    text={value}
                                    name={key}
                                    className="px-3 mr-1"
                                    key={key}
                                    selectedFilter={filter}
                                    setselectedFilter={(status) => setFilter(status)}
                                />
                            ))}
                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table table-bordered"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <Modal
                size="md"
                className="modal-container"
                show={statusModal}
                onHide={() => setStatusModal(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Change Status</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">{t("global.status")}</label>

                                <select
                                    name="status"
                                    onChange={(e) => setStatus(e.target.value)}
                                    value={status}
                                    className="form-control mb-3"
                                >
                                    <option value="">---select status---</option>
                                    {Object.keys(statusArr).map((s) => (
                                        <option key={s} value={s}>
                                            {statusArr[s]}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => setStatusModal(false)}
                    >
                        {t("modal.close")}
                    </Button>
                    <Button
                        type="button"
                        onClick={handleUpdateStatus}
                        className="btn btn-primary"
                    >
                        {t("global.send")}
                    </Button>
                </Modal.Footer>
            </Modal>

            {isModalOpen && <ViewOfferModal showModal={isModalOpen} handleClose={() => setModalStatus(false)} offerId={selectedOfferId} />}
        </div>
    );
}
