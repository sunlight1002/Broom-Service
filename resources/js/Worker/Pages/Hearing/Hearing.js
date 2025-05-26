import React, { useEffect, useRef } from "react";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";
import { useNavigate } from "react-router-dom";
import { LuFolderClosed } from "react-icons/lu";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/WorkerSidebar";

export default function Hearing() {
    const navigate = useNavigate();
    const { t } = useTranslation();
    const tableRef = useRef(null);

    useEffect(() => {
        const table = $(tableRef.current).DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: "/api/schedule",
                type: "GET",
                beforeSend: function (request) {
                    request.setRequestHeader(
                        "Authorization",
                        `Bearer ` + localStorage.getItem("worker-token")
                    );
                },
            },
            order: [[0, "desc"]],
            columns: [
                {
                    title: t("worker.hearing.meeting.attender"),
                    data: "attender_name",
                },
                {
                    title: t("worker.hearing.meeting.address"),
                    data: "address_name",
                    render: function (data, type, row, meta) {
                        if (data) {
                            return `<a href="https://maps.google.com?q=${row.geo_address}" target="_blank" class="" style="color: black; text-decoration: underline;"> ${data} </a>`;
                        } else {
                            return "NA";
                        }
                    },
                },
                {
                    title: t("worker.hearing.meeting.scheduled"),
                    data: "start_date",
                    render: function (data, type, row, meta) {
                        let _html = "";

                        if (row.start_date) {
                            _html += `<span class=""> ${Moment(
                                row.start_date
                            ).format("DD/MM/Y")} </span>`;

                            _html += `<span class=""> ${Moment(
                                row.start_date
                            ).format("dddd")} </span>`;

                            if (row.start_time && row.end_time) {
                                _html += `<span class="">  ${row.start_time} </span>`;
                                _html += `<span class="">  ${row.end_time} </span>`;
                            }
                        }

                        return _html;
                    },
                },
                {
                    title: t("worker.hearing.meeting.purpose"),
                    data: "purpose",
                },
                {
                    title: t("worker.hearing.meeting.status"),
                    data: "booking_status",
                    render: function (data, type, row, meta) {
                        return `<p style="background-color: #2F4054; color: white; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                    ${data}
                                </p>`;
                    }
                },
                {
                    title: t("worker.hearing.meeting.document"),
                    data: "document",
                    render: function (data) {
                        return data ? data : 'No Document';
                    }
                },
            ],
            searching: true,
            ordering: true,
            responsive: true,
            autoWidth: true,
            width: "100%",
            scrollX: true,
            createdRow: function (row, data, dataIndex) {
                $(row).addClass('custom-row-class');
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

        return function cleanup() {
            $(tableRef.current).DataTable().destroy(true);
        };
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">
                                {t("worker.common.meetings")}
                            </h1>
                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table  custom-datatable"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
