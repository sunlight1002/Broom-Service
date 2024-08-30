import React, { useState, useRef, useEffect } from 'react';
import axios from 'axios';
import { useAlert } from 'react-alert';
import { useTranslation } from "react-i18next";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import Sidebar from "../../Layouts/WorkerSidebar";

export default function WorkerAdvance() {
    
    const tableRef = useRef(null);
    const { t, i18n } = useTranslation();
    const [loading, setLoading] = useState(false);
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const initializeDataTable = () => {
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            $(tableRef.current).DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
               
                ajax: {
                    url: "/api/advance-loans",
                    type: "GET",
                    headers: headers,
                    data: function (d) {
                        d.search = d.search.value; // Pass the search value to the backend
                        d.status = $('#status-filter').val(); // Example for filtering by status
                    },
                    error: function (xhr, error, thrown) {
                        console.error('Error fetching data:', error);
                        alert.error('Error fetching data');
                    },
                },
                columns: [
                    { title: "Type", data: "type" , orderable: false},
                    { title: "Date", data: "created_at", orderable: false},
                    {
                        title: "Monthly Payment",
                        data: "monthly_payment",
                        render: function (data) {
                            return formatCurrency(data);
                        },
                        orderable: false,
                    },
                    {
                        title: "Amount",
                        data: "amount",
                        render: function (data) {
                            return formatCurrency(data);
                        },
                        orderable: false,
                    },
                    {
                        title: "Paid Amount",
                        data: "total_paid_amount",
                        render: function (data) {
                            return formatCurrency(data);
                        },
                        orderable: false,
                    },
                    {
                        title: "Pending Amount",
                        data: "latest_pending_amount",
                        render: function (data) {
                            return formatCurrency(data);
                        },
                        orderable: false,
                    },
                    {
                        title: "Status",
                        data: "status",
                        render: function (data) {
                            const style = getStatusStyle(data);
                            return `<span style="color: ${style.color}; font-weight: ${style.fontWeight};">${data}</span>`;
                        },
                        orderable: false,
                    },
                ],
                responsive: true,
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
            });
    
            $(tableRef.current).css('table-layout', 'fixed');
        }
    };

    useEffect(() => {
        setLoading(true);
        initializeDataTable();
        setLoading(false);
        
        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true);
            }
        };
    }, []);

    const getStatusStyle = (status) => {
            switch (status) {
                case 'paid': return { color: 'green', fontWeight: 'bold' };
                case 'active': return { color: 'orange', fontWeight: 'bold' };
                case 'pending': return { color: 'red', fontWeight: 'bold' };
                default: return {};
            }
    };
    const formatCurrency = (amount) => {
        if (amount === null || amount === undefined) {
            return '-';
        }
        return `₪${parseFloat(amount).toFixed(2)}`;
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Advance/Loans</h1>
                        </div>
                    </div>
                </div>
                <div className="dashBox p-4" style={{ backgroundColor: "inherit", border: "none" }}>
                    <table ref={tableRef} className="display table table-bordered w-100" />
                </div>
                {loading && <FullPageLoader visible={loading} />}
            </div>
        </div>
    );
}
