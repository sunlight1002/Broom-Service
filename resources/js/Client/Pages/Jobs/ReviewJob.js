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
import "moment/locale/he";
import { Card } from "react-bootstrap";
import { LuSave } from "react-icons/lu";
import { LuFolderClosed } from "react-icons/lu";
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

    console.log(job);

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
                                    {/* <div className="d-flex">
                                        <Card className="review-card">
                                            <div>
                                                <label className="control-label  review-header">
                                                    {t(
                                                        "client.jobs.review.General"
                                                    )}
                                                </label>
                                                <div className=" d-flex">
                                                    <label className="control-label review-label">
                                                        {t(
                                                            "client.jobs.review.Services"
                                                        )}
                                                        :
                                                    </label>
                                                    <p className="no-wrap">
                                                        {lng == "heb"
                                                            ? job.jobservice
                                                                  .heb_name
                                                            : job.jobservice
                                                                  .name}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className=" d-flex no-wrap">
                                                <label className="control-label review-label">
                                                    {t(
                                                        "client.jobs.review.GenderPreference"
                                                    )}
                                                    :
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

                                            <div className=" d-flex">
                                                <label className="control-label review-label">
                                                    {t(
                                                        "client.jobs.review.Worker"
                                                    )}
                                                    :
                                                </label>
                                                {job.worker ? (
                                                    <p className="no-wrap">
                                                        {job.worker.firstname +
                                                            " " +
                                                            job.worker.lastname}
                                                    </p>
                                                ) : (
                                                    <p>NA</p>
                                                )}
                                            </div>
                                        </Card>
                                        <Card className="review-card">
                                            <div className="col-sm-3 col-lg-2">
                                                <div>
                                                    <label className="control-label review-header ">
                                                        {t(
                                                            "client.jobs.review.Frequency"
                                                        )}
                                                        :
                                                    </label>
                                                    <p
                                                        className="no-wrap"
                                                        style={{
                                                            marginLeft: "20px",
                                                        }}
                                                    >
                                                        {
                                                            job.jobservice
                                                                .freq_name
                                                        }
                                                    </p>
                                                </div>

                                                <div className=" d-flex">
                                                    <label className="control-label review-label">
                                                        {t(
                                                            "client.jobs.review.Date"
                                                        )}
                                                        :
                                                    </label>
                                                    <p className="no-wrap">
                                                        {moment(job.start_date)
                                                            .locale(
                                                                lng === "heb"
                                                                    ? "he"
                                                                    : "en"
                                                            )
                                                            .format(
                                                                lng === "heb"
                                                                    ? "dddd, D MMMM YYYY"
                                                                    : "ddd, MMM D YYYY"
                                                            )}{" "}
                                                    </p>
                                                </div>

                                                <div className=" d-flex">
                                                    <label className="control-label review-label">
                                                        {t(
                                                            "client.jobs.review.Shift"
                                                        )}
                                                        :
                                                    </label>
                                                    <p className="no-wrap">
                                                        {job.shifts}
                                                    </p>
                                                </div>
                                            </div>
                                        </Card>
                                        <Card className="review-card">
                                            <div className="col-sm-3 col-lg-4">
                                                <div className=" no-wrap">
                                                    <label className="control-label review-header">
                                                        {t(
                                                            "client.jobs.review.Property"
                                                        )}
                                                    </label>
                                                    <p
                                                        style={{
                                                            marginLeft: "20px",
                                                        }}
                                                    >
                                                        {
                                                            job.property_address
                                                                .geo_address
                                                        }
                                                    </p>
                                                </div>
                                            </div>
                                        </Card>
                                    </div> */}
                                    <div className="d-flex flex-wrap">
                                        <Card className="review-card col-sm-12 col-md-6 col-lg-4">
                                            <div>
                                                <label className="control-label review-header">
                                                    {t(
                                                        "client.jobs.review.General"
                                                    )}
                                                </label>
                                                <div className="d-flex">
                                                    <label className="control-label review-label">
                                                        {t(
                                                            "client.jobs.review.Services"
                                                        )}
                                                        :
                                                    </label>
                                                    <p className="no-wrap">
                                                        {lng === "heb"
                                                            ? job.jobservice
                                                                  .heb_name
                                                            : job.jobservice
                                                                  .name}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="d-flex no-wrap">
                                                <label className="control-label review-label">
                                                    {t(
                                                        "client.jobs.review.GenderPreference"
                                                    )}
                                                    :
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

                                            <div className="d-flex">
                                                <label className="control-label review-label">
                                                    {t(
                                                        "client.jobs.review.Worker"
                                                    )}
                                                    :
                                                </label>
                                                {job.worker ? (
                                                    <p className="no-wrap">
                                                        {job.worker.firstname +
                                                            " " +
                                                            job.worker.lastname}
                                                    </p>
                                                ) : (
                                                    <p>NA</p>
                                                )}
                                            </div>
                                        </Card>

                                        <Card className="review-card col-sm-12 col-md-6 col-lg-4">
                                            <div>
                                                <label className="control-label review-header">
                                                    {t(
                                                        "client.jobs.review.Frequency"
                                                    )}
                                                    :
                                                </label>
                                                <p
                                                    className="no-wrap"
                                                    style={{
                                                        marginLeft: "20px",
                                                    }}
                                                >
                                                    {job.jobservice.freq_name}
                                                </p>
                                            </div>

                                            <div className="d-flex">
                                                <label className="control-label review-label">
                                                    {t(
                                                        "client.jobs.review.Date"
                                                    )}
                                                    :
                                                </label>
                                                <p className="no-wrap">
                                                    {moment(job.start_date)
                                                        .locale(
                                                            lng === "heb"
                                                                ? "he"
                                                                : "en"
                                                        ) // Set the locale
                                                        .format(
                                                            lng === "heb"
                                                                ? "dddd, D MMMM YYYY"
                                                                : "ddd, MMM D YYYY"
                                                        )}{" "}
                                                    {/* Localized format */}
                                                </p>
                                            </div>

                                            <div className="d-flex">
                                                <label className="control-label review-label">
                                                    {t(
                                                        "client.jobs.review.Shift"
                                                    )}
                                                    :
                                                </label>
                                                <p className="no-wrap">
                                                    {job.shifts}
                                                </p>
                                            </div>
                                        </Card>

                                        <Card className="review-card col-sm-12 col-md-6 col-lg-4">
                                            <div
                                                style={{ marginBottom: "20px" }}
                                            >
                                                <label className="control-label review-header">
                                                    {t(
                                                        "client.jobs.review.Property"
                                                    )}
                                                </label>
                                                <p
                                                    style={{
                                                        marginLeft: "20px",
                                                    }}
                                                >
                                                    {
                                                        job.property_address
                                                            .geo_address
                                                    }
                                                </p>
                                            </div>
                                        </Card>
                                    </div>

                                    <hr />
                                    <div>
                                        <div className="px-3">
                                            <label
                                                className="control-label "
                                                style={{
                                                    fontWeight: 600,
                                                    color: "#2F4054",
                                                    fontSize: "18px",
                                                }}
                                            >
                                                {t("client.jobs.review.Rating")}
                                            </label>
                                            <div className="d-flex justify-content-center">
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
                                                <label
                                                    className="control-label"
                                                    style={{ marginLeft: 10 }}
                                                >
                                                    {t(
                                                        "client.jobs.review.Review"
                                                    )}
                                                </label>
                                                <textarea
                                                    className="review-control"
                                                    rows="5"
                                                    placeholder={t(
                                                        "client.jobs.review.ReviewPlaceholder"
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
                                            <div className="col-sm-12 buttons-container">
                                                <button
                                                    type="button"
                                                    className="btn navyblue"
                                                    disabled={isLoading}
                                                >
                                                    {/* {t(
                                                        "client.jobs.review.Cancel"
                                                    )} */}
                                                    <span className="d-flex align-items-center">
                                                        <LuSave className="mr-1" />
                                                        {t(
                                                            "client.jobs.review.Cancel"
                                                        )}
                                                    </span>
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn navyblue"
                                                    onClick={handleSubmit}
                                                    disabled={isLoading}
                                                >
                                                    {/* {t(
                                                        "client.jobs.review.Submit"
                                                    )} */}
                                                    <span className="d-flex align-items-center">
                                                        <LuSave className="mr-1" />
                                                        {t(
                                                            "client.jobs.review.Submit"
                                                        )}
                                                    </span>
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
