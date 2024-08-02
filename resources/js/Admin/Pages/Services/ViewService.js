import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import { useParams } from "react-router-dom";
import axios from "axios";
import Comments from "../../Components/common/Comments";
import { SubService } from "./SubService";

export default function ViewService() {
    const [service, setService] = useState(null);
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

    const templates = {
        regular: "Regular Services( 2*, 3*, 4*, 5* )",
        office_cleaning: "Office Cleaning",
        after_renovation: "After Renovation",
        thorough_cleaning: "Thorough Cleaning",
        window_cleaning: "Window Cleaning",
        polish: "Polish",
        airbnb: "airbnb",
        others: "Others",
    };

    const getService = () => {
        axios
            .get(`/api/admin/services/${params.id}/edit`, { headers })
            .then((res) => {
                setService(res.data.service);
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
                <h1 className="page-title">View Service</h1>
                <div className="card">
                    <div className="card-body">
                        <div className="row">
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label>Service - En</label>
                                    <p>{service ? service.name : "NA"}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label>Service - Heb</label>
                                    <p>{service ? service.heb_name : "NA"}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label>Template</label>
                                    <p>
                                        {service
                                            ? templates[service.template]
                                            : "NA"}
                                    </p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label>Status</label>
                                    <p>
                                        {service
                                            ? service.status == "1"
                                                ? "Active"
                                                : "Inactive"
                                            : "NA"}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {service && (
                            <>
                                <ul className="nav nav-tabs" role="tablist">
                                    <li
                                        className="nav-item"
                                        role="presentation"
                                    >
                                        <a
                                            id="comments-tab"
                                            className="nav-link active"
                                            data-toggle="tab"
                                            href="#tab-comments"
                                            aria-selected="true"
                                            role="tab"
                                        >
                                            Comments
                                        </a>
                                    </li>
                                    <li
                                        className="nav-item"
                                        role="presentation"
                                    >
                                        <a
                                            id="comments-tab"
                                            className="nav-link "
                                            data-toggle="tab"
                                            href="#tab-subServices"
                                            aria-selected="true"
                                            role="tab"
                                        >
                                            Sub Services
                                        </a>
                                    </li>
                                </ul>

                                <div className="tab-content">
                                    <div
                                        id="tab-comments"
                                        className="tab-pane active show"
                                        role="tab-panel"
                                        aria-labelledby="comments-tab"
                                    >
                                        <Comments
                                            relationID={service.id}
                                            routeType="services"
                                        />
                                    </div>
                                    <div
                                        id="tab-subServices"
                                        className="tab-pane"
                                        role="tab-panel"
                                        aria-labelledby="subServices-tab"
                                    >
                                       <SubService
                                       params={params}
                                       />
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
