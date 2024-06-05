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
                    title: "Worker",
                    data: "worker_name",
                    render: function (data, type, row, meta) {
                        let _html = `<span class="worker-name-badge" data-id="${row.id}" data-total-amount="${row.total_amount}">`;

                        _html += `<i class="fa-solid fa-user"></i>`;

                        _html += data;

                        _html += `</span>`;

                        return _html;
                    },
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
                    title: "Shift",
                    data: "shifts",
                    render: function (data, type, row, meta) {
                        const _slots = data.split(",");

                        return _slots
                            .map((_slot, index) => {
                                return `<div class="rounded mb-1 shifts-badge"> ${_slot} </div>`;
                            })
                            .join(" ");
                    },
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
                    title: "Complete Time",
                    data: "duration_minutes",
                    render: function (data, type, row, meta) {
                        return `<span class="text-nowrap"> ${minutesToHours(
                            data
                        )} </span>`;
                    },
                },
                {
                    title: "Status",
                    data: "status",

                    // let status = item.status;
                    //                             if (status == "not-started") {
                    //                                 status = t(
                    //                                     "j_status.not-started"
                    //                                 );
                    //                             }
                    //                             if (status == "progress") {
                    //                                 status =
                    //                                     t("j_status.progress");
                    //                             }
                    //                             if (status == "completed") {
                    //                                 status =
                    //                                     t("j_status.completed");
                    //                             }
                    //                             if (status == "scheduled") {
                    //                                 status =
                    //                                     t("j_status.scheduled");
                    //                             }
                    //                             if (status == "unscheduled") {
                    //                                 status = t(
                    //                                     "j_status.unscheduled"
                    //                                 );
                    //                             }
                    //                             if (status == "re-scheduled") {
                    //                                 status = t(
                    //                                     "j_status.re-scheduled"
                    //                                 );
                    //                             }
                    //                             if (status == "cancel") {
                    //                                 status =
                    //                                     t("j_status.cancel");
                    //                             }
                },
                {
                    title: "Total",
                    data: "total",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        return (
                            `${data} ` +
                            t("global.currency") +
                            " + " +
                            t("global.vat")
                        );
                    },
                },
                {
                    title: "Action",
                    data: "action",
                    orderable: false,
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
                $(row).addClass("dt-row");
                $(row).attr("data-id", data.id);
            },
        });

        $(tableRef.current).on("click", ".dt-row", function (e) {
            if (
                !e.target.closest(".dropdown-toggle") &&
                !e.target.closest(".dropdown-menu") &&
                !e.target.closest(".dt-address-link") &&
                !e.target.closest(".dtr-control")
            ) {
                const _id = Base64.encode($(this).data("id").toString());
                navigate(`/client/view-job/${_id}`);
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
