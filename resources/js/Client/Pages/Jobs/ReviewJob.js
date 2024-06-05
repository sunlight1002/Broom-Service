import React, { useState, useEffect } from "react";
import "rsuite/dist/rsuite.min.css";
import axios from "axios";
import moment from "moment";
import { useParams } from "react-router-dom";
import Swal from "sweetalert2";
import { Base64 } from "js-base64";
import { Rating } from "react-simple-star-rating";
import { useAlert } from "react-alert";
import ClientSidebar from "../../Layouts/ClientSidebar";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";
import { useTranslation } from "react-i18next";

export default function ReviewJob() {
    const params = useParams();
    const [job, setJob] = useState(null);
    const { t } = useTranslation();
    const [formValues, setFormValues] = useState({
        rating: job ? job.rating : 0,
        review: job ? job.review : "",
    });
    const [isLoading, setIsLoading] = useState(false);
    const alert = useAlert();

    const jobId = Base64.decode(params.id);
    const lng = localStorage.getItem("i18nextLng");

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

                setFormValues({
                    rating: _job.rating,
                    review: _job.review,
                });
            })
            .catch((e) => {
                Swal.fire({
                    title: t("client.jobs.review.Error"),
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const handleSubmit = () => {
        if (!formValues.rating) {
            alert.error(t("client.jobs.review.ratingReq"));
            return false;
        }

        if (!formValues.review) {
            alert.error(t("client.jobs.review.reviewReq"));
            return false;
        }

        setIsLoading(true);
        axios
            .post(`/api/client/jobs/${jobId}/review`, formValues, { headers })
            .then((response) => {
                getJob();
                setIsLoading(false);
            })
            .catch((e) => {
                setIsLoading(false);
                Swal.fire({
                    title: t("client.jobs.review.Error"),
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
                        {t("client.jobs.review.title")}
                    </h1>
                    <div id="calendar"></div>
                    <div className="card">
                        {job && (
                            <div className="card-body">
                                <form>
                                    <div className="row">
                                        <div className="col-sm-3 col-lg-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.Services"
                                                    )}
                                                </label>
                                                <p>{job.jobservice.name}</p>
                                            </div>
                                        </div>
                                        <div className="col-sm-3 col-lg-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.Frequency"
                                                    )}
                                                </label>
                                                <p>
                                                    {job.jobservice.freq_name}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-3 col-lg-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.timeToComplete"
                                                    )}
                                                </label>
                                                <p>
                                                    {convertMinsToDecimalHrs(
                                                        job.actual_time_taken_minutes
                                                    )}{" "}
                                                    {t(
                                                        "client.jobs.review.hours"
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-3 col-lg-4">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.Property"
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
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.PetAnimals"
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
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.GenderPreference"
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
                                        <div className="col-sm-4 col-md-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.Worker"
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
                                        <div className="col-sm-4 col-md-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.Date"
                                                    )}
                                                </label>
                                                <p>
                                                    {moment(job.start_date)
                                                        .toString()
                                                        .slice(0, 15)}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4 col-md-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.Shift"
                                                    )}
                                                </label>
                                                <p>{job.shifts}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <hr />
                                    <div className="row">
                                        <div className="form-group px-3">
                                            <label className="control-label">
                                                {t("client.jobs.review.Rating")}
                                            </label>
                                            <div>
                                                <Rating
                                                    initialValue={
                                                        formValues.rating
                                                    }
                                                    onClick={(e) => {
                                                        setFormValues({
                                                            ...formValues,
                                                            rating: e,
                                                        });
                                                    }}
                                                    allowFraction
                                                    transition
                                                    rtl={lng === "heb"}
                                                    readonly={job.rating}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="row">
                                        <div className="col-sm-6">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    {t(
                                                        "client.jobs.review.Review"
                                                    )}
                                                </label>
                                                <textarea
                                                    className="form-control"
                                                    rows="5"
                                                    placeholder={t(
                                                        "client.jobs.review.Review"
                                                    )}
                                                    value={formValues.review}
                                                    onChange={(e) => {
                                                        setFormValues({
                                                            ...formValues,
                                                            review: e.target
                                                                .value,
                                                        });
                                                    }}
                                                    disabled={job.review}
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    {/* submit button */}
                                    {!job.rating && (
                                        <div className="row">
                                            <div className="col-sm-12">
                                                <button
                                                    type="button"
                                                    className="btn btn-primary"
                                                    onClick={handleSubmit}
                                                    disabled={isLoading}
                                                >
                                                    {t(
                                                        "client.jobs.review.Submit"
                                                    )}
                                                </button>
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
