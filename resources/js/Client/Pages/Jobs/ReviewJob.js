import React, { useState, useEffect } from "react";
import "rsuite/dist/rsuite.min.css";
import axios from "axios";
import moment from "moment";
import { useParams } from "react-router-dom";
import Swal from "sweetalert2";
import { Base64 } from "js-base64";
import { Rating } from "react-simple-star-rating";
import ClientSidebar from "../../Layouts/ClientSidebar";

export default function ReviewJob() {
    const params = useParams();
    const [job, setJob] = useState(null);
    const [formValues, setFormValues] = useState({
        rating: 0,
        review: "",
    });

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
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const handleSubmit = () => {
        const _data = {
            rating,
        };

        axios
            .post(`/api/client/jobs/${jobId}/review`, {}, { headers })
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
        console.log("formValues", formValues);
    }, [formValues]);

    useEffect(() => {
        getJob();
    }, []);

    return (
        <div id="container">
            <ClientSidebar />
            <div id="content">
                <div className="view-applicant">
                    <h1 className="page-title editJob">
                        Review and Rating Job
                    </h1>
                    <div id="calendar"></div>
                    <div className="card">
                        {job && (
                            <div className="card-body">
                                <form>
                                    <div className="row">
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Services
                                                </label>
                                                <p>{job.jobservice.name}</p>
                                            </div>
                                        </div>
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Frequency
                                                </label>
                                                <p>
                                                    {job.jobservice.freq_name}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Time to Complete
                                                </label>
                                                <p>
                                                    {job.jobservice.jobHours}{" "}
                                                    hours
                                                </p>
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Property
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
                                                    Pet animals
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
                                                    Gender preference
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
                                                <label className="control-label">
                                                    Worker
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
                                                <label className="control-label">
                                                    Date
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
                                                <label className="control-label">
                                                    Shift
                                                </label>
                                                <p>{job.shifts}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <hr />
                                    <div className="row">
                                        <div className="form-group px-3">
                                            <label className="control-label">
                                                Rating
                                            </label>
                                            <div>
                                                <Rating
                                                    initialValue={
                                                        formValues.rating
                                                    }
                                                    onClick={(e) => {
                                                        console.log(e);
                                                        setFormValues({
                                                            rating: e,
                                                            ...formValues,
                                                        });
                                                    }}
                                                    allowFraction
                                                    transition
                                                    rtl={lng === "heb"}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="row">
                                        <div className="col-sm-6">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Review
                                                </label>
                                                <textarea
                                                    className="form-control"
                                                    rows="5"
                                                    placeholder="Review"
                                                    value={formValues.review}
                                                    onChange={(e) => {
                                                        console.log(e);
                                                        setFormValues({
                                                            review: e.target
                                                                .value,
                                                            ...formValues,
                                                        });
                                                    }}
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    {/* submit button */}
                                    <div className="row">
                                        <div className="col-sm-12">
                                            <button
                                                type="button"
                                                className="btn btn-primary"
                                                onClick={handleSubmit}
                                            >
                                                Submit
                                            </button>
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
