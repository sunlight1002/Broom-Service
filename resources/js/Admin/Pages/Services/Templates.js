import React, { useState } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";

export default function Templates() {

    const { t } = useTranslation();

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title addEmployer">{t("admin.global.serviceTemplates")}</h1>
                    <div className="card">
                        <div className="card-body">
                        <table className="table table-bordered">
                        <thead>
                            <tr>
                                <th scope="col" >{t("admin.global.template")}</th>
                                <th scope="col" >{t("admin.global.view")}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{t("services.regularServices")}</td>
                                <td>
                                <Link
                                    to="/admin/template/regular-service"
                                    className="btn bg-yellow">
                                    <i className="fa fa-eye"></i>
                                </Link>
                                </td>
                            </tr>
                            <tr>
                                <td>{t("services.officeCleaning")}</td>
                                <td>
                                <Link
                                    to="/admin/template/office-cleaning"
                                    className="btn bg-yellow">
                                    <i className="fa fa-eye"></i>
                                </Link>
                                </td>
                            </tr>
                            <tr>
                                <td>{t("services.cleaning/renovation")}</td>
                                <td>
                                <Link
                                    to="/admin/template/after-renovation"
                                    className="btn bg-yellow">
                                    <i className="fa fa-eye"></i>
                                </Link>
                                </td>
                            </tr>
                            <tr>
                                <td>{t("services.throughCleaning")}</td>
                                <td>
                                <Link
                                    to="/admin/template/thorough-cleaning"
                                    className="btn bg-yellow">
                                    <i className="fa fa-eye"></i>
                                </Link>
                                </td>
                            </tr>
                            <tr>
                                <td>{t("services.windowCleaning")}</td>
                                <td>
                                <Link
                                    to="/admin/template/window-cleaning"
                                    className="btn bg-yellow">
                                    <i className="fa fa-eye"></i>
                                </Link>
                                </td>
                            </tr>
                            <tr>
                                <td>{t("services.airBnb")}</td>
                                <td>
                                <Link
                                    to="/admin/template/airbnb-servce"
                                    className="btn bg-yellow">
                                    <i className="fa fa-eye"></i>
                                </Link>
                                </td>
                            </tr>
                            <tr>
                            <td>{t("services.others")}</td>
                                <td>
                                <Link
                                    to="/admin/template/others"
                                    className="btn bg-yellow">
                                    <i className="fa fa-eye"></i>
                                </Link>
                                </td>
                            </tr>
                        </tbody>
                        
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    );
}