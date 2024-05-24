import React, { useState, useEffect } from "react";
import "rsuite/dist/rsuite.min.css";
import axios from "axios";
import moment from "moment";
import { useParams } from "react-router-dom";
import Swal from "sweetalert2";
import { Base64 } from "js-base64";
import { useTranslation } from "react-i18next";

import ClientSidebar from "../../Layouts/ClientSidebar";
import ChangeScheduleCalender from "../../Component/Job/ChangeScheduleCalender";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";

export default function ChangeSchedule() {
    const params = useParams();
    const [job, setJob] = useState(null);
    const { t } = useTranslation();

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
                        {t("client.jobs.change.title")}
                    </h1>
                    <div id="calendar"></div>
                    <div className="card">
                        {job && (
                            <div className="card-body">
                                <form>
                                    <div className="row">
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>
                                                    {t(
                                                        "client.jobs.change.services"
                                                    )}
                                                </label>
                                                <p>{job.jobservice.name}</p>
                                            </div>
                                        </div>
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>
                                                    {t(
                                                        "client.jobs.change.frequency"
                                                    )}
                                                </label>
                                                <p>
                                                    {job.jobservice.freq_name}
                                                </p>
                                            </div>
                                        </div>
                                        {/* <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>
                                                    {t(
                                                        "client.jobs.change.time_to_complete"
                                                    )}
                                                </label>
                                                <p>
                                                    {convertMinsToDecimalHrs(
                                                        job.jobservice
                                                            .duration_minutes
                                                    )}{" "}
                                                    hours
                                                </p>
                                            </div>
                                        </div> */}
                                        <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>
                                                    {t(
                                                        "client.jobs.change.property"
                                                    )}
                                                </label>
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
                                                <label>
                                                    {t(
                                                        "client.jobs.change.pet_animals"
                                                    )}
                                                </label>
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
                                                <label>
                                                    {t(
                                                        "client.jobs.change.gender_preference"
                                                    )}
                                                </label>
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
                                                <label>
                                                    {t(
                                                        "client.jobs.change.worker"
                                                    )}
                                                </label>
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
                                                <label>
                                                    {t(
                                                        "client.jobs.change.date"
                                                    )}
                                                </label>
                                                <p>
                                                    {moment(job.start_date)
                                                        .toString()
                                                        .slice(0, 15)}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label>
                                                    {t(
                                                        "client.jobs.change.shift"
                                                    )}
                                                </label>
                                                <p>{job.shifts}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="row">
                                        <div className="col-sm-12">
                                            <ChangeScheduleCalender job={job} />
                                            <div className="mb-3">&nbsp;</div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
