import axios from "axios";
import React, { useState } from "react";
import Sidebar from "../../Layouts/WorkerSidebar";
import { useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function AddSickLeave() {
    const { t, i18n } = useTranslation();
    const [workerId, setWorkerId] = useState("");
    const [startDate, setStartDate] = useState("");
    const [endDate, setEndDate] = useState("");
    const [reason, setReason] = useState("");
    const [doctorReport, setDoctorReport] = useState(null);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState(null);

    const alert = useAlert();
    const navigate = useNavigate();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data", // Important for file upload
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);

        const formData = new FormData();
        formData.append("worker_id", workerId);
        formData.append("start_date", startDate);
        formData.append("end_date", endDate);
        formData.append("reason_for_leave", reason);

        if (doctorReport) {
            formData.append("doctor_report", doctorReport);
        }

        axios
            .post(`/api/sick-leaves`, formData, { headers })
            .then((res) => {
                if (res.data.errors) {
                    setLoading(false);
                    setErrors(res.data.errors);
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e]);
                    }
                } else {
                    setLoading(false);
                    alert.success("Sick leave added successfully!");
                    setTimeout(() => {
                        navigate("/worker/leaves");
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
                <h1 className="page-title">Add Leaves</h1>
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="col-lg-6 col-12">
                            <div className="dashBox p-4">

                                <div className="form-group">
                                    <label className="control-label">
                                      {t("global.startDate")}
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
                                       {t("worker.endDate")}
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
                                    <label className="control-label">
                                    {t("worker.doctorReport")}
                                    </label>
                                    <input
                                        type="file"
                                        className="form-control"
                                        onChange={(e) => setDoctorReport(e.target.files[0])}
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                    {t("worker.reason")}
                                    </label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={reason}
                                        onChange={(e) => setReason(e.target.value)}

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