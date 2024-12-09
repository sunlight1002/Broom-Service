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
    const [dayType, setDayType] = useState("fullDay"); // Single state for day selection
    const [halfType, setHalfType] = useState(""); // State for half-day type
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
            day_type: dayType,
            half_type: halfType,
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
                alert.error("An unexpected error occurred.");
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
                                        required
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
                                        required
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
                                        required
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label d-block">{t("admin.holidays.day")} *</label>
                                    <div className="">
                                        <label className="radio">
                                            <input
                                                type="radio"
                                                name="day"
                                                value="fullDay"
                                                checked={dayType === "fullDay"}
                                                onChange={(e) => setDayType(e.target.value)} 
                                                className="mr-2"
                                            />
                                            <span className="">{t("admin.holidays.fullDay")}</span>
                                        </label>
                                        <label className="radio mt-0">
                                            <input
                                                type="radio"
                                                name="day"
                                                value="halfDay"
                                                checked={dayType === "halfDay"}
                                                onChange={(e) => setDayType(e.target.value)} 
                                                className="mr-2"
                                            />
                                            <span className="">{t("admin.holidays.halfDay")}</span>
                                        </label>
                                        {dayType === "halfDay" && ( 
                                            <div className="form-group">
                                                <div className="d-flex">
                                                    <label className="radio">
                                                        <input
                                                            type="radio"
                                                            name="half"
                                                            value="firstHalf"
                                                            checked={halfType === "firstHalf"}
                                                            onChange={(e) => setHalfType(e.target.value)} 
                                                            className="mr-2"
                                                        />
                                                        <span className="">{t("admin.holidays.firstHalf")}</span>
                                                    </label>
                                                    <label className="radio mt-0 mx-1">
                                                        <input
                                                            type="radio"
                                                            name="half"
                                                            value="secondHalf"
                                                            checked={halfType === "secondHalf"}
                                                            onChange={(e) => setHalfType(e.target.value)} 
                                                            className="mr-2"
                                                        />
                                                        <span className="">{t("admin.holidays.secondHalf")}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        )}
                                    </div>


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
