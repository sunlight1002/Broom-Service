import React, { useState, useRef, useEffect } from 'react';
import axios from 'axios';
import { useAlert } from 'react-alert';
import { useTranslation } from "react-i18next";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";
import { getDataTableStateConfig, TABLE_IDS } from '../../../Utils/datatableStateManager';
import FullPageLoader from "../../../Components/common/FullPageLoader";
import Sidebar from "../../Layouts/WorkerSidebar";
import { getMobileStatusBadgeHtml } from '../../../Utils/common.utils';

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
        // Ensure DataTable is initialized only if it hasn't been already
        if (!$.fn.DataTable.isDataTable(tableRef.current)) {
            const baseConfig = {
                processing: true,
                serverSide: true,
                autoWidth: false,
                paging: false, 
                searching: false, 
                ordering: false, 
                info: false,
                ajax: {
                    url: "/api/advance-loans",
                    type: "GET",
                    headers: headers,
                    dataSrc: '', // Ensure data is fetched correctly
                    error: function (xhr, error, thrown) {
                        console.error('Error fetching data:', error);
                        alert.error('Error fetching data');
                    },
                },
                columns: [
                    {
                        title: t("worker.advanceAndLoan.type"),
                        data: "type",
                    },
                    {
                        title: t("worker.advanceAndLoan.date"),
                        data: "created_at",
                    },

                    {
                        title: t("worker.advanceAndLoan.monthlyPayment"),
                        data: "monthly_payment",
                        render: function(data, type, row) {
                            return formatCurrency(data);
                        }
                    },
                    {
                        title: t("worker.advanceAndLoan.amount"),
                        data: "amount",
                        render: function(data, type, row) {
                            return formatCurrency(data);
                        }
                    },
                    {
                        title: t("worker.advanceAndLoan.paidAmount"),
                        data: "total_paid_amount",
                        render: function(data, type, row) {
                            return formatCurrency(data);
                        }
                    },
                    {
                        title: t("worker.advanceAndLoan.pendingAmount"),
                        data: "latest_pending_amount",
                        render: function(data, type, row) {
                            return formatCurrency(data);
                        }
                    },
                    {
                        title: t("worker.advanceAndLoan.status"),
                        data: "status",
                        render: function (data, type, row, meta) {
                            const style = getStatusStyle(data);
                            const badge = getMobileStatusBadgeHtml(data);
                            return `<span style="color: ${style.color}; font-weight: ${style.fontWeight};">${data} ${badge}</span>`;
                        }
                    },
                ],
                responsive: true,
                createdRow: function (row, data, dataIndex) {
                    $(row).addClass("dt-row custom-row-class");
                    $(row).attr("data-id", data.id);
                },
            };

            // Add state management configuration
            const stateConfig = getDataTableStateConfig(TABLE_IDS.WORKER_ADVANCE_LOANS, {
                onStateLoad: (settings, data) => {
                    console.log('Worker advance loans table state loaded:', data);
                },
                onStateSave: (settings, data) => {
                    console.log('Worker advance loans table state saved:', data);
                }
            });

            const fullConfig = { ...baseConfig, ...stateConfig };

            $(tableRef.current).DataTable(fullConfig);

            $(tableRef.current).css('table-layout', 'fixed');
        }
    };

    useEffect(() => {
        setLoading(true);
        initializeDataTable();
        setLoading(false);

        // Cleanup on unmount
        return () => {
            if ($.fn.DataTable.isDataTable(tableRef.current)) {
                $(tableRef.current).DataTable().destroy(true);
            }
        };
    }, []);

    const getStatusStyle = (status) => {
        switch (status) {
            case 'paid':
                return {
                    color: 'green',
                    fontWeight: 'bold'
                };
            case 'active':
                return { color: 'orange', fontWeight: 'bold' };
            case 'pending':
                return { color: 'red', fontWeight: 'bold' };
            default:
                return {};
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
                            <h1 className="page-title">{t("worker.advanceAndLoan.advanceAndLoansTitle")}</h1>
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