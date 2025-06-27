import axios from "axios";
import React, { useRef, useState, useEffect } from "react";
import { useTranslation } from "react-i18next";
import { Link, useNavigate } from "react-router-dom";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";

import Sidebar from "../../Layouts/Sidebar";
import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import { getDataTableStateConfig, TABLE_IDS } from '../../../Utils/datatableStateManager';

function Expanses() {
    const { t, i18n } = useTranslation();
    const [isOpen, setIsOpen] = useState(false)
    const [errors, setErrors] = useState({});
    const [expenseTypes, setExpenseTypes] = useState([]);
    const [expenseType, setExpenseType] = useState({
        expense_doctype: '',
        expense_type_id: ''
    });
    const [suppliers, setSuppliers] = useState([]);
    const [supplier, setSupplier] = useState({
        supplier_id: '',
        supplier_name: '',
        supplier_vat_id: ''
    });
    const [expenseSum, setExpenseSum] = useState("");
    const [expenseDocNum, setExpenseDocNum] = useState("");
    const [scanFile, setScanFile] = useState(null);
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const adminId = localStorage.getItem("admin-id");
    const tableRef = useRef(null);


    const handleExpanse = async () => {
        try {
            const data = new FormData();
            data.append("supplier_id", `${supplier.supplier_id}`);

            data.append("supplier_name", `${supplier.supplier_name}`);
            data.append("supplier_vat_id", `${supplier.supplier_vat_id}`);

            data.append("expense_type_id", `${expenseType.expense_type_id}`);
            data.append('expense_type_name', `${expenseType.expense_doctype}`);
            data.append("expense_sum", `${expenseSum}`);
            data.append('expense_docnum', `${expenseDocNum}`);
            if (scanFile) {
                data.append('scan', scanFile);
            }
            const res = await axios.post(`/api/admin/expenses/store`, data, { headers: headers });
            if (res.data.icount_response.status) {
                alert.success("Expense added successfully");
                setIsOpen(false);
                setExpenseType({ expense_doctype: null, expense_type_id: null });
                setSupplier({ supplier_id: null, supplier_name: null, supplier_vat_id: null });
                setExpenseSum("");
                setExpenseDocNum("");
                setScanFile(null);
            }
        } catch (error) {
            if (error.response && error.response.data.errors) {
                setErrors(error.response.data.errors);
            } else {
                console.error("Something went wrong:", error);
            }
        }
    }

    const handleGetExpanseTypes = async () => {
        try {
            const res = await axios.get(`/api/admin/get-expense-types`, { headers: headers });
            console.log(res.data);

            if (res.data.status) {
                const typesArray = Object.values(res.data.expense_types); // Convert object to array
                setExpenseTypes(typesArray);
            }
        } catch (error) {
            console.error("Error fetching expense types:", error);
        }
    };

    const handleGetSuplierList = async () => {
        try {
            const res = await axios.get(`/api/admin/get-suplier-list`, { headers: headers });
            console.log(res.data);

            if (res.data.status && res.data.suppliers) {
                const suppliersArray = Object.values(res.data.suppliers); // Convert object to array

                setSuppliers(suppliersArray);
            }
        } catch (error) {
            console.error("Error fetching supplier list:", error);
        }
    };


    // Call function when component mounts
    useEffect(() => {
        handleGetExpanseTypes();
        handleGetSuplierList();
    }, []);

    const initializeDataTable = (initialPage = 0) => {
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            const baseConfig = {
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/expenses",
                    type: "GET",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                    // data: function (d) {
                    //     d.status = filterRef.current === "All" ? null : filterRef.current; // Use ref here
                    // },
                },
                order: [[0, "desc"]],
                columns: [
                    { title: "ID", data: "id", visible: false },
                    { title: t("global.supplier"), data: "supplier_name" },
                    {
                        title: t("global.vat_id"), data: "supplier_vat_id",
                        render: function (data) {
                            return `${data ? data : null}`;
                        },
                    },
                    {
                        title: t("global.type"),
                        data: "expense_type_name",
                        render: function (data) {
                            return `${data}`;
                        }
                    },
                    {
                        title: t("global.doc_num"),
                        data: "expense_docnum",
                        render: function (data) {
                            return `${data}`;
                        },
                    },
                    {
                        title: t("global.sum"),
                        data: "expense_sum",
                        render: function (data) {
                            return `${data}`;
                        }
                    },
                    {
                        title: t("global.file"),
                        data: "upload_file",
                        render: function (data) {
                            if(data){
                                return `<a href="/storage/uploads/expanses/${data}" target="_blank">${t("admin.leads.view")}</a>`;
                            }else{
                                return "";
                            }
                        }
                    },
                    // {
                    //     title: t("admin.global.action"),
                    //     data: null,
                    //     orderable: false,
                    //     responsivePriority: 1,
                    //     render: function (data, type, row, meta) {
                    //         return `
                    //             <div class="action-dropdown dropdown"> 
                    //                 <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> 
                    //                     <i class="fa fa-ellipsis-vertical"></i> 
                    //                 </button> 
                    //                 <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    //                     <button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}">${t('admin.leads.Edit')}</button>
                    //                     <button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">${t("admin.leads.view")}</button>
                    //                     <button type="button" class="dropdown-item dt-change-status-btn" data-id="${row.id}">${t("admin.leads.change_status")}</button>
                    //                     <button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">${t("admin.leads.Delete")}</button>
                    //                 </div> 
                    //             </div>`;
                    //     }

                    // },
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
                        targets: "_all",
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass("custom-cell-class");
                        },
                    },
                ],
                initComplete: function () {
                    const table = $(tableRef.current).DataTable();
                    table.page(initialPage).draw("page");
                },
            };

            // Add state management configuration
            const stateConfig = getDataTableStateConfig(TABLE_IDS.EXPENSES, {
                onStateLoad: (settings, data) => {
                    console.log('Expenses table state loaded:', data);
                },
                onStateSave: (settings, data) => {
                    console.log('Expenses table state saved:', data);
                }
            });

            const fullConfig = { ...baseConfig, ...stateConfig };

            const table = $(tableRef.current).DataTable(fullConfig);

            $(tableRef.current).css('table-layout', 'fixed');
        } else {
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
    }, [location.search]);

    // useEffect(() => {
    //     filterRef.current = filter; // Update the ref with the latest filter
    //     const table = $(tableRef.current).DataTable();
    //     table.ajax.reload(null, false); // Reload the table without resetting pagination
    //     table.columns.adjust().draw();  // This forces a redraw to fix the column shifting issue

    // }, [filter]);


    const btnSelect = (type) => {
        document.getElementById(`${type}`).click();
    };


    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex justify-content-between">
                        <div className="">
                            <h1 className="page-title">{t("global.expenses")}</h1>
                        </div>
                        <button
                            onClick={() => setIsOpen(true)}
                            className="btn navyblue align-content-center addButton no-hover"
                        >
                            <i className="btn-icon fas fa-plus-circle"></i>
                            {t("admin.client.AddNew")}
                        </button>
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
                    <Modal.Title>{t("global.add_expenses")}</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">

                        <div className="col-sm-12">
                            <label className="control-label">{t("admin.global.Document")}</label>

                            <button
                                type="button"
                                onClick={() => btnSelect("invoice")}
                                className="btn navyblue m-3"
                            >
                                {t("global.upload")}
                            </button>
                            <input
                                className="form-control d-none"
                                id="invoice"
                                type="file"
                                accept="application/pdf, image/*"
                                onChange={(e) =>
                                    setScanFile(e.target.files[0])
                                }
                            ></input>
                        </div>
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">{t("global.doc_type")}</label>

                                <select
                                    name="expense_type_id"
                                    className="form-control"
                                    value={expenseType.expense_type_id || ""}
                                    onChange={(e) => {
                                        const selectedId = e.target.value;
                                        const selectedType = expenseTypes.find(type => type.expense_type_id == selectedId);

                                        setExpenseType({
                                            expense_doctype: selectedType ? selectedType.expense_type_name : null,
                                            expense_type_id: selectedId
                                        });
                                    }}
                                >
                                    <option value="">{t("global.select_default_option")}</option>
                                    {expenseTypes.map((type) => (
                                        <option key={type.expense_type_id} value={type.expense_type_id}>
                                            {type.expense_type_name}
                                        </option>
                                    ))}
                                </select>
                                {errors.expense_type_name && (
                                    <small className="text-danger mb-1">
                                        {errors.expense_type_name}
                                    </small>
                                )}
                            </div>
                        </div>
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">{t("global.suppliers")}</label>

                                <select
                                    name="expense_type_id"
                                    className="form-control "
                                    value={supplier.supplier_id || ""}
                                    onChange={(e) => {
                                        const selectedId = e.target.value;
                                        const selectedType = suppliers.find(s => s.supplier_id == selectedId);
                                        setSupplier({
                                            supplier_id: selectedId,
                                            supplier_name: selectedType ? selectedType.supplier_name : null,
                                            supplier_vat_id: selectedType ? selectedType.vat_id : null
                                        });
                                    }}
                                >
                                    <option value="">{t("global.select_default_option")}</option>
                                    {suppliers.map((s) => (
                                        <option key={s.supplier_id} value={s.supplier_id}>
                                            {s.supplier_name}
                                        </option>
                                    ))}
                                </select>
                                {errors.supplier_name && (
                                    <small className="text-danger mb-1">
                                        {errors.supplier_name}
                                    </small>
                                )}
                            </div>
                        </div>
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">Document Number</label>
                                <input
                                    name="doc_number"
                                    type="text"
                                    className="form-control"
                                    value={expenseDocNum}
                                    onChange={(e) => setExpenseDocNum(e.target.value)}
                                />
                                {errors.expense_docnum && (
                                    <small className="text-danger mb-1">
                                        {errors.expense_docnum}
                                    </small>
                                )}
                            </div>
                        </div>
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">Expense Sum</label>
                                <input
                                    type="number"
                                    name="expense_sum"
                                    value={expenseSum}
                                    className="form-control"
                                    onChange={(e) => setExpenseSum(e.target.value)}
                                />
                                {errors.expense_sum && (
                                    <small className="text-danger mb-1">
                                        {errors.expense_sum}
                                    </small>
                                )}
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
                        onClick={handleExpanse}
                        className="btn btn-primary"
                    >
                        {t("global.send")}
                    </Button>
                </Modal.Footer>
            </Modal>
        </div >

    )
}

export default Expanses