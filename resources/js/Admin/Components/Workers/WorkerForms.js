import axios from "axios";
import React, { useState, useEffect } from "react";
import { Link, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import { Base64 } from "js-base64";

export default function WorkerForms() {
    const params = useParams();
    const id = params.id;
    const [form, setForm] = useState(false);
    const [contractForm, setContractForm] = useState(false);
    const [workerId, setWorkerId] = useState("");

    const getForm = () => {
        axios.get(`/api/work-contract/${id}`).then((res) => {
            setContractForm(res.data.form ? true : false);
            setWorkerId(res.data.worker.worker_id);
        });
        axios.get(`/api/get101/${id}`).then((res) => {
            if (res.data.form) {
                setForm(true);
            } else {
                setForm(false);
            }
        });
    };

    useEffect(() => {
        getForm();
    }, []);

    return (
        <div
            className="tab-pane fade active show"
            id="customer-notes"
            role="tabpanel"
            aria-labelledby="customer-notes-tab"
        >
            <div
                key={"contract"}
                className="card card-widget widget-user-2"
                style={{ boxShadow: "none" }}
            >
                <div className="card-comments cardforResponsive"></div>
                <div
                    className="card-comment p-3"
                    style={{
                        backgroundColor: "rgba(0,0,0,.05)",
                        borderRadius: "5px",
                    }}
                >
                    <div className="row">
                        <div className="col-sm-4 col-4">
                            <span
                                className="noteDate"
                                style={{ fontWeight: "600" }}
                            >
                               Contract
                            </span>
                        </div>
                        <div className="col-sm-4 col-4">
                            {contractForm && workerId ? (
                                <div>
                                    <span className="badge btn btn-success m-3">
                                        Signed
                                    </span>
                                </div>
                            ) : (                                
                                <span className="badge btn btn-warning m-3">
                                    Not Signed{" "}
                                </span>
                            )}
                        </div>
                        <div className="col-sm-4 col-4">
                            {contractForm && workerId ? (
                                    <div>
                                        <Link
                                            target="_blank"
                                            to={`/worker-contract/` + Base64.encode(workerId)}
                                            className="m-2 btn btn-pink"
                                        >
                                            View Contract
                                        </Link>
                                    </div>
                                ) : (                                
                                    <span className="badge btn btn-warning m-3">
                                        -
                                    </span>
                                )}
                        </div>
                    </div>
                </div>
            </div>
            <div
                key={"form101"}
                className="card card-widget widget-user-2"
                style={{ boxShadow: "none" }}
            >
                <div className="card-comments cardforResponsive"></div>
                <div
                    className="card-comment p-3"
                    style={{
                        backgroundColor: "rgba(0,0,0,.05)",
                        borderRadius: "5px",
                    }}
                >
                    <div className="row">
                        <div className="col-sm-4 col-4">
                            <span
                                className="noteDate"
                                style={{ fontWeight: "600" }}
                            >
                               Form 101
                            </span>
                        </div>
                        <div className="col-sm-4 col-4">
                            {form && workerId ? (
                                <div>
                                    <span className="badge btn btn-success m-3">
                                        Signed
                                    </span>
                                </div>
                            ) : (                                
                                <span className="badge btn btn-warning m-3">
                                    Not Signed{" "}
                                </span>
                            )}
                        </div>
                        <div className="col-sm-4 col-4">
                            {form && workerId ? (
                                    <div>
                                        <Link
                                            target="_blank"
                                            to={`/form101/` + Base64.encode(id.toString())}
                                            className="m-2 m-2 btn btn-pink"
                                        >
                                            View Form
                                        </Link>
                                    </div>
                                ) : (                                
                                    <span className="badge btn btn-warning m-3">
                                        -
                                    </span>
                                )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
