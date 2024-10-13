import React, { useMemo } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";

export default function WorkerDetails({ worker }) {
    const { t } = useTranslation();

    return (
        <>
            <h2 className="text-custom">
                {t("admin.schedule.jobs.workerDetails")}
            </h2>
            <div className="dashBox p-0 p-md-4 mb-3">
                <form>
                    <div className="row">
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {" "}
                                    {t("admin.schedule.jobs.Name")}
                                </label>
                                {worker ? (
                                    <p>
                                        <Link
                                            to={`/admin/workers/view/${worker.id}`}
                                        >
                                            {" "}
                                            {worker.firstname}{" "}
                                        </Link>{" "}
                                        {worker.lastname}
                                    </p>
                                ) : (
                                    <p>NA</p>
                                )}
                            </div>
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {" "}
                                    {t("admin.schedule.jobs.Email")}
                                </label>
                                <p className="word-break">{worker ? worker.email : "NA"}</p>
                            </div>
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {" "}
                                    {t("admin.schedule.jobs.Phone")}
                                </label>
                                <p>
                                    {worker ? (
                                        <a href={`tel:${worker.phone}`}>
                                            {worker.phone}
                                        </a>
                                    ) : (
                                        "NA"
                                    )}
                                </p>
                            </div>
                        </div>
                        <div className="col-sm-8">
                            <div className="form-group">
                                <label className="control-label">
                                    {" "}
                                    {t("admin.schedule.jobs.Address")}
                                </label>
                                <p>
                                    {worker ? (
                                        <Link
                                            target="_blank"
                                            to={`https://maps.google.com?q=${worker.address}`}
                                        >
                                            {worker.address}
                                        </Link>
                                    ) : (
                                        "NA"
                                    )}
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
}
