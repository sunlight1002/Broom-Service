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

import Sidebar from "../../Layouts/ClientSidebar";

export default function Schedule() {
    const navigate = useNavigate();
    const { t } = useTranslation();
    const tableRef = useRef(null);

    useEffect(() => {
        const table = $(tableRef.current).DataTable({
            serverSide: true,
            processing: true,
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
                            return `<a href="https://maps.google.com?q=${row.geo_address}" target="_blank" class="" style="color: black; text-decoration: underline;"> ${data} </a>`;
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
                    title: "Purpose",
                    data: "purpose",
                },
                {
                    title: "Status",
                    data: "booking_status",
                    render: function (data, type, row, meta) {
                        return `<p style="background-color: #2F4054; color: white; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                    ${data}
                                </p>`;
                    }
                },

                {
                    title: "Files",
                    data: "action",
                    orderable: false,
                    responsivePriority: 1,
                    render: function (data, type, row, meta) {
                        const _id = Base64.encode(row.id.toString());

                        let _html = `<a href="/client/files/${_id}" class="d-block d-md-flex text-center pl-5 pl-md-0 dt-file-button" data-id="${_id}">`;
                        _html += `<i class="fa-regular fa-folder" style="font-size: 24px; color: #2F4054; padding: 7px; background: #E5EBF1; border-radius: 5px;"></i></a>`;
                        // if (row.file_exists == 1) {
                        //     _html += `<i class="fa fa-image" style="font-size: 24px"></i></a>`;
                        // } else {
                        //     _html += `<i class="fa fa-upload" style="font-size: 24px"></i></a>`;
                        // }

                        // _html += `</a>`;

                        return _html;
                    },
                },
            ],
            searching: true,
            ordering: true,
            responsive: true,
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

        // Customize the search input
        const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
        $("div.dt-search").append(searchInputWrapper);
        $("div.dt-search").addClass("position-relative");

        // Handle search input event
        $(document).on('keyup', '.input-search', function () {
            table.search(this.value).draw();
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
