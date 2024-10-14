import React, { useState } from "react";
import { Link } from "react-router-dom";

import { getShiftsDetails } from "../../../Utils/common.utils";
import { useTranslation } from "react-i18next";
import JobDiscountModal from "../Modals/JobDiscountModal";
import JobExtraModal from "../Modals/JobExtraModal";

export default function Services({ job, updateJob }) {
    const [isOpenDiscountModal, setIsOpenDiscountModal] = useState(false);
    const [isOpenExtraModal, setIsOpenExtraModal] = useState(false);
    const { durationInHours, startTime, endTime } = getShiftsDetails(job);


    const { t } = useTranslation();
    const service = job.jobservice;

    return (
        <>
            <div className="row">
                <div className="col-sm-12">
                    <h2 className="text-custom float-left col-12 col-md-6">
                        {t("admin.schedule.jobs.serviceDetailslabel")}
                    </h2>

                    <button
                        type="button"
                        className="btn btn-primary float-right mb-2 mb-sm-0"
                        onClick={() => setIsOpenDiscountModal(true)}
                    >
                        {t("admin.global.discount")}
                    </button>

                    <button
                        type="button"
                        className="btn btn-primary float-right mr-2  mb-2 mb-sm-0"
                        onClick={() => setIsOpenExtraModal(true)}
                    >
                        {t("admin.global.extraAmount")}
                    </button>
                </div>
            </div>

            <div className="dashBox p-0 p-md-4 mb-3">
                {service && (
                    <form>
                        <div className="row">
                            <div className="col-lg-3 col-6">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t(
                                            "admin.schedule.jobs.serviceDetails.Service"
                                        )}
                                    </label>
                                    <p>{service.name}</p>
                                </div>
                            </div>
                            <div className="col-lg-2 col-6">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t(
                                            "admin.schedule.jobs.serviceDetails.Frequency"
                                        )}
                                    </label>
                                    <p>{service.freq_name}</p>
                                </div>
                            </div>
                            <div className="col-lg-2 col-6">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t(
                                            "admin.schedule.jobs.serviceDetails.CompleteTime"
                                        )}
                                    </label>

                                    <p>
                                        {durationInHours}{" "}
                                            {t(
                                            "admin.schedule.jobs.serviceDetails.hours"
                                        )}
                                    </p>
                                </div>
                            </div>
                            <div className="col-lg-2 col-6">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t(
                                            "admin.schedule.jobs.serviceDetails.Shift"
                                        )}
                                    </label>
                                    <p>{startTime + "-" + endTime}</p>
                                </div>
                            </div>
                            <div className="col-lg-2 col-6">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t(
                                            "admin.schedule.jobs.serviceDetails.JobStatus"
                                        )}
                                    </label>
                                    <p>{job.status}</p>

                                    {job.order && (
                                        <React.Fragment>
                                            <br />
                                            <Link
                                                target="_blank"
                                                to={job.order.doc_url}
                                                className="jorder"
                                            >
                                                {t(
                                                    "admin.schedule.jobs.serviceDetails.Order"
                                                )}
                                                - {job.order.order_id}
                                            </Link>
                                        </React.Fragment>
                                    )}

                                    {job.invoice && (
                                        <React.Fragment>
                                            <br />
                                            <Link
                                                target="_blank"
                                                to={job.invoice.doc_url}
                                                className="jinv"
                                            >
                                                {t(
                                                    "admin.schedule.jobs.serviceDetails.Invoice"
                                                )}
                                                - {job.invoice.invoice_id}
                                            </Link>
                                            <br />
                                            <span className="jorder">
                                                {job.invoice.status}
                                            </span>
                                        </React.Fragment>
                                    )}

                                    {job.status == "cancel" &&
                                        ` (with cancellation fees of ${job.cancellation_fee_amount} ILS)`}
                                </div>
                            </div>
                        </div>
                    </form>
                )}

                {isOpenDiscountModal && (
                    <JobDiscountModal
                        setIsOpen={setIsOpenDiscountModal}
                        isOpen={isOpenDiscountModal}
                        job={job}
                        onSuccess={() => {
                            updateJob();
                            setIsOpenDiscountModal(false);
                        }}
                    />
                )}

                {isOpenExtraModal && (
                    <JobExtraModal
                        setIsOpen={setIsOpenExtraModal}
                        isOpen={isOpenExtraModal}
                        job={job}
                        onSuccess={() => {
                            updateJob();
                            setIsOpenExtraModal(false);
                        }}
                    />
                )}
            </div>
        </>
    );
}
