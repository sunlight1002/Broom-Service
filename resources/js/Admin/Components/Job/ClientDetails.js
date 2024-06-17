import React from "react";
import { useTranslation } from "react-i18next";
import { Link } from "react-router-dom";
export default function ClientDetails({ client, address }) {
    const { t } = useTranslation();

    return (
        <>
            <h2 className="text-custom">
                {" "}
                {t("admin.schedule.jobs.clientDetails")}
            </h2>
            <div className="dashBox p-4 mb-3">
                <form>
                    <div className="row">
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {" "}
                                    {t("admin.schedule.jobs.Name")}
                                </label>
                                <p>
                                    {" "}
                                    <Link
                                        to={
                                            client
                                                ? `/admin/view-client/${client.id}`
                                                : "#"
                                        }
                                    >
                                        {client.firstname} {client.lastname}
                                    </Link>
                                </p>
                            </div>
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {" "}
                                    {t("admin.schedule.jobs.Email")}
                                </label>
                                <p className="word-break">{client.email}</p>
                            </div>
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {" "}
                                    {t("admin.schedule.jobs.Phone")}
                                </label>
                                <p>
                                    <a href={`tel:${client.phone}`}>
                                        {client.phone}
                                    </a>
                                </p>
                            </div>
                        </div>
                        <div className="col-sm-4">
                            <div className="form-group">
                                <label className="control-label">
                                    {" "}
                                    {t("admin.schedule.jobs.City")}
                                </label>
                                <p>{address.city}</p>
                            </div>{" "}
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">
                                    {" "}
                                    {t("admin.schedule.jobs.Address")}
                                </label>
                                <p>
                                    <Link
                                        target="_blank"
                                        to={`https://maps.google.com?q=${address.geo_address}`}
                                    >
                                        {address.address_name}
                                    </Link>
                                </p>
                            </div>
                        </div>
                        {address.parking && (
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label>
                                        {t("admin.schedule.jobs.parking")}
                                    </label>
                                    <p>{address.parking}</p>
                                </div>
                            </div>
                        )}
                    </div>
                </form>
            </div>
        </>
    );
}
