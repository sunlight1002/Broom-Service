import React, { useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import ClientSidebar from "../../Layouts/ClientSidebar";

export default function ClientOfferPrice() {
    const { t } = useTranslation();
    const tableRef = useRef(null);
    const navigate = useNavigate();

    useEffect(() => {
        const table = $(tableRef.current).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/api/client/offers",
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
                    title: t("client.offer.ofr_date"),
                    data: "created_at",
                },
                {
                    title: t("client.offer.services"),
                    data: "services",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        if (data == null) {
                            return "-";
                        }

                        return data.map((s, j) => {
                            return data.length - 1 != j
                                ? s.template == "others"
                                    ? s.other_title + " | "
                                    : s.name + " | "
                                : s.name;
                        });
                    },
                },
                {
                    title: t("client.offer.status"),
                    data: "status",
                },
                {
                    title: t("client.offer.action"),
                    data: "action",
                    orderable: false,
                    responsivePriority: 1,
                    render: function (data, type, row, meta) {
                        const _id = Base64.encode(row.id.toString());

                        let _html = `<a href="/price-offer/${_id}" class="ml-auto ml-md-2 mt-4 mt-md-0 btn dt-view-button" data-id="${_id}"
                                    style="font-size: 15px; color: #2F4054; padding: 5px 8px; background: #E5EBF1; border-radius: 5px;">`;

                        _html += `<i class="fa fa-eye"></i></a>`;

                        _html += `</a>`;

                        return _html;
                    },
                },
            ],
            ordering: true,
            searching: true,
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
                        $(td).addClass('custom-cell-class');
                    }
                }
            ]
        });

        const searchInputWrapper = `<i class="fa fa-search search-icon"></i>`;
        $("div.dt-search").append(searchInputWrapper);
        $("div.dt-search").addClass("position-relative");

        $(tableRef.current).on("click", ".dt-view-button", function (e) {
            e.preventDefault();
            const _id = $(this).data("id").toString();
            navigate(`/price-offer/${_id}`);
        });

        return function cleanup() {
            $(tableRef.current).DataTable().destroy(true);
        };
    }, []);


    return (
        <div id="container">
            <ClientSidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">
                                {t("client.common.offers")}
                            </h1>
                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body">
                        <div className="boxPanel">
                            <table
                                ref={tableRef}
                                className="display table custom-datatable table-bordered"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
