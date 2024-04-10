import React, { useState, useEffect } from "react";
import "rsuite/dist/rsuite.min.css";
import axios from "axios";
import moment from "moment";
import { useParams } from "react-router-dom";
import Swal from "sweetalert2";
import { Base64 } from "js-base64";

import ClientSidebar from "../../Layouts/ClientSidebar";
import ChangeWorkerCalender from "../../Component/Job/ChangeWorkerCalender";

export default function ChangeWorkerRequest() {
    const params = useParams();
    const [job, setJob] = useState(null);
    const [hasPendingRequest, setHasPendingRequest] = useState(false);

    const jobId = Base64.decode(params.id);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const getJob = () => {
        axios
            .get(`/api/client/jobs/${jobId}`, { headers })
            .then((response) => {
                const _job = response.data.job;
                setJob(_job);

                const _hasPendingRequest = _job.change_worker_requests.filter(
                    (i) => i.status == "pending"
                ).length;

                setHasPendingRequest(_hasPendingRequest);
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

    return (
        <div id="container">
            <ClientSidebar />
            <div id="content">
                <div className="view-applicant">
                    <h1 className="page-title editJob">
                        Change Worker Request
                    </h1>
                    <div id="calendar"></div>
                    <div className="card">
                        {job && (
                            <div className="card-body">
                                <form>
                                    <div className="row">
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>Services</label>
                                                <p>{job.jobservice.name}</p>
                                            </div>
                                        </div>
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>Frequency</label>
                                                <p>
                                                    {job.jobservice.freq_name}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>Time to Complete</label>
                                                <p>
                                                    {job.jobservice.jobHours}{" "}
                                                    hours
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>Property</label>
                                                <p>
                                                    {
                                                        job.property_address
                                                            .address_name
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>Pet animals</label>
                                                <p>
                                                    {job.property_address
                                                        .is_cat_avail
                                                        ? "Cat ,"
                                                        : job.property_address
                                                              .is_dog_avail
                                                        ? "Dog"
                                                        : !job.property_address
                                                              .is_cat_avail &&
                                                          !job.property_address
                                                              .is_dog_avail
                                                        ? "NA"
                                                        : ""}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>Gender preference</label>
                                                <p
                                                    style={{
                                                        textTransform:
                                                            "capitalize",
                                                    }}
                                                >
                                                    {
                                                        job.property_address
                                                            .prefer_type
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="row">
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>Worker</label>
                                                {job.worker ? (
                                                    <p>
                                                        {job.worker.firstname +
                                                            " " +
                                                            job.worker.lastname}
                                                    </p>
                                                ) : (
                                                    <p>NA</p>
                                                )}
                                            </div>
                                        </div>
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>Date</label>
                                                <p>
                                                    {moment(job.start_date)
                                                        .toString()
                                                        .slice(0, 15)}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>Shift</label>
                                                <p>{job.shifts}</p>
                                            </div>
                                        </div>
                                    </div>
                                    {!hasPendingRequest ? (
                                        <div className="row">
                                            <div className="col-sm-12">
                                                <div className="mt-3 mb-3">
                                                    <h3 className="text-center">
                                                        Worker Availability
                                                    </h3>
                                                    <p className="text-center text-danger">
                                                        Assign a specific worker
                                                        only and choose a shift
                                                        for a single date.
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="col-sm-12">
                                                <ChangeWorkerCalender
                                                    job={job}
                                                />
                                                <div className="mb-3">
                                                    &nbsp;
                                                </div>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="row">
                                            <div className="col-sm-12">
                                                <p className="text-center text-info">
                                                    Request is pending.
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </form>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
