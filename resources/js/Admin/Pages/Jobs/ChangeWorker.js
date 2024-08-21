import React, { useState, useEffect } from "react";
import "rsuite/dist/rsuite.min.css";
import axios from "axios";
import moment from "moment";
import { useParams } from "react-router-dom";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";

import Sidebar from "../../Layouts/Sidebar";
import ChangeWorkerCalender from "../../Components/Job/ChangeWorkerCalender";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";

export default function ChangeWorker() {
    const { t } = useTranslation();
    const params = useParams();
    const [job, setJob] = useState(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getJob = () => {
        axios
            .get(`/api/admin/jobs/${params.id}`, { headers })
            .then((res) => {
                console.log(res);
                
                setJob(res.data.job);
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
            <Sidebar />
            <div id="content">
                <div className="view-applicant">
                    <h1 className="page-title editJob">{t("admin.global.changeWorker")}</h1>
                    <div id="calendar"></div>
                    <div className="card">
                        {job && (
                            <div className="card-body">
                                <form>
                                    <div className="row">
                                        <div className="col-lg-2 col-sm-4 col-12">
                                            <div className="form-group">
                                                <label>{t("client.jobs.client")}</label>
                                                <p>
                                                    {job.client.firstname +
                                                        " " +
                                                        job.client.lastname}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-lg-2 col-sm-4 col-12">
                                            <div className="form-group">
                                                <label>{t("client.jobs.service")}</label>
                                                <p>{job.jobservice.name}</p>
                                            </div>
                                        </div>
                                        <div className="col-lg-2 col-sm-4 col-12">
                                            <div className="form-group">
                                                <label>{t("client.jobs.change.frequency")}</label>
                                                <p>
                                                    {job.jobservice.freq_name}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-lg-2 col-sm-4 col-12">
                                            <div className="form-group">
                                                <label>{t("client.jobs.change.time_to_complete")}</label>
                                                <p>
                                                    {convertMinsToDecimalHrs(
                                                        job.jobservice
                                                            .duration_minutes
                                                    )}{" "}
                                                    {t("client.jobs.review.hours")}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>{t("client.jobs.review.Property")}</label>
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
                                                <label>{t("client.jobs.change.pet_animals")}</label>
                                                <p>
                                                    {job.property_address
                                                        .is_cat_avail
                                                        ? t("admin.leads.AddLead.addAddress.Cat")
                                                        : job.property_address
                                                              .is_dog_avail
                                                        ? t("admin.leads.AddLead.addAddress.Dog")
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
                                                <label>{t("client.jobs.change.gender_preference")}</label>
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
                                        <div className="col-4 col-lg-2">
                                            <div className="form-group">
                                                <label>{t("client.jobs.change.worker")}</label>
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
                                        <div className="col-sm-4 col-12 col-lg-2">
                                            <div className="form-group">
                                                <label>{t("client.jobs.change.date")}</label>
                                                <p>
                                                    {moment(job.start_date)
                                                        .toString()
                                                        .slice(0, 15)}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4 col-12 col-lg-2">
                                            <div className="form-group">
                                                <label>{t("client.jobs.change.shift")}</label>
                                                <p>{job.shifts}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="row">
                                        <div className="col-sm-12">
                                            <ChangeWorkerCalender job={job} />
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
