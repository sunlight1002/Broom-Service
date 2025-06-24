import React, { useState, useEffect, useRef } from 'react';
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import axios from 'axios';
import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import Sidebar from '../../Layouts/Sidebar';

export default function Cvv() {
    const { t } = useTranslation();
    const alert = useAlert();
    const tableRef = useRef(null);
    
    const [query, setQuery] = useState('');
    const [loading, setLoading] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const initializeDataTable = () => {
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/api/admin/cvv/search",
                    type: "POST",
                    beforeSend: function (request) {
                        request.setRequestHeader(
                            "Authorization",
                            `Bearer ` + localStorage.getItem("admin-token")
                        );
                    },
                    data: function (d) {
                        d.client = query;
                    },
                },
                order: [[1, "asc"]],
                columns: [
                    {
                        title: "No.",
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        },
                        className: "text-left",
                        width: "10%",
                    },
                    {
                        title: "Client Name",
                        data: "client_name",
                        orderable: false,
                    },
                    {
                        title: "Card Type",
                        data: "cards",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            if (type === 'display') {
                                let html = '';
                                data.forEach((card, index) => {
                                    if (index === 0) {
                                        html += `<div>${card.card_type}</div>`;
                                    } else {
                                        html += `<div class="mt-1">${card.card_type}</div>`;
                                    }
                                });
                                return html;
                            }
                            return data;
                        },
                    },
                    {
                        title: "CVV",
                        data: "cards",
                        orderable: false,
                        render: function (data, type, row, meta) {
                            if (type === 'display') {
                                let html = '';
                                data.forEach((card, index) => {
                                    if (index === 0) {
                                        html += `<div>${card.cvv}</div>`;
                                    } else {
                                        html += `<div class="mt-1">${card.cvv}</div>`;
                                    }
                                });
                                return html;
                            }
                            return data;
                        },
                    },
                ],
                ordering: true,
                searching: true,
                responsive: true,
                autoWidth: true,
                width: "100%",
                scrollX: true,
                info: false, // Remove "Showing X to Y of Z entries"
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass('custom-row-class');
                },
                columnDefs: [
                    {
                        targets: '_all',
                        createdCell: function (td, cellData, rowData, row, col) {
                            $(td).addClass('custom-cell-class');
                        }
                    }
                ]
            });

            // Customize the search input
            const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
            $("div.dt-search").append(searchInputWrapper);
            $("div.dt-search").addClass("position-relative");
        }
    };

    useEffect(() => {
        initializeDataTable();
    }, []);

    useEffect(() => {
        if ($.fn.DataTable.isDataTable(tableRef.current)) {
            const table = $(tableRef.current).DataTable();
            table.ajax.reload();
        }
    }, [query]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="d-flex flex-column flex-lg-row">
                        <div className="d-flex mt-2 d-lg-none justify-content-between no-hover">
                            <h1 className="page-title p-0">
                                CVV Search
                            </h1>
                        </div>

                        <div className="clearfix w-100 justify-content-between align-items-center">
                            <h1 className="page-title d-none d-lg-block float-left">
                                CVV Search
                            </h1>
                        </div>
                    </div>
                </div>

                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body boxPanel-th-border-none">
                        <div className="table-responsive">
                            <table ref={tableRef} className="table table-striped">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Client Name</th>
                                        <th>Card Type</th>
                                        <th>CVV</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
} 