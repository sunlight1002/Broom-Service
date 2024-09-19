import React from "react";
import { useTranslation } from "react-i18next";

import { getShiftsDetails } from "../../../Utils/common.utils";
import { Link } from "react-router-dom";
export default function Services({ job }) {
    const { t, i18n } = useTranslation();
    const c_lng = i18n.language;
    let status = job.status;
    if (status == "not-started") {
        status = t("j_status.not-started");
    }
    if (status == "progress") {
        status = t("j_status.progress");
    }
    if (status == "completed") {
        status = t("j_status.completed");
    }
    if (status == "scheduled") {
        status = t("j_status.scheduled");
    }
    if (status == "unscheduled") {
        status = t("j_status.unscheduled");
    }
    if (status == "re-scheduled") {
        status = t("j_status.re-scheduled");
    }
    if (status == "cancel") {
        status = t("j_status.cancel");
    }

    const service = job.jobservice;

    const { durationInHours, startTime, endTime } = getShiftsDetails(job);    

    return (
        <>
            <h2 className="text-custom">{t("worker.jobs.view.s_details")}</h2>
            <div className="dashBox p-4 mb-3">
                {service && (
                    <form>
                        <div className="row">
                            <div className="col-sm-3">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("client.jobs.view.service")}
                                    </label>
                                    <p>
                                        {c_lng == "en"
                                            ? service.name
                                            : service.heb_name}
                                    </p>
                                </div>
                            </div>
                            <div className="col-sm-3">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("client.jobs.view.c_time")}
                                    </label>
                                    <p>
                                        {service.duration_minutes
                                            ? durationInHours
                                            : "NA"}{" "}
                                        {t("client.jobs.view.hour_s")}
                                    </p>
                                </div>
                            </div>
                            <div className="col-sm-3">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("client.jobs.view.shift")}
                                    </label>
                                    <p>{startTime}</p>
                                </div>
                            </div>
                            <div className="col-sm-3">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("client.jobs.view.job_status")}
                                    </label>
                                    <p>{status}</p>
                                    {job.status == "cancel" &&
                                        ` (with cancellation fees of ${job.cancellation_fee_amount} ILS)`}
                                </div>
                            </div>
                            {job.property_address &&
                                job.property_address.address_name &&
                                job.property_address.latitude &&
                                job.property_address.longitude && (
                                    <div className="col-sm-3">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t("client.jobs.view.address")}
                                            </label>
                                            <p>
                                                <Link
                                                    target="_blank"
                                                    to={`https://maps.google.com?q=${job.property_address.geo_address}`}
                                                >
                                                    {
                                                        job.property_address
                                                            .address_name
                                                    }
                                                </Link>
                                            </p>
                                        </div>
                                    </div>
                                )}
                        </div>
                    </form>
                )}
            </div>
        </>
    );
}
