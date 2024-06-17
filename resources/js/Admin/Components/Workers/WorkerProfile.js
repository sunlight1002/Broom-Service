import React, { useState, useEffect, useMemo } from "react";

export default function WorkerProfile({ worker }) {
    const [pass, setPass] = useState(null);
    const [passVal, setPassVal] = useState(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const viewPass = () => {
        if (!passVal) {
            window.alert("Please enter your password");
            return;
        }

        axios
            .post(
                `/api/admin/viewpass`,
                { id: localStorage.getItem("admin-id"), pass: passVal },
                { headers }
            )
            .then((res) => {
                if (res.data.response == false) {
                    window.alert("Wrong password!");
                } else {
                    setPass(worker.passcode);
                    document.querySelector(".closeb1").click();
                }
            });
    };

    return (
        <>
            <div className="worker-profile">
                <h2>
                    #{worker.worker_id}{" "}
                    {worker.firstname + " " + worker.lastname}
                </h2>
                <div className="dashBox p-4 mb-3">
                    <form>
                        <div className="row">
                            {/* <div className='col-sm-4'>
                            <div className='form-group'>
                                <label className='control-label'>Email</label>
                                <p>{email}</p>
                            </div>
                        </div> */}
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        Phone
                                    </label>
                                    <p>
                                        <a href={`tel:${worker.phone}`}>
                                            {worker.phone}
                                        </a>
                                    </p>
                                </div>
                            </div>
                            {worker.country != "Israel" && (
                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Renewal of Visa
                                        </label>
                                        <p>{worker.renewal_date}</p>
                                    </div>
                                </div>
                            )}
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        Gender
                                    </label>
                                    <p>{worker.gender}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        Payment Per Hour
                                    </label>
                                    <p>{worker.payment_per_hour}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        Worker Id
                                    </label>
                                    <p>{worker.worker_id}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        Worker email
                                    </label>
                                    <p className="word-break">{worker.email}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        Password
                                    </label>

                                    {pass == null ? (
                                        <span
                                            style={{
                                                cursor: "pointer",
                                                border: "none",
                                            }}
                                            className="form-control"
                                            data-toggle="modal"
                                            data-target="#exampleModalPass"
                                        >
                                            ******** &#128274;
                                        </span>
                                    ) : (
                                        <span
                                            style={{ border: "none" }}
                                            className="form-control"
                                        >
                                            {pass}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        Status
                                    </label>
                                    <p>
                                        {worker.status ? "Active" : "Inactive"}
                                    </p>
                                </div>
                            </div>

                            <div className="col-sm-8">
                                <div className="form-group">
                                    <label className="control-label">
                                        Address
                                    </label>
                                    <a
                                        href={`https://maps.google.com?q=${worker.address}`}
                                        target="_blank"
                                    >
                                        <p>{worker.address}</p>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div
                    className="modal fade"
                    id="exampleModalPass"
                    tabIndex="-1"
                    role="dialog"
                    aria-labelledby="exampleModalPass"
                    aria-hidden="true"
                >
                    <div className="modal-dialog" role="document">
                        <div className="modal-content">
                            <div className="modal-header">
                                <button
                                    type="button"
                                    className="close"
                                    data-dismiss="modal"
                                    aria-label="Close"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div className="modal-body">
                                <div className="row">
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Enter your password
                                            </label>
                                            <input
                                                type="password"
                                                onChange={(e) =>
                                                    setPassVal(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder="Enter your password"
                                                autoComplete="new-password"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button
                                    type="button"
                                    className="btn btn-secondary closeb1"
                                    data-dismiss="modal"
                                >
                                    Close
                                </button>
                                <button
                                    type="button"
                                    onClick={viewPass}
                                    className="btn btn-primary"
                                >
                                    Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
