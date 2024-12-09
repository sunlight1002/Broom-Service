import React, { useState, useEffect, useRef } from "react";
import { useNavigate, Link } from "react-router-dom";
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

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const leaveStatuses = ["pending", "completed", "approved"];
    const userType = ["Client", "Worker"];

    const statusArr = {
        "pending": "pending",
        "completed": "completed",
        "approved": "approved",
    };

    const toggleChangeStatusModal = (_id) => {
        console.log(_id);

        setIsOpen(!isOpen)
        setUserId(_id)
    }


    const handleChangeStatus = async () => {
        setLoading(true)
        try {
            const response = await axios.put(`/api/admin/schedule-changes/${userId}`, { status }, { headers });
            console.log(response.data);

            setLoading(false)
            setIsOpen(false)
            $(tableRef.current).DataTable().ajax.reload();
        } catch (error) {
            console.error(error);
        }
    }

    const initializeDataTable = (initialPage = 0) => {
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                stateSave: true,
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
                    { title: "User Type", data: "user_type" },
                    { title: "User Name", data: "user_fullname" },
                    { title: "Comments", data: "comments" },
                    {
                        title: "Status",
                        data: "status",
                        render: function (data) {
                            const style = leadStatusColor(data);
                            return `<p style="background-color: ${style.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
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
                                        <button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>
                                        <button type="button" class="dropdown-item dt-change-status-btn" data-id="${row.id}">${t("admin.leads.change_status")}</button>
                                    </div> 
                                </div>`;
                        }

                    },
                ],
                ordering: true,
                searching: true,
                responsive: true,
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

        $(tableRef.current).on("click", ".dt-change-status-btn", function () {
            const _id = $(this).data("id");
            toggleChangeStatusModal(_id);
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
                            <h1 className="page-title">Pending Request</h1>
                        </div>
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
    )
}

export default ScheduleChange