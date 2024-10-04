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

export default function WorkerLead() {
    const { t, i18n } = useTranslation();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [isOpen, setIsOpen] = useState(false)
    const [filter, setFilter] = useState("All");
    const [workerLeadId, setWorkerLeadId] = useState(null);
    const [status, setStatus] = useState("pending")
    const tableRef = useRef(null);
    const filterRef = useRef(filter);     

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const leaveStatuses = ["pending", "irrelevant", "rejected", "unanswered"];
    const statusArr = {
        "pending": "pending",
        "rejected": "rejected",
        "irrelevant": "irrelevant",
        "unanswered": "unanswered"
    };

    const toggleChangeStatusModal = (_id) => {
        setIsOpen(!isOpen)
        setWorkerLeadId(_id)
    }
    
    

    const handleChangeStatus = async () => {
        setLoading(true)
        try {
            const response = await axios.post(`/api/admin/worker-leads/${workerLeadId}/status`, { status }, { headers });
            setLoading(false)
            setIsOpen(false)
            $(tableRef.current).DataTable().ajax.reload();
        } catch (error) {
            console.error(error);
        }
    }

    const initializeDataTable = () => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
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
                        d.status = filterRef.current === "All" ? null : filterRef.current; // Use ref here
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    { title: "ID", data: "id", visible: false },
                    { title: t("admin.global.Name"), data: "name" },
                    {
                        title: t("admin.global.Email"), data: "email",
                        render: function (data) {
                            return `${data ? data : null}`;
                        },
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
                        render: function (data) {
                            const _statusColor = leadStatusColor(data);
                            return `<p style="background-color: ${_statusColor.backgroundColor}; color: white; padding: 5px 10px; border-radius: 5px; width: 100px; text-align: center;">
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
                                        <button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">${t('admin.leads.Edit')}</button>
                                        <button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>
                                        <button type="button" class="dropdown-item dt-change-status-btn" data-id="${row.id}">${t("admin.leads.change_status")}</button>
                                        <button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>
                                    </div> 
                                </div>`;
                        }

                    },
                ],
                ordering: true,
                searching: true,
                responsive: true,
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
                columnDefs: [
                    {
                        targets: 3,
                        className: 'text-left'
                    },
                    {
                        targets: '_all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass('custom-cell-class');
                        }
                    }
                ]
            });
        }
    };


    useEffect(() => {
        initializeDataTable();

        // Customize the search input
        const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
        $("div.dt-search").append(searchInputWrapper);
        $("div.dt-search").addClass("position-relative");


        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/worker-leads/edit/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/worker-leads/view/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-change-status-btn", function () {
            const _id = $(this).data("id");
            toggleChangeStatusModal(_id);
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        // Handle language changes
        i18n.on("languageChanged", () => {
            $(tableRef.current).DataTable().destroy(); // Destroy the table
            initializeDataTable(); // Reinitialize the table with updated language
        });

        // Cleanup event listeners and destroy DataTable when unmounting
        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true); // Ensure proper cleanup
                $(tableRef.current).off("click");
            }
        };
    }, []);

    useEffect(() => {
        filterRef.current = filter; // Update the ref with the latest filter
        const table = $(tableRef.current).DataTable();
        table.ajax.reload(null, false); // Reload the table without resetting pagination
        table.columns.adjust().draw();  // This forces a redraw to fix the column shifting issue

    }, [filter]);

    const handleDelete = (id) => {
        Swal.fire({
            title: t("Are you sure?"),
            text: t("You won't be able to revert this!"),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: t("Yes, delete it!")
        }).then((result) => {
            if (result.isConfirmed) {
                axios.delete(`/api/admin/worker-leads/${id}`, { headers })
                    .then(response => {
                        Swal.fire(t("Deleted!"), t("Your file has been deleted."), 'success');
                        $(tableRef.current).DataTable().ajax.reload(); // Reload DataTable
                    })
                    .catch(error => {
                        Swal.fire(t("Error!"), t("There was an error deleting the record."), 'error');
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
                            <h1 className="page-title">{t("worker.leaveRequest")}</h1>
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
                    <div className="dashBox pt-4 pb-4 w-100" style={{ backgroundColor: "inherit", border: "none", overflowX: "auto" }}>
                        <table ref={tableRef} className="display table table-bordered w-100" />
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




