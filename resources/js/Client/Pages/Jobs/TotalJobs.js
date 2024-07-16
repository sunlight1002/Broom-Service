import React, { useEffect, useState, useRef } from "react";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";
import { useNavigate } from "react-router-dom";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/ClientSidebar";

export default function TotalJobs() {
    const tableRef = useRef(null);
    const { t, i18n } = useTranslation();
    const navigate = useNavigate();

    const minutesToHours = (minutes) => {
        const hours = Math.floor(minutes / 60);
        return `${hours} hours`;
    };

    useEffect(() => {
        $(tableRef.current).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/api/client/jobs",
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
                    title: "Date",
                    data: "start_date",
                },
                {
                    title: "Service",
                    data: "service_name",
                    render: function (data, type, row, meta) {
                        let _html = `<span class="service-name-badge" style="background-color: ${
                            row.service_color ?? "#FFFFFF"
                        };">`;

                        _html += data;

                        _html += `</span>`;

                        return _html;
                    },
                },
                {
                    title: "Arrival Time",
                    data: "start_time",
                    render: function (data, type, row, meta) {
                        return `<div class="rounded mb-1 " style="text-decoration: underline;"> <a>${data}</a> </div>`;
                    },
                },
                {
                    title: "Address",
                    data: "address_name",
                    render: function (data, type, row, meta) {
                        if (data) {
                            return `<a href="https://maps.google.com?q=${row.geo_address}" target="_blank" class="" style="text-decoration: underline; color: black;"> ${data} </a>`;
                        } else {
                            return "NA";
                        }
                    },
                },
                {
                    title: "Status",
                    data: "status",
                    render: function (data, type, row, meta) {
                        return `<p style="background-color: #2F4054; color: white; padding: 5px 10px; border-radius: 5px; width: 110px; text-align: center;">
                                    ${data}
                                </p>`;
                    }
                },
                {
                    title: "Action",
                    data: "action",
                    orderable: false,
                    responsivePriority: 1,
                    render: function (data, type, row, meta) {
                        let _html =
                            '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                        _html += `<button type="button" class="dropdown-item dt-view-btn" data-id="${row.id}">View</button>`;

                        if (
                            [
                                "not-started",
                                "scheduled",
                                "unscheduled",
                                "re-scheduled",
                            ].includes(row.status)
                        ) {
                            _html += `<button type="button" class="dropdown-item dt-change-schedule-btn" data-id="${row.id}">Change Schedule</button>`;
                        }

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
                        $(td).addClass('custom-cell-class');
                    }
                }
            ]
        });
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
                navigate(`/client/view-job/${Base64.encode(_id.toString())}`);
            }
        });

        $(tableRef.current).on("click", ".dt-view-btn", function () {
            const _id = Base64.encode($(this).data("id").toString());
            navigate(`/client/view-job/${_id}`);
        });

        $(tableRef.current).on("click", ".dt-change-schedule-btn", function () {
            const _id = Base64.encode($(this).data("id").toString());
            navigate(`/client/jobs/${_id}/change-schedule`);
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
                                {t("client.jobs.title")}
                            </h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder={t("client.search")}
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="card" style={{boxShadow: "none"}}>
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
