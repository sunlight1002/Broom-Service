import React, { useState, useEffect } from "react";
import WorkerHistory from "../../Components/Workers/WorkerHistory";
import WorkerProfile from "../../Components/Workers/WorkerProfile";
import Sidebar from "../../Layouts/Sidebar";
import { Link, useParams } from "react-router-dom";

export default function ViewWorker() {
    const [worker, setWorker] = useState(null);

    const params = useParams();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getWorker = () => {
        axios
            .get(`/api/admin/workers/${params.id}/edit`, { headers })
            .then((response) => {
                setWorker(response.data.worker);
            });
    };

    useEffect(() => {
        getWorker();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">View Worker</h1>
                        </div>
                        <div className="col-sm-6">
                            <div className="search-data">
                                <Link
                                    to={`/admin/edit-worker/${params.id}`}
                                    className="btn btn-pink addButton"
                                >
                                    <i className="btn-icon fas fa-pencil"></i>
                                    Edit
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                {worker && (
                    <div className="view-applicant">
                        <WorkerProfile worker={worker} />
                        <WorkerHistory worker={worker} />
                    </div>
                )}
            </div>
        </div>
    );
}
