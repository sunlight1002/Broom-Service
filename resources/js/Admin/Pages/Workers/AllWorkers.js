import React, { useState, useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { useTranslation } from "react-i18next";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import LeaveJobWorkerModal from "../../Components/Modals/LeaveJobWorkerModal";

export default function AllWorkers() {
    const { t, i18n } = useTranslation()
    const [isOpenLeaveJobWorker, setIsOpenLeaveJobWorker] = useState(false);
    const [selectedWorkerId, setSelectedWorkerId] = useState(null);
    const [filters, setFilters] = useState({
        status: "",
        manpower_company_id: "",
        is_my_company: false,
    });
    const [manpowerCompanies, setManpowerCompanies] = useState([]);
    const [show, setShow] = useState(false);
    const [importFile, setImportFile] = useState("");

    const alert = useAlert();
    const navigate = useNavigate();
    const tableRef = useRef(null);
    const statusRef = useRef(null);
    const manpowerCompanyRef = useRef(null);
    const isMyCompanyRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };


    const initializeDataTable = () => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/workers",
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                    data: function (d) {
                        d.status = statusRef.current.value;
                        d.manpower_company_id = manpowerCompanyRef.current.value;
                        d.is_my_company = isMyCompanyRef.current.value;
                    },
                },
                order: [[0, "desc"]],
                columns: [
                    {
                        title: "ID",
                        data: "id",
                        visible: false,
                    },
                    {
                        title: t("admin.global.Name"),
                        data: "name",
                    },
                    {
                        title: t("admin.global.Email"),
                        data: "email",
                    },
                    { 
                        title: t("admin.global.Phone"), 
                        data: "phone",
                        render: function(data) {
                            return `+${data}`;
                        }
                    },     
                    {
                        title: t("admin.global.Address"),
                        data: "address",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            if (data) {
                                return `<a href="https://maps.google.com?q=${row.address}" target="_blank" class="dt-address-link"> ${data} </a>`;
                            } else {
                                return "NA";
                            }
                        },
                    },
                    {
                        title: t("admin.global.Status"),
                        data: "status",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            // return data == 1 ? "Active" : "Inactive";
                            return data == 1 ? `<p style="background-color: #efefef; color: green; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        Active
                                    </p>` : `<p style="background-color: #efefef; color: red; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                        Inactive
                                    </p>` ;
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
    
                            _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>`;
    
                            _html += `<button type="button" class="dropdown-item dt-freeze-shift-btn" data-id="${row.id}">${t("global.freezeShift")}</button>`;
    
                            _html += `<button type="button" class="dropdown-item dt-leave-job-btn" data-id="${row.id}">${t("modal.leave_job")}</button>`;
    
                            _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>`;
    
                            _html += "</div> </div>";
    
                            return _html;
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
                columnDefs: [
                    {
                        targets: '_all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass('custom-cell-class ');
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

        $(tableRef.current).on("click", "tr.dt-row,tr.child", function (e) {
            let _id = null;
            if (e.target.closest("tr.dt-row")) {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-address-link") &&
                    (!tableRef.current.classList.contains("collapsed") ||
                        !e.target.closest(".dtr-control"))
                ) {
                    _id = $(this).data("id");
                }
            } else {
                if (
                    !e.target.closest(".dropdown-toggle") &&
                    !e.target.closest(".dropdown-menu") &&
                    !e.target.closest(".dt-address-link")
                ) {
                    _id = $(e.target).closest("tr.child").prev().data("id");
                }
            }

            if (_id) {
                navigate(`/admin/workers/view/${_id}`);
            }
        });

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/workers/edit/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/workers/view/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-freeze-shift-btn", function () {
            const _id = $(this).data("id");
            navigate(`/admin/workers/freeze-shift/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-leave-job-btn", function () {
            const _id = $(this).data("id");
            handleLeaveJob(_id);
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

    // useEffect(() => {
    //     $(tableRef.current).DataTable({
    //         processing: true,
    //         serverSide: true,
    //         ajax: {
    //             url: "/api/admin/workers",
    //             type: "GET",
    //             beforeSend: function (request) {
    //                 request.setRequestHeader(
    //                     "Authorization",
    //                     `Bearer ` + localStorage.getItem("admin-token")
    //                 );
    //             },
    //             data: function (d) {
    //                 d.status = statusRef.current.value;
    //                 d.manpower_company_id = manpowerCompanyRef.current.value;
    //                 d.is_my_company = isMyCompanyRef.current.value;
    //             },
    //         },
    //         order: [[0, "desc"]],
    //         columns: [
    //             {
    //                 title: "ID",
    //                 data: "id",
    //                 visible: false,
    //             },
    //             {
    //                 title: t("admin.global.Name"),
    //                 data: "name",
    //             },
    //             {
    //                 title: t("admin.global.Email"),
    //                 data: "email",
    //             },
    //             {
    //                 title: t("admin.global.Phone"),
    //                 data: "phone",
    //             },
    //             {
    //                 title: t("admin.global.Address"),
    //                 data: "address",
    //                 orderable: false,
    //                 render: function (data, type, row, meta) {
    //                     if (data) {
    //                         return `<a href="https://maps.google.com?q=${row.address}" target="_blank" class="dt-address-link"> ${data} </a>`;
    //                     } else {
    //                         return "NA";
    //                     }
    //                 },
    //             },
    //             {
    //                 title: t("admin.global.Status"),
    //                 data: "status",
    //                 orderable: false,
    //                 render: function (data, type, row, meta) {
    //                     // return data == 1 ? "Active" : "Inactive";
    //                     return data == 1 ? `<p style="background-color: #efefef; color: green; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
    //                                 Active
    //                             </p>` : `<p style="background-color: #efefef; color: red; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
    //                                 Inactive
    //                             </p>` ;
    //                 },
    //             },
    //             {
    //                 title: t("admin.global.Action"),
    //                 data: "action",
    //                 orderable: false,
    //                 responsivePriority: 1,
    //                 render: function (data, type, row, meta) {
    //                     let _html =
    //                         '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

    //                     _html += `<button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">${t('admin.leads.Edit')}</button>`;

    //                     _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>`;

    //                     _html += `<button type="button" class="dropdown-item dt-freeze-shift-btn" data-id="${row.id}">${t("global.freezeShift")}</button>`;

    //                     _html += `<button type="button" class="dropdown-item dt-leave-job-btn" data-id="${row.id}">${t("admin.modal.leave_job")}</button>`;

    //                     _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>`;

    //                     _html += "</div> </div>";

    //                     return _html;
    //                 },
    //             },
    //         ],
    //         ordering: true,
    //         searching: true,
    //         responsive: true,
    //         createdRow: function (row, data, dataIndex) {
    //             $(row).addClass("dt-row custom-row-class");
    //             $(row).attr("data-id", data.id);
    //         },
    //         columnDefs: [
    //             {
    //                 targets: '_all',
    //                 createdCell: function (td, cellData, rowData, row, col) {
    //                     $(td).addClass('custom-cell-class ');
    //                 }
    //             }
    //         ]
    //     });

    //     // Customize the search input
    //     const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
    //     $("div.dt-search").append(searchInputWrapper);
    //     $("div.dt-search").addClass("position-relative");

    //     $(tableRef.current).on("click", "tr.dt-row,tr.child", function (e) {
    //         let _id = null;
    //         if (e.target.closest("tr.dt-row")) {
    //             if (
    //                 !e.target.closest(".dropdown-toggle") &&
    //                 !e.target.closest(".dropdown-menu") &&
    //                 !e.target.closest(".dt-address-link") &&
    //                 (!tableRef.current.classList.contains("collapsed") ||
    //                     !e.target.closest(".dtr-control"))
    //             ) {
    //                 _id = $(this).data("id");
    //             }
    //         } else {
    //             if (
    //                 !e.target.closest(".dropdown-toggle") &&
    //                 !e.target.closest(".dropdown-menu") &&
    //                 !e.target.closest(".dt-address-link")
    //             ) {
    //                 _id = $(e.target).closest("tr.child").prev().data("id");
    //             }
    //         }

    //         if (_id) {
    //             navigate(`/admin/workers/view/${_id}`);
    //         }
    //     });

    //     $(tableRef.current).on("click", ".dt-edit-btn", function () {
    //         const _id = $(this).data("id");
    //         navigate(`/admin/workers/edit/${_id}`);
    //     });

    //     $(tableRef.current).on("click", ".dt-view-btn", function () {
    //         const _id = $(this).data("id");
    //         navigate(`/admin/workers/view/${_id}`);
    //     });

    //     $(tableRef.current).on("click", ".dt-freeze-shift-btn", function () {
    //         const _id = $(this).data("id");
    //         navigate(`/admin/workers/freeze-shift/${_id}`);
    //     });

    //     $(tableRef.current).on("click", ".dt-leave-job-btn", function () {
    //         const _id = $(this).data("id");
    //         handleLeaveJob(_id);
    //     });

    //     $(tableRef.current).on("click", ".dt-delete-btn", function () {
    //         const _id = $(this).data("id");
    //         handleDelete(_id);
    //     });

    //     return function cleanup() {
    //         $(tableRef.current).DataTable().destroy(true);
    //     };
    // }, []);

    useEffect(() => {
        $(tableRef.current).DataTable().draw();
    }, [filters]);

    const handleLeaveJob = (_workerID) => {
        setSelectedWorkerId(_workerID);
        setIsOpenLeaveJobWorker(true);
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Worker!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/workers/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Worker has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            $(tableRef.current).DataTable().draw();
                        }, 1000);
                    });
            }
        });
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

    const sortTable = (colIdx) => {
        $(tableRef.current).DataTable().order(parseInt(colIdx), "asc").draw();
    };

    const handleImportSubmit = () => {
        const formData = new FormData();
        formData.append("file", importFile);
        axios
            .post("/api/admin/workers/import", formData, {
                headers: {
                    Accept: "application/json, text/plain, */*",
                    "Content-Type": "multipart/form-data",
                    Authorization:
                        `Bearer ` + localStorage.getItem("admin-token"),
                },
            })
            .then((response) => {
                handleClose();
                if (response.data.error) {
                    alert.error(response.data.error);
                } else {
                    alert.success(response.data.message);
                    setTimeout(() => {
                        $(tableRef.current).DataTable().draw();
                    }, 1000);
                }
            })
            .catch((error) => {
                handleClose();
                alert.error(error.message);
            });
    };

    const handleClose = () => {
        setImportFile("");
        setShow(false);
    };
    const handleShow = () => {
        setImportFile("");
        setShow(true);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex justify-content-between align-items-center flex-wrap">
                        <div className=" d-flex justify-content-between mt-3">
                            <h1 className="page-title d-none d-md-block">
                                {t("admin.dashboard.workers")}
                            </h1>
                            <h1 className="page-title p-0 d-block d-md-none">
                                {t("admin.dashboard.workers")}
                            </h1>

                        </div>
                        <div className="search-data">
                            <button
                                className="btn navyblue mt-4 mr-2 no-hover"
                                onClick={handleShow}
                            >
                                {t("admin.global.Import")}
                            </button>
                            <Link
                                to="/admin/workers/working-hours"
                                className="btn navyblue addButton mr-0 mr-md-2  ml-auto no-hover"
                            >
                                {t("price_offer.worker_hours")}
                            </Link>
                            <Link
                                to="/admin/add-worker"
                                className="btn navyblue d-none d-md-block addButton no-hover"
                            >
                                <i className="btn-icon fas fa-plus-circle"></i>
                                {t("admin.leads.AddNew")}
                            </Link>
                            <Link
                                to="/admin/add-worker"
                                className="btn ml-2 navyblue d-block d-md-none addButton no-hover align-content-center"
                            >
                                <i className="btn-icon fas fa-plus-circle"></i>
                                {t("admin.leads.AddNew")}
                            </Link>
                        </div>
                    </div>
                    <div className="col-sm-6 hidden-xl mt-4">
                        <select
                            className="form-control"
                            onChange={(e) => sortTable(e.target.value)}
                        >
                            <option value="">{t("admin.leads.Options.sortBy")}</option>
                            <option value="0">{t("admin.leads.Options.ID")}</option>
                            <option value="1">{t("admin.leads.Options.Name")}</option>
                            <option value="2">{t("admin.leads.Options.Email")}</option>
                            <option value="3">{t("admin.leads.Options.Phone")}</option>
                            <option value="4">{t("admin.leads.AddLead.addAddress.Address")}</option>
                        </select>
                    </div>
                </div>
                <div className="row mb-2 d-none d-lg-block">
                    <div className="col-sm-12 d-flex align-items-center">
                        <div className="mr-3" style={{ fontWeight: "bold" }}>
                            {t("admin.global.Status")}
                        </div>
                        <button
                            className={`btn border rounded px-3 mr-1`}
                            style={
                                filters.status === "active"
                                    ? { background: "white" }
                                    : {
                                        background: "#2c3f51",
                                        color: "white",
                                    }
                            }
                            onClick={() => {
                                setFilters({
                                    ...filters,
                                    status: "active",
                                });
                            }}
                        >
                            {t("admin.global.active")}
                        </button>
                        <button
                            className={`btn border rounded px-3 mr-1`}
                            style={
                                filters.status === "past"
                                    ? { background: "white" }
                                    : {
                                        background: "#2c3f51",
                                        color: "white",
                                    }
                            }
                            onClick={() => {
                                setFilters({
                                    ...filters,
                                    status: "past",
                                });
                            }}
                        >
                            {t("admin.global.past")}
                        </button>
                    </div>
                    <div className="col-sm-12 d-flex mt-2">
                        <div
                            className="mr-3 align-items-center"
                            style={{ fontWeight: "bold" }}
                        >
                            {t("admin.global.manpower_company")}
                        </div>
                        <div className="d-flex">
                            <select
                                className="form-control"
                                onChange={(e) => {
                                    setFilters({
                                        ...filters,
                                        manpower_company_id: e.target.value,
                                        is_my_company: false,
                                    });
                                }}
                                value={filters.manpower_company_id}
                            >
                                <option value="">--- Select ---</option>

                                {manpowerCompanies.map((company, _index) => (
                                    <option key={_index} value={company.id}>
                                        {" "}
                                        {company.name}
                                    </option>
                                ))}
                            </select>
                            <button
                                className={`btn border rounded px-3 mx-1`}
                                style={
                                    filters.is_my_company === true
                                        ? { background: "white" }
                                        : {
                                            background: "#2c3f51",
                                            color: "white",
                                        }
                                }
                                onClick={() => {
                                    setFilters({
                                        ...filters,
                                        manpower_company_id: "",
                                        is_my_company: true,
                                    });
                                }}
                            >
                                {t("admin.global.myCompany")}
                            </button>
                            <button
                                className={`btn border rounded px-3 mx-1`}
                                style={
                                    filters.is_my_company !== true &&
                                        filters.manpower_company_id === ""
                                        ? { background: "white" }
                                        : {
                                            background: "#2c3f51",
                                            color: "white",
                                        }
                                }
                                onClick={() => {
                                    setFilters({
                                        ...filters,
                                        manpower_company_id: "",
                                        is_my_company: false,
                                    });
                                }}
                            >
                                {t("admin.global.All")}
                            </button>
                        </div>

                        <input
                            type="hidden"
                            value={filters.status}
                            ref={statusRef}
                        />

                        <input
                            type="hidden"
                            value={filters.manpower_company_id}
                            ref={manpowerCompanyRef}
                        />

                        <input
                            type="hidden"
                            value={filters.is_my_company}
                            ref={isMyCompanyRef}
                        />
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

                {isOpenLeaveJobWorker && (
                    <LeaveJobWorkerModal
                        setIsOpen={setIsOpenLeaveJobWorker}
                        isOpen={isOpenLeaveJobWorker}
                        workerId={selectedWorkerId}
                    />
                )}

                <Modal show={show} onHide={handleClose}>
                    <Modal.Header closeButton>
                        <Modal.Title>{t("admin.global.import_file")}</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <a href="/api/admin/workers/import/sample">
                            {t("admin.global.download_sample_file")}
                        </a>
                        <form encType="multipart/form-data">
                            <div className="row mt-2">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <input
                                            type="file"
                                            onChange={(e) =>
                                                setImportFile(e.target.files[0])
                                            }
                                            className="form-control"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleClose}>
                            {t("global.close")}
                        </Button>
                        <Button
                            className="btn btn-pink"
                            onClick={handleImportSubmit}
                        >
                            {t("admin.global.submit")}
                        </Button>
                    </Modal.Footer>
                </Modal>
            </div>
        </div>
    );
}
