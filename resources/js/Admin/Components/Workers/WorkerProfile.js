import React, { useState, useEffect, useMemo } from "react";
import { useTranslation } from "react-i18next";

export default function WorkerProfile({ worker }) {
    const { t } = useTranslation();
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
                                        {t("admin.leads.viewLead.Phone")}
                                    </label>
                                    <p>
                                        <a href={`tel:+${worker.phone}`}>
                                            +{worker.phone}
                                        </a>
                                    </p>
                                </div>
                            </div>
                            {worker.country != "Israel" && (
                                <div className="col-sm-4">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.renewal_visa")}
                                        </label>
                                        <p>{worker.renewal_date}</p>
                                    </div>
                                </div>
                            )}
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.gender")}
                                    </label>
                                    <p>{worker.gender}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.p_ph")}
                                    </label>
                                    <p>{worker.payment_per_hour}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.w_id")}
                                    </label>
                                    <p>{worker.worker_id}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("global.worker")}{t("work-contract.email")}
                                    </label>
                                    <p className="word-break">{worker.email}</p>
                                </div>
                            </div>
                            <div className="col-sm-4">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.pass")}
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
                                        {t("worker.settings.status")}
                                    </label>
                                    <p>
                                        {worker.status ? "Active" : "Inactive"}
                                    </p>
                                </div>
                            </div>

                            <div className="col-sm-8">
                                <div className="form-group">
                                    <label className="control-label">
                                        {t("worker.settings.address")}
                                    </label>
                                    <a
                                        href={`https://maps.google.com?q=${worker.address}`}
                                        target="_blank"
                                    >
                                        <p>{worker.address}</p>
                                    </a>
                                </div>
                            </div>

                            <div className="col-sm-8">
                                <p className="font-18">Bank Details</p>
                            {
                                worker.payment_type === "money_transfer" ? (
                                    <div className="row mt-2">
                                    <div className="col-md-6">
                                        <div className="d-flex align-items-center mb-3">
                                            <label className="control-label mr-2" style={{margin: "0"}}>Full name:</label>
                                            <p className="mb-0">{worker.full_name}</p>
                                        </div>
                                        <div className="d-flex align-items-center mb-3">
                                            <label className="control-label mr-2" style={{margin: "0"}}>Bank Name:</label>
                                            <p className="mb-0">{worker.bank_name}</p>
                                        </div>
                                    </div>
                                    <div className="col-md-6">
                                        <div className="d-flex align-items-center mb-3">
                                            <label className="control-label mr-2" style={{margin: "0"}}>Bank Number:</label>
                                            <p className="mb-0">{worker.bank_number}</p>
                                        </div>
                                        <div className="d-flex align-items-center mb-3">
                                            <label className="control-label mr-2" style={{margin: "0"}}>Branch Number:</label>
                                            <p className="mb-0">{worker.branch_number}</p>
                                        </div>
                                    </div>
                                </div>
                                ) : (
                                    <div className="row mt-2">
                                        <div className="col-md-6">
                                        <div className="d-flex align-items-center mb-3">
                                            <label className="control-label mr-2" style={{margin: "0"}}>Payment type:</label>
                                            <p className="mb-0">{worker.payment_type}</p>
                                        </div>
                                        </div>
                                    </div>
                                )
                            }
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
                                                {t("admin.client.Enter_password")}
                                            </label>
                                            <input
                                                type="password"
                                                onChange={(e) =>
                                                    setPassVal(e.target.value)
                                                }
                                                className="form-control"
                                                required
                                                placeholder={t("admin.client.Enter_password")}
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
                                    {t("modal.close")}
                                </button>
                                <button
                                    type="button"
                                    onClick={viewPass}
                                    className="btn btn-primary"
                                >
                                    {t("workerInviteForm.submit")}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
