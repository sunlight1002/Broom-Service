import React from "react";
import { Link } from "react-router-dom";

import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";

export default function Services({ job }) {
    const service = job.jobservice;

    return (
        <>
            <h2 className="text-custom">Service Details</h2>
            <div className="dashBox p-4 mb-3">
                {service && (
                    <form>
                        <div className="row">
                            <div className="col-sm-3">
                                <div className="form-group">
                                    <label className="control-label">
                                        Service
                                    </label>
                                    <p>{service.name}</p>
                                </div>
                            </div>
                            <div className="col-sm-2">
                                <div className="form-group">
                                    <label className="control-label">
                                        Frequency
                                    </label>
                                    <p>{service.freq_name}</p>
                                </div>
                            </div>
                            <div className="col-sm-2">
                                <div className="form-group">
                                    <label className="control-label">
                                        Complete Time
                                    </label>

                                    <p>
                                        {convertMinsToDecimalHrs(
                                            service.duration_minutes
                                        )}{" "}
                                        hours
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
                            <div className="col-sm-2">
                                <div className="form-group">
                                    <label className="control-label">
                                        Job Status
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
                                                Order - {job.order.order_id}
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
                                                Invoice -{" "}
                                                {job.invoice.invoice_id}
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
            </div>
        </>
    );
}
