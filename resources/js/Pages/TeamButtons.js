import axios from "axios";
import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import Moment from "moment";
import i18next from "i18next";
import { Base64 } from "js-base64";
import logo from "../Assets/image/sample.svg";
import { useAlert } from "react-alert";
import { Link } from "react-router-dom";
import Swal from "sweetalert2";
import useToggle from "../Hooks/useToggle";
import { useTranslation } from "react-i18next";

export default function TeamButtons() {
    const { t } = useTranslation();
    const alert = useAlert();
    const [clientID, setClientID] = useState(null)
    const [workerID, setWorkerID] = useState(null)
    const [job, setJob] = useState([]);

    const params = useParams();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };


    const handleApproveJob = () => {
        axios
            .post(
                `/api/admin/worker/${workerID}/jobs/${Base64.decode(params.id)}/approve`, {}, { headers })
            .then((res) => {
                getJob();
                alert.success(res.data.data);
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });
    };

    const getJob = () => {
        axios
            .get(`/api/admin/jobs/${Base64.decode(params.id)}`, { headers })
            .then((res) => {
                const r = res.data.job;
                console.log(res);

                setJob(r);
                setClientID(r.client.id)
                setWorkerID(r?.worker_id)

            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };
    useEffect(() => {
        getJob();
    }, []);

    const handleOpeningTime = () => {
        let data = {
            job_id: Base64.decode(params.id),
            worker_id: workerID
        };

        axios
            .post(`/api/admin/job-opening-timestamp`, data, { headers })
            .then((res) => {
                alert.success(res.data.message); // Show success message if everything is fine
                getJob()
            })
            .catch((err) => {
                // Check if the response has the message, and handle different types of errors accordingly
                if (err.response) {
                    // If the response exists, display the error message from the server
                    alert.error(err.response.data.message);
                } else {
                    // Handle any other error (like network errors)
                    console.log(err);
                    alert.error("Something went wrong.");
                }
            });
    };


    const startJob = async () => {
        let data = {
            job_id: Base64.decode(params.id),
            worker_id: workerID
        };
        try {
            const response = await axios.post(`/api/admin/jobs/start-time`, data, { headers });
            getJob();

            // Handle successful response
            alert.success(response.data.message);
        } catch (error) {
            if (error.response && error.response.status === 404) {
                alert.error('End timer');
            } else {
                alert.error('Something went wrong. Please try again.');
            }
        }
    };



    // const startTimer = () => {

    //     axios
    //         .post(`/api/jobs/${params.id}/start-time`, {}, { headers })
    //         .then((res) => {
    //             getTime();
    //             setTimeout(() => {
    //                 setIsSubmitting(false);
    //             }, 500);
    //         });
    // };
    // const stopTimer = () => {
    //     axios
    //         .post(`/api/jobs/${params.id}/end-time`, {}, { headers })
    //         .then((res) => {
    //             getTimes();
    //             setTimeout(() => {
    //                 setIsSubmitting(false);
    //             }, 500);
    //         });
    // };

    const handleSpeakToManager = async (e) => {
        e.preventDefault();

        const data = {
            job_id: params.id,
            client_id: clientID,
            worker_id: workerID,
            problem: problem
        };

        try {
            const res = await axios.post(`/api/client/jobs/speak-to-manager`, data, { headers });
            getJob();
            alert.success(res?.data?.message)
        } catch (error) {
            if (error.response) {
                console.error("Error response: ", error.response.data);
            } else if (error.request) {
                console.error("No response received: ", error.request);
            } else {
                console.error("Error in request setup: ", error.message);
            }
        }
    };

    return (
        <div className="container meeting" style={{ display: "block" }}>
            <div className="thankyou meet-status dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                <div className="cta">
                    <div id="content">
                        <div className="titleBox customer-title">
                            <div className="row">
                                <div className="col-sm-6">
                                    <h1 className="page-title">
                                        Buttons:
                                    </h1>
                                </div>

                            </div>
                        </div>
                        {job && (
                            <div className="comment-details mb-3">
                                <p>Details</p>
                                <p>Client: {job?.client?.firstname} {job?.client?.lastname}</p>
                                <p>Worker: {job?.worker?.firstname} {job?.client?.lastname}</p>
                                <p>Property Address: {job?.property_address?.geo_address}</p>
                            </div>
                        )}
                        <div className="card">
                            <div className="card-body d-flex justify-content-around align-items-center flex-wrap">
                                <div className="ml-2">
                                    <Link
                                        className="btn btn-pink addButton"
                                        style={{ width: "9rem" }}
                                        to={`/admin/jobs/view/${job.id}`}
                                    >
                                        Cancel Worker
                                    </Link>
                                </div>

                                {
                                    job && job?.job_opening_timestamp == null && (
                                        <div className="ml-2">
                                            <button
                                                className="btn btn-pink addButton mt-2"
                                                style={{ textTransform: "none", width: "9rem" }}
                                                type="button"
                                                onClick={handleOpeningTime}
                                            >
                                                On My Way
                                            </button>
                                        </div>
                                    )
                                }

                                {
                                    job && job?.worker_approved_at != null && job?.job_opening_timestamp != null
                                     && job?.hours[0]?.job_id != Base64.decode(params.id) && (
                                        <div className="ml-2">
                                            <button
                                                className="btn btn-pink addButton mt-2"
                                                style={{ textTransform: "none", width: "9rem" }}
                                                type="button"
                                                onClick={startJob}
                                            >
                                                Start Job/Time
                                            </button>
                                        </div>
                                    )
                                }

                                {
                                    job && job?.worker_approved_at == null && (
                                        <div className="ml-2">
                                            <button
                                                className="btn btn-pink addButton mt-2"
                                                style={{ textTransform: "none", width: "9rem" }}
                                                type="button"
                                                onClick={handleApproveJob}
                                            >
                                                Approve
                                            </button>
                                        </div>
                                    )
                                }

                                <div className="ml-2">
                                    <Link
                                        className="btn btn-pink addButton"
                                        style={{ width: "9rem" }}
                                        to={`/admin/jobs/${job.id}/change-worker`}
                                    >
                                        Change Worker
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
