import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import { Base64 } from "js-base64";
import axios from "axios";
import { useTranslation } from "react-i18next";

function AllTemplatesList() {
    const [templates, setTemplates] = useState([]);
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleGetTemplates = async () => {
        try {
            const response = await axios.get('/api/admin/whatsapp-templates', { headers });
            setTemplates(response?.data)
        } catch (error) {
            console.error(error.response ? error.response.data : error.message);
        }
    };

    useEffect(() => {
        handleGetTemplates();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("admin.sidebar.templates.all_templates")}</h1>
                        </div>
                    </div>
                </div>

                <div className="border-0 dashBox" style={{ background: "inherit" }}>
                    <div className="table-responsive">
                        <table className="table table-bordered">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">{t("admin.sidebar.templates.template_name")}</th>
                                    <th scope="col">{t("admin.sidebar.templates.description")}</th>
                                    <th scope="col">{t("admin.sidebar.templates.action")}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {templates.length > 0 ? (
                                    templates.map((template, index) => (
                                        <tr key={template.id}>
                                            <td>{index + 1}</td>
                                            <td>{template.key}</td>
                                            <td>{template.description}</td>
                                            <td>
                                                <Link
                                                    to={`edit/template/${Base64.encode(String(template.id))}`}
                                                    className="btn btn-primary">
                                                    {t("admin.sidebar.templates.edit")}
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="3" className="text-center">
                                            {t("admin.sidebar.templates.no_templates_found")}.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default AllTemplatesList;
