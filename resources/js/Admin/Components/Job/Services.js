import React from "react";

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
