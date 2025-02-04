import React, { useState, useEffect, useRef } from "react";
import { useNavigate, Link } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import { Button, Modal } from "react-bootstrap";
import moment from "moment";
import { Tooltip } from "react-tooltip";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import FilterButtons from "../../../Components/common/FilterButton";
import Sidebar from "../../Layouts/Sidebar";
import { leadStatusColor } from "../../../Utils/client.utils";

function ScheduleChange() {
    const { t, i18n } = useTranslation();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [isOpen, setIsOpen] = useState(false)
    const [filter, setFilter] = useState("All");
    const [userId, setUserId] = useState(null);
    const [status, setStatus] = useState("pending")
    const [type, setType] = useState("Both")
    const tableRef = useRef(null);
    const filterRef = useRef(filter);
    const typeRef = useRef(type);
    const [schedule, setSchedule] = useState([])
    const [adminMessage, setAdminMessage] = useState({
        reason: "",
        message: ""
    })


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const leaveStatuses = ["pending", "completed"];
    const userType = ["Client", "Worker"];

    const statusArr = {
        "pending": "pending",
        "completed": "completed",
    };

    const toggleChangeStatusModal = (_id) => {
        setIsOpen(!isOpen)
        setUserId(_id)
        getRequest(_id)
    }


    const handleChangeStatus = async (userId, e) => {
        setLoading(true)
        try {
            const response = await axios.put(`/api/admin/schedule-changes/${userId}`, { status: e ? "completed" : "pending" }, { headers });
            setLoading(false)
            setIsOpen(false)
            $(tableRef.current).DataTable().ajax.reload();
        } catch (error) {
            console.error(error);
        }
    }

    const getRequest = async (id) => {
        const response = await axios.get(`/api/admin/schedule-change/${id}`, { headers })
        setSchedule(response.data?.scheduleChange);
        setAdminMessage({
            ...adminMessage,
            reason: response.data?.scheduleChange?.reason,
        })
    };

    const handleSendMessage = async () => {
        const data = {
            user_id: userId,
            message: adminMessage.message,
            reason: adminMessage.reason,
        }
        try {
            const response = await axios.post(`/api/admin/send-message-to-user/${userId}`, data, { headers });
            setIsOpen(false)
            setAdminMessage({
                ...adminMessage,
                message: "",
            })
        } catch (error) {
            console.error(error);
        }
    }

    const initializeDataTable = (initialPage = 0) => {
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                // autoWidth: false,
                // stateSave: true,
                ajax: {
                    url: "/api/admin/schedule-changes",
                    type: "GET",
                    headers: headers,
                    data: function (d) {
                        d.status = filterRef.current === "All" ? null : filterRef.current; // Use ref here
                        d.type = typeRef.current === "Both" ? null : typeRef.current; // Use ref for type here
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: t("global.Type"),
                        data: "user_type",
                        className: "text-center",
                        render: function (data) {
                            if (data === "Client") {
                                return `<span class="">C</span>`;
                            } else if (data === "Worker") {
                                return `<span class="">W</span>`;
                            }
                        },
                    },
                    {
                        title: t("global.name"),
                        data: "user_fullname",
                        className: "cursor-pointer text-center",
                        width: "20%",
                        render: function (data, type, row, meta) {
                            const firstname = data.split(" ")[0];
                            const lastname = data.split(" ")[1];
                            return `<div class="dt-user-name-btn cursor-pointer" data-id="${row.user_id}"><p 
                                        data-tooltip-id="comment" 
                                        data-tooltip-html="${data}">
                                        ${data}
                                    </p></div>`;
                        },

                    },
                    {
                        title: t("global.reason"),
                        data: "reason",
                        className: "text-center",
                        render: function (data) {
                            const first = data.indexOf(" ") === -1 ? data : data.split(" ")[0];
                            return `<p 
                                    class="badge" 
                                    data-tooltip-id="comment" 
                                    data-tooltip-html="${data}">
                                    ${first}...
                                </p>`;
                        },
                    },
                    {
                        title: t("global.comments"),
                        data: "comments",
                        className: "text-center",
                        width: "35%",
                        render: function (data) {
                            const first = data.indexOf(" ") === -1 ? data : data.split(" ")[0];
                            return `<p 
                                        class=" dt-comment-btn text-start" 
                                        data-tooltip-id="comment" 
                                        data-tooltip-html="${data}">
                                        ${data}
                                    </p>`;
                        },

                    },
                    {
                        title: "Send Notification",
                        data: null,
                        className: "text-center",
                        render: function (data, type, row, meta) {
                            if(row.user_type == "Client"){
                                return `<div class="d-flex justify-content-center dt-date-wabtn" data-id="${row.id}"><button type="button" class="rounded" style="border: 1px solid #ebebeb; overflow: hidden; "><i class="fa-brands fa-whatsapp font-20"></i></button></div>`;
                            }else{
                                return "";
                            }
                        },
                    },
                    {
                        title: t("global.is_completed"),
                        data: "status",
                        className: "text-center",
                        width: "10%",
                        render: function (data, type, row, meta) {
                            return `<div class="d-flex justify-content-center "><span class="rounded" style="border: 1px solid #ebebeb; overflow: hidden; "> <input type="checkbox" data-id="${row.id
                                }" class="form-control dt-if-completed-checkbox" style="cursor: pointer; margin: 5px 5px;" ${row.status == "completed" ? "checked" : ""
                                }/> </span></div> `;
                        },
                    },
                    {
                        title: t("modal.date"),
                        data: "created_at",
                        className: "text-center",
                        width: "5%",
                        render: function (data) {
                            return `<p 
                                        class="badge dt-date-btn" 
                                        data-tooltip-id="comment" 
                                        data-tooltip-html="${moment(data).format("DD-MM-YYYY HH:mm")}">
                                        ${moment(data).format("DD-MM")}
                                    </p>`;
                        },
                    },
                    {
                        title: t("admin.global.action"),
                        data: null,
                        className: "text-center",
                        width: "5%",
                        render: function (data, type, row, meta) {
                            return `
                                <div class="action-dropdown dropdown"> 
                                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> 
                                        <i class="fa fa-ellipsis-vertical"></i> 
                                    </button> 
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>
                                        
                                    </div> 
                                </div>`;
                        }

                    },
                ],
                searching: true,
                scrollX: true, // Ensures horizontal scrolling
                autoWidth: false, // Prevents automatic width issues
                width: "100% !important",
                drawCallback: function () {
                    // initializeTableActions();
                    setLoading(false); // Hide loader when data is loaded
                },
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

        $(tableRef.current).on("click", ".dt-user-name-btn", function (e) {
            console.log("Button clicked!");

            const _id = $(this).data("id");
            console.log("ID:", _id);

            if (_id) {
                navigate(`/admin/clients/view/${_id}`);
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


        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/schedule-requests/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-date-wabtn", function () {
            const _id = $(this).data("id");
            toggleChangeStatusModal(_id);
        });

        $(tableRef.current).on(
            "change",
            ".dt-if-completed-checkbox",
            function () {
                const _id = $(this).data("id");
                handleChangeStatus(_id, this.checked);
            }
        );

        // $(tableRef.current).on("click", ".dt-change-status-btn", function () {
        //     const _id = $(this).data("id");
        //     toggleChangeStatusModal(_id);
        // });

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

    useEffect(() => {
        filterRef.current = filter; // Update the ref with the latest filter
        typeRef.current = type; // Update the ref with the latest type

        const table = $(tableRef.current).DataTable();
        table.ajax.reload(null, false); // Reload the table without resetting pagination
        table.columns.adjust().draw();  // This forces a redraw to fix the column shifting issue

    }, [filter, type]);


    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex justify-content-between">
                        <div className="">
                            <h1 className="page-title">{t("admin.sidebar.pending_request")}</h1>
                        </div>
                        <Link
                            to="/admin/add-schedule-requests"
                            className="btn navyblue align-content-center addButton no-hover"
                        >
                            <i className="btn-icon fas fa-plus-circle"></i>
                            {t("admin.client.AddNew")}
                        </Link>
                    </div>
                </div>
                <div className="dashBox pt-4 pb-4" style={{ backgroundColor: "inherit", border: "none" }}>
                    <div className="row">
                        <div style={{ fontWeight: "bold", marginTop: 10, marginLeft: 15 }}>
                            {t("global.filter")}
                        </div>
                        <div>
                            <FilterButtons
                                text={t("admin.global.All")}
                                className="px-3 mr-1 ml-4"
                                selectedFilter={filter}
                                setselectedFilter={setFilter}
                            />
                            {leaveStatuses.map((status, index) => (
                                <FilterButtons
                                    text={status}
                                    className="mr-1 px-3 ml-2"
                                    key={index}
                                    selectedFilter={filter}
                                    setselectedFilter={setFilter}
                                />
                            ))}
                        </div>
                    </div>
                    <div className="row mt-3">
                        <div style={{ fontWeight: "bold", marginTop: 10, marginLeft: 15 }}>
                            Type
                        </div>
                        <div>
                            <FilterButtons
                                text={"Both"}
                                className="px-3 mr-1 ml-4"
                                selectedFilter={type}
                                setselectedFilter={setType}
                            />
                            {userType.map((user, index) => (
                                <FilterButtons
                                    text={user}
                                    className="mr-1 px-3 ml-2"
                                    key={index}
                                    selectedFilter={type}
                                    setselectedFilter={setType}
                                />
                            ))}
                        </div>
                    </div>
                    <div className="dashBox pt-4 pb-4 w-100" style={{ backgroundColor: "inherit", border: "none", overflowX: "auto" }}>
                        <table ref={tableRef} className="display table table-bordered w-100" />
                    </div>
                </div>
            </div>
            <Modal
                size="md"
                className="modal-container"
                show={isOpen}
                onHide={() => setIsOpen(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Send Message</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">Reason</label>
                                <input
                                    name="reason"
                                    type="text"
                                    defaultValue={schedule.reason}
                                    value={adminMessage.reason || ""}
                                    onChange={(e) => setAdminMessage({
                                        ...adminMessage,
                                        reason: e.target.value
                                    })}
                                    className="form-control"
                                />
                            </div>
                        </div>
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">Comment</label>
                                <textarea
                                    type="text"
                                    name="other_title"
                                    className="form-control"
                                    value={adminMessage.message || ""}
                                    onChange={(e) => setAdminMessage({
                                        ...adminMessage,
                                        message: e.target.value
                                    })}
                                />
                            </div>
                        </div>
                    </div>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => setIsOpen(false)}
                    >
                        {t("modal.close")}
                    </Button>
                    <Button
                        type="button"
                        onClick={handleSendMessage}
                        className="btn btn-primary"
                    >
                        {t("global.send")}
                    </Button>
                </Modal.Footer>
            </Modal>
            <Tooltip id="comment" place="top" type="dark" effect="solid" style={{ zIndex: "99999" }} />
        </div>
    )
}

export default ScheduleChange