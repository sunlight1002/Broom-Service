import axios from "axios";
import React, { useState } from "react";
import Sidebar from "../../Layouts/WorkerSidebar";
import { useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function AddRefundClaim() {
    const { t, i18n } = useTranslation();
    const [workerId, setWorkerId] = useState("");
    const [date, setDate] = useState("");
    const [amount, setAmount] = useState("");
    const [billFiles, setBillFiles] = useState(null);
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
        formData.append("user_id", workerId);
        formData.append("date", date);
        formData.append("amount", amount);
        
        if (billFiles) {
            formData.append("bill_file", billFiles);
        }

        axios
            .post(`/api/refund-claims`, formData, { headers })
            .then((res) => {
                if (res.data.errors) {
                    setLoading(false);
                    setErrors(res.data.errors);
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e]);
                    }
                } else {
                    setLoading(false);
                    alert.success("Request added successfully!");
                    setTimeout(() => {
                        navigate("/worker/refund-claim");
                    }, 1000);
                }
            })
            .catch((error) => {
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
                <h1 className="page-title">Add Leaves</h1>
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
                                    <input
                                        type="file"
                                        className="form-control"
                                        onChange={(e) => setBillFiles(e.target.files[0])}
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
