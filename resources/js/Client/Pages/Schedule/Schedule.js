import React, { useEffect, useRef } from "react";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";
import { useNavigate } from "react-router-dom";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/ClientSidebar";

export default function Schedule() {
    const navigate = useNavigate();
    const { t } = useTranslation();
    const tableRef = useRef(null);

    useEffect(() => {
        $(tableRef.current).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/api/client/schedule",
                type: "GET",
                beforeSend: function (request) {
                    request.setRequestHeader(
                        "Authorization",
                        `Bearer ` + localStorage.getItem("client-token")
                    );
                },
            },
            order: [[0, "desc"]],
            columns: [
                {
                    title: "Attender",
                    data: "attender_name",
                },
                {
                    title: "Address",
                    data: "address_name",
                    render: function (data, type, row, meta) {
                        if (data) {
                            return `<a href="https://maps.google.com?q=${row.latitude},${row.longitude}" target="_blank" class="dt-address-link"> ${data} </a>`;
                        } else {
                            return "NA";
                        }
                    },
                },
                {
                    title: "Scheduled",
                    data: "start_date",
                    render: function (data, type, row, meta) {
                        let _html = "";

                        if (row.start_date) {
                            _html += `<span class="text-blue"> ${Moment(
                                row.start_date
                            ).format("DD/MM/Y")} </span>`;

                            _html += `<br /> <span class="text-blue"> ${Moment(
                                row.start_date
                            ).format("dddd")} </span>`;

                            if (row.start_time && row.end_time) {
                                _html += `<br /> <span class="text-green"> Start : ${row.start_time} </span>`;
                                _html += `<br /> <span class="text-danger"> End : ${row.end_time} </span>`;
                            }
                        }

                        return _html;
                    },
                },
                {
                    title: "Purpose",
                    data: "purpose",
                },
                {
                    title: "Status",
                    data: "booking_status",
                },
                {
                    title: "Files",
                    data: "action",
                    orderable: false,
                    responsivePriority: 1,
                    render: function (data, type, row, meta) {
                        const _id = Base64.encode(row.id.toString());

                        let _html = `<a href="/client/files/${_id}" class="d-block d-md-flex text-center pl-5 pl-md-0 dt-file-button" data-id="${_id}">`;

                        if (row.file_exists == 1) {
                            _html += `<i class="fa fa-image" style="font-size: 24px"></i></a>`;
                        } else {
                            _html += `<i class="fa fa-upload" style="font-size: 24px"></i></a>`;
                        }

                        _html += `</a>`;

                        return _html;
                    },
                },
            ],
            ordering: true,
            searching: true,
            responsive: true,
        });

        $(tableRef.current).on("click", ".dt-file-button", function (e) {
            e.preventDefault();
            const _id = $(this).data("id").toString();
            navigate(`/client/files/${_id}`);
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
                                {t("client.common.meetings")}
                            </h1>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table table-bordered"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
