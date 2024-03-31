import React from "react";
import { Base64 } from "js-base64";
import { Link } from "react-router-dom";

export default function WorkerDetails({ worker }) {
    var cords =
        worker.latitude && worker.longitude
            ? worker.latitude + "," + worker.longitude
            : "NA";

    return (
        <>
            <h2 className="text-custom">Worker Details</h2>
            <div className="dashBox p-4 mb-3">
                <form>
                    <div className="row">
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">Name</label>
                                <p>
                                    <Link
                                        to={`/admin/view-worker/${worker.id}`}
                                    >
                                        {" "}
                                        {worker.firstname}{" "}
                                    </Link>{" "}
                                    {worker.lastname}
                                </p>
                            </div>
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">Email</label>
                                <p>{worker.email}</p>
                            </div>
                        </div>
                        <div className="col-sm-6">
                            <div className="form-group">
                                <label className="control-label">Phone</label>
                                <p>
                                    <a href={`tel:${worker.phone}`}>
                                        {worker.phone}
                                    </a>
                                </p>
                            </div>
                        </div>
                        <div className="col-sm-8">
                            <div className="form-group">
                                <label className="control-label">Address</label>
                                <p>
                                    <Link
                                        target="_blank"
                                        to={`https://maps.google.com?q=${cords}`}
                                    >
                                        {worker.address}
                                    </Link>
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
}
