import React, { useEffect, useRef, useState } from "react";
import { Link, useParams } from "react-router-dom";
import axios from "axios";
import { useAlert } from "react-alert";

import Sidebar from "../../Layouts/Sidebar";

export default function WorkerAffectedAvailability() {
    const [affected, setAffected] = useState(null);
    const alert = useAlert();
    const params = useParams();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const weekDays = [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
    ];

    const handleApprove = (e) => {
        e.preventDefault();

        axios
            .post(
                `/api/admin/worker-affected-availability/${params.id}/approve`,
                {},
                { headers }
            )
            .then((res) => {
                alert.success(res.data.message);
                getAffected()
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });
    };

    const handleReject = (e) => {
        e.preventDefault();

        axios
            .post(
                `/api/admin/worker-affected-availability/${params.id}/reject`,
                {},
                { headers }
            )
            .then((res) => {
                alert.success(res.data.message);
                getAffected()
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });
    };

    const getAffected = () => {
        axios
            .get(`/api/admin/worker-affected-availability/${params.id}`, {
                headers,
            })
            .then((response) => {
                if (response.data.data) {
                    setAffected(response.data.data);
                }
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });
    };

    const arrayToTime = (_time) => {
        return _time.map((i) => i.start_time + "-" + i.end_time);
    };

    useEffect(() => {
        getAffected();
    }, [params.id]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">
                                Worker Affected Availability
                            </h1>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        {affected && (
                            <>
                                <div>
                                    <p>
                                        <strong>Worker: </strong>

                                        <span>
                                            {affected.worker.firstname}{" "}
                                            {affected.worker.lastname}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p>
                                        <strong>Date: </strong>

                                        <span>{affected.old_values.date}</span>
                                    </p>
                                </div>

                                <div>
                                    <p>
                                        <strong>Old Time: </strong>

                                        <span>
                                            {arrayToTime(
                                                affected.old_values.time_by_date
                                            )}
                                        </span>
                                    </p>
                                </div>

                                <div>
                                    <p>
                                        <strong>New Time: </strong>

                                        <span>
                                            {arrayToTime(
                                                affected.new_values.time_by_date
                                            )}
                                        </span>
                                    </p>
                                </div>

                                <div>
                                    <p>
                                        <strong>Weekday: </strong>

                                        <span>
                                            {
                                                weekDays[
                                                    affected.old_values.weekday
                                                ]
                                            }
                                        </span>
                                    </p>
                                </div>

                                <div>
                                    <p>
                                        <strong>Old Time: </strong>

                                        <span>
                                            {arrayToTime(
                                                affected.old_values
                                                    .time_by_weekday
                                            )}
                                        </span>
                                    </p>
                                </div>

                                <div>
                                    <p>
                                        <strong>New Time: </strong>

                                        <span>
                                            {arrayToTime(
                                                affected.new_values
                                                    .time_by_weekday
                                            )}
                                        </span>
                                    </p>
                                </div>

                                {affected.status == "pending" && (
                                    <div className="mt-4">
                                        <button
                                            className="btn btn-success addButton"
                                            onClick={(e) => handleApprove(e)}
                                        >
                                            Approve
                                        </button>

                                        <button
                                            className="btn btn-danger addButton ml-2"
                                            onClick={(e) => handleReject(e)}
                                        >
                                            Reject
                                        </button>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
