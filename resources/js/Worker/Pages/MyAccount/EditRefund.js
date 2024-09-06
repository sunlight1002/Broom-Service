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
    const [date, setDate] = useState("");
    const [amount, setAmount] = useState("");
    const [billFiles, setBillFiles] = useState(null);
    const [billFilesUrl, setBillFilesUrl] = useState(" "); // For existing report URL
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
            .get(`/api/refund-claims/${id}`, { headers })
            .then((res) => {
                setWorkerId(res.data.user_id);
                setDate(res.data.date);
                setAmount(res.data.amount);
                
                if (res.data.bill_file_url) {
                    setBillFilesUrl(res.data.bill_file_url); // Store existing report URL
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
        formData.append("date", date);
        formData.append("amount", amount);
        
        if (billFiles) {
            formData.append("bill_file", billFiles);
        }
    
        axios
        .post(`/api/refund-claims/${id}`, formData, { headers })
        .then((res) => {
            setLoading(false);
            if (res.data.errors) {
                setErrors(res.data.errors);
                for (let error in res.data.errors) {
                    alert.error(res.data.errors[error]);
                }
            } else {
                alert.success("Request updated successfully!");
                setTimeout(() => {
                    navigate("/worker/refund-claim");
                }, 1000);
            }
        })
        .catch((error) => {
            setLoading(false);
            if (error.response && error.response.status === 403) {
                alert.error(error.response.data.error); 
            } else if (error.response && error.response.data.errors) {
                const errors = error.response.data.errors;
                Object.keys(errors).forEach((field) => {
                    alert.error(errors[field][0]);
                }, 2000);
            } else {
                alert.error("An unexpected error occurred.");
            }
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
                                      Date
                                    </label>
                                    <input
                                        type="date"
                                        className="form-control"
                                        value={date}
                                        onChange={(e) => setDate(e.target.value)}
                    
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                      Amount
                                    </label>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={amount}
                                        onChange={(e) => setAmount(e.target.value)}
                                       
                                    />
                                </div>
                                <div className="form-group">
                                    <label className="control-label">
                                    Bill File
                                    </label>
                                    {billFilesUrl && (
                                        <div className="mb-3">
                                            <img
                                                src={billFilesUrl}
                                                style={{ maxWidth: "200px", maxHeight: "200px", cursor: "pointer" }}
                                                onClick={handleModalOpen} // Open modal on click
                                            />
                                        </div>
                                    )}
                                    <input
                                        type="file"
                                        className="form-control"
                                        onChange={(e) => setBillFiles(e.target.files[0])}
                                    />
                                </div>
                               
                                <Modal show={showModal} onHide={handleModalClose} centered>
                                    <Modal.Header closeButton>
                                        <Modal.Title>Doctor's Report</Modal.Title>
                                    </Modal.Header>
                                    <Modal.Body className="text-center">
                                        <img
                                            src={billFilesUrl}
                                            alt="Bill Image"
                                            style={{ maxWidth: "100%", maxHeight: "90vh" }}
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
