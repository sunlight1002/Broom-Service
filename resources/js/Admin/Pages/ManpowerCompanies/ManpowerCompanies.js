import React, { useState, useEffect, useRef } from "react";
import axios from "axios";
import { useTranslation } from "react-i18next";
import Swal from "sweetalert2";

import $ from "jquery";
import "datatables.net";
import "datatables.net-dt/css/dataTables.dataTables.css";
import "datatables.net-responsive";
import "datatables.net-responsive-dt/css/responsive.dataTables.css";

import Sidebar from "../../Layouts/Sidebar";
import ManpowerCompanyModal from "../../Components/Modals/ManpowerCompanyModal";

export default function ManpowerCompanies() {

    const { t } = useTranslation();
    const [isOpenCompanyModal, setIsOpenCompanyModal] = useState(false);
    const [selectedCompany, setSelectedCompany] = useState(null);

    const tableRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        $(tableRef.current).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/api/admin/manpower-companies",
                type: "GET",
                beforeSend: function (request) {
                    request.setRequestHeader(
                        "Authorization",
                        `Bearer ` + localStorage.getItem("admin-token")
                    );
                },
            },
            order: [[0, "desc"]],
            columns: [
                {
                    title: "ID",
                    data: "id",
                    visible: false,
                },
                {
                    title: "Name",
                    data: "name",
                },
                {
                    title: "Action",
                    data: "action",
                    orderable: false,
                    render: function (data, type, row, meta) {
                        let _html =
                            '<div class="action-dropdown dropdown"> <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="fa fa-ellipsis-vertical"></i> </button> <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">';

                        _html += `<button type="button" class="dropdown-item dt-edit-btn" data-id="${row.id}" data-name="${row.name}">Edit</button>`;

                        if (row.contract_filename) {
                            _html += `<a href="/storage/manpower-companies/contract/${row.contract_filename}" target="_blank" class="dropdown-item">Contract</a>`;
                        }

                        _html += `<button type="button" class="dropdown-item dt-delete-btn" data-id="${row.id}">Delete</button>`;

                        _html += "</div> </div>";

                        return _html;
                    },
                },
            ],
            ordering: true,
            searching: true,
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

        $(tableRef.current).on("click", ".dt-edit-btn", function () {
            const _id = $(this).data("id");
            const _name = $(this).data("name");
            handleEditCompany({
                id: _id,
                name: _name,
            });
        });

        $(tableRef.current).on("click", ".dt-delete-btn", function () {
            const _id = $(this).data("id");
            handleDelete(_id);
        });

        return function cleanup() {
            $(tableRef.current).DataTable().destroy(true);
        };
    }, []);

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Company!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/manpower-companies/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Company has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            $(tableRef.current).DataTable().draw();
                        }, 1000);
                    });
            }
        });
    };

    const handleAddCompany = () => {
        setSelectedCompany(null);
        setIsOpenCompanyModal(true);
    };

    const handleEditCompany = (_company) => {
        setSelectedCompany(_company);
        setIsOpenCompanyModal(true);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("admin.sidebar.settings.manpower")}</h1>
                        </div>
                        <div className="col-sm-6">
                            <button
                                type="button"
                                className="ml-2 btn navyblue addButton"
                                onClick={handleAddCompany}
                            >
                                {t("modal.add")} {t("admin.sidebar.settings.manpower")}
                            </button>
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

            {isOpenCompanyModal && (
                <ManpowerCompanyModal
                    isOpen={isOpenCompanyModal}
                    setIsOpen={setIsOpenCompanyModal}
                    company={selectedCompany}
                    onSuccess={() => {
                        setIsOpenCompanyModal(false);
                        $(tableRef.current).DataTable().draw();
                    }}
                />
            )}
        </div>
    );
}
