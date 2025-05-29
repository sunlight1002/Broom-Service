import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import { useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import axios from "axios";

export default function EditService() {

    const { t } = useTranslation();
    const [service, setService] = useState("");
    const [serviceHeb, setServiceHeb] = useState("");
    const [template, setTemplate] = useState("");
    const [status, setStatus] = useState("0");
    const [errors, setErrors] = useState([]);
    const alert = useAlert();
    const navigate = useNavigate();
    const params = useParams();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        const data = {
            name: service,
            heb_name: serviceHeb,
            template: template,
            status: status,
        };

        axios
            .put(`/api/admin/services/${params.id}`, data, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    alert.success("Service has been updated successfully");
                    setTimeout(() => {
                        navigate("/admin/services");
                    }, 1000);
                }
            });
    };

    const getService = () => {
        axios
            .get(`/api/admin/services/${params.id}/edit`, { headers })
            .then((res) => {
                setService(res.data.service.name);
                setServiceHeb(res.data.service.heb_name);
                setTemplate(res.data.service.template);
                setStatus(res.data.service.status);
            });
    };

    useEffect(() => {
        getService();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title addEmployer">{t("global.edit")} {t("global.service")}</h1>
                    <div className="card">
                        <div className="card-body">
                            <form>
                                <div className="row">
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                            {t("global.service")} - En*
                                            </label>
                                            <input
                                                type="text"
                                                value={service}
                                                onChange={(e) =>
                                                    setService(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter service name (english)"
                                            />
                                            {errors.service && (
                                                <small className="text-danger mb-1">
                                                    {errors.service}
                                                </small>
                                            )}
                                        </div>

                                        <div className="form-group">
                                            <label className="control-label">
                                            {t("global.service")} - Heb*
                                            </label>
                                            <input
                                                type="text"
                                                value={serviceHeb}
                                                onChange={(e) =>
                                                    setServiceHeb(
                                                        e.target.value
                                                    )
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter service name (hebrew)"
                                            />
                                            {errors.service && (
                                                <small className="text-danger mb-1">
                                                    {errors.heb_name}
                                                </small>
                                            )}
                                        </div>
                                    </div>

                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                            {t("admin.global.template")}
                                            </label>
                                            <select
                                                className="form-control"
                                                value={template}
                                                onChange={(e) =>
                                                    setTemplate(e.target.value)
                                                }
                                            >
                                                <option value="">
                                                {t("worker.settings.pleaseSelect")}
                                                </option>
                                                <option value="regular">
                                                {t("services.regularServices")}( 2*, 3*,
                                                    4*, 5* )
                                                </option>
                                                <option value="office_cleaning">
                                                {t("services.officeCleaning")}
                                                </option>
                                                <option value="after_renovation">
                                                {t("services.afterRenovation")}
                                                </option>
                                                <option value="thorough_cleaning">
                                                {t("services.throughCleaning")}
                                                </option>
                                                <option value="window_cleaning">
                                                {t("services.windowCleaning")}
                                                </option>
                                                <option value="polish">
                                                {t("services.polish")}
                                                </option>
                                                <option value="airbnb">
                                                {t("services.airBnb")}
                                                </option>
                                                <option value="others">
                                                {t("services.others")}
                                                </option>
                                            </select>
                                            {errors.template && (
                                                <small className="text-danger mb-1">
                                                    {errors.template}
                                                </small>
                                            )}
                                        </div>
                                    </div>

                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("global.status")}
                                            </label>
                                            <select
                                                className="form-control"
                                                value={status}
                                                onChange={(e) =>
                                                    setStatus(e.target.value)
                                                }
                                            >
                                                <option value="">
                                                {t("worker.settings.pleaseSelect")}
                                                </option>
                                                <option value="1">
                                                {t("admin.global.active")}
                                                </option>
                                                <option value="0">
                                                {t("admin.global.inactive")}
                                                </option>
                                            </select>
                                            {errors.status && (
                                                <small className="text-danger mb-1">
                                                    {errors.status}
                                                </small>
                                            )}
                                        </div>
                                    </div>

                                    <div className="form-group text-center col-sm-12">
                                        <input
                                            type="submit"
                                            onClick={handleUpdate}
                                            value={t("client.jobs.review.Submit")}
                                            className="btn navyblue saveBtn"
                                        />
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
