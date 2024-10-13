import axios from "axios";
import React, { useState } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function AddHoliday() {
    const { t, i18n } = useTranslation();
    const [holidayName, setHolidayName] = useState("");
    const [startDate, setStartDate] = useState("");
    const [endDate, setEndDate] = useState("");
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState(null);

    const alert = useAlert();
    const navigate = useNavigate();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);

        const data = {
            holiday_name: holidayName,
            start_date: startDate,
            end_date: endDate,
        };

        axios
            .post(`/api/admin/holidays`, data, { headers })
            .then((res) => {
                if (res.data.errors) {
                    setLoading(false);
                    setErrors(res.data.errors);
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e]);
                    }
                } else {
                    setLoading(false);
                    alert.success("Holiday added successfully!");
                    setTimeout(() => {
                        navigate("/admin/holidays");
                    }, 1000);
                }
            })
            .catch((error) => {
                setLoading(false);
                if (error.response && error.response.data.errors) {
                    const errors = error.response.data.errors;
                    Object.keys(errors).forEach((field) => {
                        alert.error(errors[field][0]);
                    },2000);
                } else {
                    alert.error("An unexpected error occurred.");
                }
            });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">{t("admin.holidays.addNewHoliday")}</h1>
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="col-lg-6 col-12">
                            <div className="dashBox p-0 p-md-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("admin.holidays.holidayName")}
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={holidayName}
                                        onChange={(e) => setHolidayName(e.target.value)}
                                        placeholder="Enter holiday name"

                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("admin.holidays.startDate")}
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={startDate}
                                        onChange={(e) => setStartDate(e.target.value)}

                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("admin.holidays.endDate")}
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}

                                    />
                                </div>
                                <button type="submit" className="btn btn-primary">
                                    {t("global.Submit")}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                {loading && <FullPageLoader />}
            </div>
        </div>
    );
}
