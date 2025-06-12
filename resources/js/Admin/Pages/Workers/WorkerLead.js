import React, { useState, useEffect, useRef } from "react";
import { useNavigate, Link, useParams } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import { Button, Modal } from "react-bootstrap";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import FilterButtons from "../../../Components/common/FilterButton";
import Sidebar from "../../Layouts/Sidebar";
import { leadStatusColor } from "../../../Utils/client.utils";

export default function WorkerLead() {
    const { t, i18n } = useTranslation();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [isOpen, setIsOpen] = useState(false);
    const [filter, setFilter] = useState("All");
    const [subFilter, setSubFilter] = useState("All");
    const [workerLeadId, setWorkerLeadId] = useState(null);
    const [status, setStatus] = useState("pending");
    const [notHiredStatus, setNotHiredStatus] = useState("construction visa");
    const tableRef = useRef(null);
    const filterRef = useRef(filter);
    const subFilterRef = useRef(subFilter);
    const [sources, setSources] = useState([]);
    const [source, setSource] = useState("");
    const [email, setEmail] = useState("");
    const [paymentPerHour, setPaymentPerHour] = useState("");
    const [manpowerCompanies, setManpowerCompanies] = useState([]);
    const sourceRef = useRef(source);
    const [formValues, setFormValues] = useState({
        email: "",

        payment_per_hour: "",

        company_type: "my-company",
        manpower_company_id: "",
    });
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const leaveStatuses = {
        pending: t("admin.leads.Pending"),
        irrelevant: t("admin.leads.Irrelevant"),
        rejected: t("admin.leads.Rejected"),
        "will-think": t("admin.leads.Will_think"),
        unanswered: t("admin.leads.Unanswered"),
        hiring: t("admin.leads.Hiring"),
        "not-hired": t("admin.leads.Not_hired"),
        "Not respond to bot": t("admin.leads.not_respond_to_bot"),
        "Not respond to messages": t("admin.leads.not_respond_to_messages"),
    };
    const statusArr = {
        pending: "pending",
        rejected: "rejected",
        irrelevant: "irrelevant",
        unanswered: "unanswered",
        hiring: "hiring",
        "will-think": "will-think",
        "not-hired": "not-hired",
        "Not respond to bot": "Not respond to bot",
        "Not respond to messages": "Not respond to messages",
    };

    const notHiredSubStatus = {
        "construction visa": "construction visa",
        "caregiver visa": "caregiver visa",
        "agriculture visa": "agriculture visa",
        "hotel sector": "hotel sector",
        "Tied to employer": "Tied to employer",
        expired: "expired",
        other: "other",
    };
    const getManpowerCompanies = async () => {
        await axios
            .get("/api/admin/manpower-companies-list", {
                headers,
            })
            .then((response) => {
                if (response?.data?.companies?.length > 0) {
                    setManpowerCompanies(response.data.companies);
                } else {
                    setManpowerCompanies([]);
                }
            });
    };
    useEffect(() => {
        getManpowerCompanies();
    }, []);
    const toggleChangeStatusModal = (_id) => {
        setIsOpen(!isOpen);
        setWorkerLeadId(_id);
    };
    useEffect(() => {
        if (workerLeadId && isOpen) {
            getWorker();
        }
    }, [workerLeadId, isOpen]);
    const [errors, setErrors] = useState([]);
    const handleChangeStatus = async () => {
        setLoading(true);
        try {
            const response = await axios.post(
                `/api/admin/worker-leads/${workerLeadId}/status`,
                {
                    status,
                    sub_status: notHiredStatus,
                    email: formValues.email,
                    payment_per_hour: formValues.payment_per_hour,
                    company_type: formValues.company_type,
                    ...(formValues.company_type === "manpower" && {
                        manpower_company_id: formValues.manpower_company_id,
                    }),
                },
                { headers }
            );
            setLoading(false);
            setErrors(response.data.errors);
            if (!response.data.errors) {
                setIsOpen(false);
            }

            $(tableRef.current).DataTable().ajax.reload();
        } catch (error) {
            console.error(error);
        }
    };

    const getUniqueSource = async () => {
        await axios
            .get("/api/admin/worker-leads/get-unique-source", {
                headers,
            })
            .then((response) => {
                if (response?.data?.sources?.length > 0) {
                    setSources(response.data.sources);
                } else {
                    setSources([]);
                }
            });
    };

    const getWorker = () => {
        axios
            .get(`/api/admin/worker-leads/${workerLeadId}/edit`, { headers })
            .then((response) => {
                const worker = response.data;
                setFormValues({
                    ...formValues,

                    email: worker.email,
                    payment_per_hour: worker.hourly_rate,
                    company_type: worker.company_type || "my-company", 
                    manpower_company_id: worker.manpower_company_id,
                });
            });
    };
    useEffect(() => {
        getUniqueSource();
    }, []);

    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            const table = $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/worker-leads",
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                    data: function (d) {
                        d.status =
                            filterRef.current === "All"
                                ? null
                                : filterRef.current; // Use ref here
                        d.sub_status =
                            subFilterRef.current === "All"
                                ? null
                                : subFilterRef.current; // Use ref here
                        d.source = sourceRef.current;
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    { title: "ID", data: "id", visible: false },
                    {
                        title: t("global.date"),
                        data: "created_at",
                        responsivePriority: 1,
                        render: function (data) {
                            return `${data ? data : null}`;
                        },
                        width: "10%",
                    },
                    {
                        title: t("admin.global.Name"),
                        data: "name",
                        render: function (data) {
                            return `${data ? data : null}`;
                        },
                    },
                    {
                        title: t("admin.global.Email"),
                        data: "email",
                        render: function (data) {
                            return `${data ? data : null}`;
                        },
                    },
                    {
                        title: t("admin.global.Phone"),
                        data: "phone",
                        render: function (data) {
                            return `+${data}`;
                        },
                    },
                    {
                        title: "Source",
                        data: "source",
                        render: function (data) {
                            return `${data ? data : "-"}`;
                        },
                    },
                    // {
                    //     title: t("admin.global.Status"),
                    //     data: "status",
                    //     render: function (data) {
                    //         const _statusColor = leadStatusColor(data);
                    //         return `<p style="background-color: ${_statusColor.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 100px; text-align: center;">
                    //         ${data}
                    //     </p>`;
                    //     },
                    // },
                    {
                        title: t("admin.global.Status"),
                        data: "status",
                        render: function (data, type, row) {
                            const _statusColor = leadStatusColor(data);
                            return `<p class="status-clickable" data-id="${row.id}" 
                                       style="cursor: pointer; background-color: ${_statusColor.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 100px; text-align: center;">
                                    ${data}
                                </p>`;
                        },
                    },

                    {
                        title: t("admin.global.action"),
                        data: null,
                        orderable: false,
                        responsivePriority: 1,
                        render: function (data, type, row, meta) {
                            return `
                                <div class="action-dropdown dropdown"> 
                                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> 
                                        <i class="fa fa-ellipsis-vertical"></i> 
                                    </button> 
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <button type="button" class="dropdown-item dt-edit-btn" data-id="${
                                            row.id
                                        }">${t("admin.leads.Edit")}</button>
                                        <button type="button" class="dropdown-item dt-view-btn" data-id="${
                                            row.id
                                        }">${t("admin.leads.view")}</button>
                                        <button type="button" class="dropdown-item dt-change-status-btn" data-id="${
                                            row.id
                                        }">${t(
                                "admin.leads.change_status"
                            )}</button>
                                        <button type="button" class="dropdown-item dt-delete-btn" data-id="${
                                            row.id
                                        }">${t("admin.leads.Delete")}</button>
                                    </div> 
                                </div>`;
                        },
                    },
                ],
                ordering: true,
                searching: true,
                responsive: true,
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
                // columnDefs: [
                //     {
                //         targets: 3,
                //         className: "text-left",
                //     },
                //     {
                //         targets: "_all",
                //         createdCell: function (
                //             td,
                //             cellData,
                //             rowData,
                //             row,
                //             col
                //         ) {
                //             $(td).addClass("custom-cell-class");
                //         },
                //     },
                // ],
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
            navigate(`/admin/worker-leads/edit/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/worker-leads/view/${_id}`);
        });

        // $(tableRef.current).on("click", ".dt-change-status-btn", function () {
        //     const _id = $(this).data("id");
        //     toggleChangeStatusModal(_id);
        // });
        $(tableRef.current).on(
            "click",
            ".dt-change-status-btn, .status-clickable",
            function () {
                const _id =
                    $(this).data("id") ||
                    $(this)
                        .closest("tr")
                        .find(".dt-change-status-btn")
                        .data("id");
                toggleChangeStatusModal(_id);
            }
        );

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
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
                // $(tableRef.current).off("click");
                $(tableRef.current).off("page.dt");
            }
        };
    }, []);

    useEffect(() => {
        filterRef.current = filter; // Update the ref with the latest filter
        subFilterRef.current = subFilter; // Update the ref with the latest subFilter
        sourceRef.current = source;
        const table = $(tableRef.current).DataTable();
        table.ajax.reload(null, false); // Reload the table without resetting pagination
        table.columns.adjust().draw(); // This forces a redraw to fix the column shifting issue
    }, [filter, subFilter, source]);

    const handleDelete = (id) => {
        Swal.fire({
            title: t("Are you sure?"),
            text: t("You won't be able to revert this!"),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: t("Yes, delete it!"),
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/worker-leads/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            t("Deleted!"),
                            t("Your file has been deleted."),
                            "success"
                        );
                        $(tableRef.current).DataTable().ajax.reload(); // Reload DataTable
                    })
                    .catch((error) => {
                        Swal.fire(
                            t("Error!"),
                            t("There was an error deleting the record."),
                            "error"
                        );
                    });
            }
        });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex justify-content-between">
                        <div className="">
                            <h1 className="page-title">
                                {t("worker.leaveRequest")}
                            </h1>
                        </div>
                        <div className="">
                            <Link
                                to="/admin/worker-leads/add"
                                className="btn navyblue align-content-center addButton no-hover"
                            >
                                <i className="btn-icon fas fa-plus-circle"></i>
                                {t("admin.client.AddNew")}
                            </Link>
                        </div>
                    </div>
                </div>
                <div
                    className="dashBox pt-0 pb-4"
                    style={{ backgroundColor: "inherit", border: "none" }}
                >
                    <div className="row d-flex flex-column">
                        <div
                            style={{
                                fontWeight: "bold",
                                marginTop: 10,
                                marginLeft: 15,
                            }}
                        >
                            {t("global.filter")}
                        </div>
                        <div className="pl-3 mt-1">
                            <FilterButtons
                                text={t("admin.global.All")}
                                className="px-3 mr-1"
                                selectedFilter={filter}
                                setselectedFilter={setFilter}
                            />
                            {Object.entries(leaveStatuses).map(
                                ([key, value]) => (
                                    <FilterButtons
                                        text={value}
                                        name={key}
                                        className="px-3 mr-1"
                                        key={key}
                                        selectedFilter={filter}
                                        setselectedFilter={(status) =>
                                            setFilter(status)
                                        }
                                    />
                                )
                            )}
                        </div>
                    </div>
                    {filter == "not-hired" && (
                        <div className="row mt-2">
                            <div
                                style={{
                                    fontWeight: "bold",
                                    marginTop: 10,
                                    marginLeft: 15,
                                }}
                            >
                                {t("global.sub_filter")}
                            </div>
                            <div>
                                <FilterButtons
                                    text={t("admin.global.All")}
                                    className="px-3 mr-1 ml-4"
                                    selectedFilter={subFilter}
                                    setselectedFilter={setSubFilter}
                                />
                                {Object.entries(notHiredSubStatus).map(
                                    ([key, value]) => (
                                        <FilterButtons
                                            text={value}
                                            name={key}
                                            className="px-3 mr-1"
                                            key={key}
                                            selectedFilter={subFilter}
                                            setselectedFilter={(status) =>
                                                setSubFilter(status)
                                            }
                                        />
                                    )
                                )}
                            </div>
                        </div>
                    )}

                    {sources.length > 0 && (
                        <div className="col-sm-3 px-0 mt-2">
                            <div
                                style={{
                                    fontWeight: "bold",
                                    marginTop: 10,
                                }}
                            >
                                {t("worker.source")}
                            </div>
                            <select
                                className="form-control"
                                onChange={(e) => setSource(e.target.value)}
                                value={source}
                            >
                                <option value="">--- Select ---</option>
                                {sources.length > 0 &&
                                    sources.map((source) => (
                                        <option value={source} key={source}>
                                            {source}
                                        </option>
                                    ))}
                            </select>
                        </div>
                    )}
                    <div
                        className="dashBox pt-4 pb-4 w-100"
                        style={{
                            backgroundColor: "inherit",
                            border: "none",
                            overflowX: "auto",
                        }}
                    >
                        <table
                            ref={tableRef}
                            className="display table table-bordered w-100"
                        />
                    </div>
                </div>
                {loading && <FullPageLoader visible={loading} />}
            </div>
            <Modal
                size="md"
                className="modal-container"
                show={isOpen}
                onHide={() => setIsOpen(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Change status</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">Status</label>

                                <select
                                    name="status"
                                    onChange={(e) => setStatus(e.target.value)}
                                    value={status}
                                    className="form-control mb-3"
                                >
                                    {Object.keys(statusArr).map((s) => (
                                        <option key={s} value={s}>
                                            {statusArr[s]}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        {status == "not-hired" && (
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">
                                        Sub Status
                                    </label>

                                    <select
                                        name="status"
                                        onChange={(e) =>
                                            setNotHiredStatus(e.target.value)
                                        }
                                        value={notHiredStatus}
                                        className="form-control mb-3"
                                    >
                                        {Object.keys(notHiredSubStatus).map(
                                            (s) => (
                                                <option key={s} value={s}>
                                                    {notHiredSubStatus[s]}
                                                </option>
                                            )
                                        )}
                                    </select>
                                </div>
                            </div>
                        )}

                        {status == "hiring" && (
                            <>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Email
                                        </label>
                                        <input
                                            type="email"
                                            className="form-control mb-3"
                                            value={formValues.email}
                                            onChange={(e) =>
                                                setFormValues({
                                                    ...formValues,
                                                    email: e.target.value,
                                                })
                                            }
                                            placeholder="Enter email"
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Payment Per Hour
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control mb-3"
                                            value={formValues.payment_per_hour}
                                            onChange={(e) =>
                                                setFormValues({
                                                    ...formValues,
                                                    payment_per_hour:
                                                        e.target.value,
                                                })
                                            }
                                            placeholder="Enter payment per hour"
                                        />
                                    </div>
                                    <div>
                                        {errors.payment_per_hour ? (
                                            <small className="text-danger mb-1">
                                                {errors.payment_per_hour}
                                            </small>
                                        ) : (
                                            ""
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("global.company")}
                                        </label>

                                        <div className="d-flex flex-nowrap align-items-center gap-3">
                                            <div
                                                className="form-check"
                                                style={{ whiteSpace: "nowrap" }}
                                            >
                                                <label className="form-check-label">
                                                    <input
                                                        type="radio"
                                                        className="form-check-input"
                                                        value="my-company"
                                                        onChange={(e) => {
                                                            setFormValues({
                                                                ...formValues,
                                                                company_type:
                                                                    e.target
                                                                        .value,
                                                                manpower_company_id:
                                                                    "",
                                                            });
                                                        }}
                                                        checked={
                                                            formValues.company_type ===
                                                            "my-company"
                                                        }
                                                    />
                                                    {t(
                                                        "admin.global.myCompany"
                                                    )}
                                                </label>
                                            </div>
                                            <div
                                                className="form-check"
                                                style={{ whiteSpace: "nowrap" }}
                                            >
                                                <label className="form-check-label">
                                                    <input
                                                        type="radio"
                                                        className="form-check-input"
                                                        value="manpower"
                                                        onChange={(e) => {
                                                            setFormValues({
                                                                ...formValues,
                                                                company_type:
                                                                    e.target
                                                                        .value,
                                                            });
                                                        }}
                                                        checked={
                                                            formValues.company_type ===
                                                            "manpower"
                                                        }
                                                    />
                                                    {t("admin.global.manpower")}
                                                </label>
                                            </div>
                                            <div
                                                className="form-check"
                                                style={{ whiteSpace: "nowrap" }}
                                            >
                                                <label className="form-check-label">
                                                    <input
                                                        type="radio"
                                                        className="form-check-input"
                                                        value="freelancer"
                                                        onChange={(e) => {
                                                            setFormValues({
                                                                ...formValues,
                                                                company_type:
                                                                    e.target
                                                                        .value,
                                                            });
                                                        }}
                                                        checked={
                                                            formValues.company_type ===
                                                            "freelancer"
                                                        }
                                                    />
                                                    {t(
                                                        "admin.global.freelancer"
                                                    )}
                                                </label>
                                            </div>
                                        </div>

                                        <div>
                                            {errors.company_type ? (
                                                <small className="text-danger mb-1">
                                                    {errors.company_type}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {formValues.company_type === "manpower" && (
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("admin.global.manpower")}
                                            </label>

                                            <select
                                                name="manpower-id"
                                                className="form-control"
                                                value={
                                                    formValues.manpower_company_id
                                                }
                                                onChange={(e) =>
                                                    setFormValues({
                                                        ...formValues,
                                                        manpower_company_id:
                                                            e.target.value,
                                                    })
                                                }
                                            >
                                                <option value="">
                                                    {t(
                                                        "admin.global.select_manpower"
                                                    )}
                                                </option>
                                                {manpowerCompanies.map(
                                                    (mpc, index) => (
                                                        <option
                                                            value={mpc.id}
                                                            key={mpc.id}
                                                        >
                                                            {mpc.name}
                                                        </option>
                                                    )
                                                )}
                                            </select>
                                        </div>
                                        <div>
                                            {errors.manpower_company_id ? (
                                                <small className="text-danger mb-1">
                                                    {errors.manpower_company_id}
                                                </small>
                                            ) : (
                                                ""
                                            )}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => setIsOpen(false)}
                    >
                        Close
                    </Button>
                    <Button
                        type="button"
                        onClick={handleChangeStatus}
                        className="btn btn-primary"
                    >
                        Save
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    );
}
