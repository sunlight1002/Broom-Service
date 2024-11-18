import React, { useState, useEffect } from "react";
import Sidebar from "../../js/Admin/Layouts/Sidebar";
import { Link, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import axios from "axios";

const ScheduleRequestDetails = () => {
    const { t } = useTranslation();
    // const [worker, setWorker] = useState(null);
    const [schedule, setSchedule] = useState([])

    const params = useParams();
    console.log(params);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const getWorker = async () => {
        const response = await axios.get(`/api/admin/schedule-change/${params.id}`, { headers })
        console.log(response);
        setSchedule(response.data?.scheduleChange);
    };

    useEffect(() => {
        getWorker();
    }, [])


    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{schedule?.user_type === "App\\Models\\User" ? t("worker.viewWorker") + " Request" : "View Client Request"}</h1>
                        </div>
                    </div>
                </div>
                <div className="worker-profile">
                    <h2>{schedule?.user?.firstname} {schedule?.user?.lastname}</h2>
                    <div className="dashBox p-0 p-md-4 mb-3">
                        <form>
                            <div className="row">
                                <div className='col-sm-4'>
                                    <div className='form-group'>
                                        <label className='control-label'>User Type</label>
                                        <p>{schedule?.user_type === "App\\Models\\User" ? "Worker" : "Client"}</p>
                                    </div>
                                </div>
                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("admin.leads.viewLead.Phone")}
                                        </label>
                                        <p>
                                            <a href={`tel:+${schedule?.user?.phone}`}>
                                                +{schedule?.user?.phone}
                                            </a>
                                        </p>
                                    </div>
                                </div>
                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.gender")}
                                        </label>
                                        <p>{schedule?.user?.gender}</p>
                                    </div>
                                </div>
                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("work-contract.email")}
                                        </label>
                                        <p className="word-break">{schedule?.user?.email}</p>
                                    </div>
                                </div>

                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.status")}
                                        </label>
                                        <p>
                                            {schedule?.status}
                                        </p>
                                    </div>
                                </div>
                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Comments
                                        </label>
                                        <p>
                                            {schedule?.comments}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default ScheduleRequestDetails