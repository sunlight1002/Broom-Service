import axios from "axios";
import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function EditHoliday() {
    const { t } = useTranslation();
    const [startDate, setStartDate] = useState("");
    const [endDate, setEndDate] = useState("");
    const [holidayName, setHolidayName] = useState("");
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState(null);

    const { id } = useParams(); // Get the holiday ID from the URL
    const alert = useAlert();
    const navigate = useNavigate();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    // Fetch the holiday details on component mount
    useEffect(() => {
        setLoading(true);
        axios.get(`/api/admin/holidays/${id}`, { headers })
            .then((res) => {
                setStartDate(res.data.start_date);
                setEndDate(res.data.end_date);
                setHolidayName(res.data.holiday_name);
                setLoading(false);
            })
            .catch((error) => {
                setLoading(false);
                alert.error("Failed to load holiday details");
            });
    }, [id]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        const data = {
            start_date: startDate,
            end_date: endDate,
            holiday_name: holidayName,
        };

        axios.post(`/api/admin/holidays/${id}`, data, { headers }).then((res) => {
            if (res.data.errors) {
                setLoading(false);
                setErrors(res.data.errors);
                for (let e in res.data.errors) {
                    alert.error(res.data.errors[e]);
                }
            } else {
                setLoading(false);
                alert.success("Holiday updated successfully");
                setTimeout(() => {
                    navigate("/admin/holidays");
                }, 1000);
            }
        }).catch((error) => {
            setLoading(false);
            alert.error("Failed to update holiday");
        });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <h1 className="page-title">{t("admin.holidays.editHoliday")}</h1>
                {loading && <FullPageLoader />}
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="col-lg-6 col-12">
                            <div className="dashBox p-0 p-md-4">
                                <div className="form-group">
                                    <label className="control-label">
                                       {t("admin.holidays.startDate")}
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={startDate}
                                        onChange={(e) =>
                                            setStartDate(e.target.value)
                                        }
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
                                        onChange={(e) =>
                                            setEndDate(e.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                      {t("admin.holidays.holidayName")}
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={holidayName}
                                        onChange={(e) =>
                                            setHolidayName(e.target.value)
                                        }
                                        required
                                    />
                                </div>
                                <button
                                    type="submit"
                                    className="btn btn-primary"
                                    disabled={loading}
                                >
                                    {t("admin.holidays.update")}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
}
