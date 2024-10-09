import axios from "axios";
import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/WorkerSidebar";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import { Modal } from "react-bootstrap";

export default function EditLeaves() {
    const { t, i18n } = useTranslation();
    const [workerId, setWorkerId] = useState("");
    const [startDate, setStartDate] = useState("");
    const [endDate, setEndDate] = useState("");
    const [reason, setReason] = useState("");
    const [doctorReport, setDoctorReport] = useState(null); // For new file uploads
    const [doctorReportUrl, setDoctorReportUrl] = useState(""); // For existing report URL
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState(null);
    const [showModal, setShowModal] = useState(false);

    const handleModalOpen = () => setShowModal(true);
    const handleModalClose = () => setShowModal(false);

    const alert = useAlert();
    const navigate = useNavigate();
    const { id } = useParams(); // Assuming the leave ID is passed in the URL

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data", // Important for file upload
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    useEffect(() => {
        setLoading(true);
        axios
            .get(`/api/sick-leaves/${id}`, { headers })
            .then((res) => {
                setWorkerId(res.data.worker_id);
                setStartDate(res.data.start_date);
                setEndDate(res.data.end_date);
                setReason(res.data.reason_for_leave);
                if (res.data.doctor_report_url) {
                    setDoctorReportUrl(res.data.doctor_report_url); // Store existing report URL
                }
                setLoading(false);
            })
            .catch((error) => {
                setLoading(false);
                alert.error("Failed to load the leave data.");
            });
    }, [id]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);

        const formData = new FormData();
        formData.append("start_date", startDate);
        formData.append("end_date", endDate);
        formData.append("reason_for_leave", reason);
        formData.append('_method', 'PUT');

        if (doctorReport) {
            formData.append("doctor_report", doctorReport);
        }

        axios
            .post(`/api/sick-leaves/${id}`, formData, { headers })
            .then((res) => {
                setLoading(false);
                if (res.data.errors) {
                    setErrors(res.data.errors);
                    for (let error in res.data.errors) {
                        alert.error(res.data.errors[error]);
                    }
                } else {
                    alert.success("Sick leave updated successfully!");
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
                <h1 className="page-title">Edit Leaves</h1>
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
                                    {doctorReportUrl && (
                                        <div className="mb-3">
                                            <img
                                                src={doctorReportUrl}
                                                style={{ maxWidth: "200px", maxHeight: "200px", cursor: "pointer" }}
                                                onClick={handleModalOpen} // Open modal on click
                                            />
                                        </div>
                                    )}
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
                                <Modal show={showModal} onHide={handleModalClose} centered>
                                    <Modal.Header closeButton>
                                        <Modal.Title>Doctor's Report</Modal.Title>
                                    </Modal.Header>
                                    <Modal.Body className="text-center">
                                        <img
                                            src={doctorReportUrl}
                                            alt="Doctor's Report"
                                            style={{ maxWidth: "100%", maxHeight: "80vh" }}
                                        />
                                    </Modal.Body>
                                </Modal>
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