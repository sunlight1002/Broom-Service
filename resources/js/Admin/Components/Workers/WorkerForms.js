import axios from "axios";
import React, { useState, useEffect } from "react";
import { Link, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import { Base64 } from "js-base64";
import Form101Table from "./Form101Table";

export default function WorkerForms({ worker, getWorkerDetails }) {
    const [forms, setForms] = useState([]);
    const [contractForm, setContractForm] = useState(false);
    const [safetyAndGearForm, setSafetyAndGearForm] = useState(false);
    const [form, setForm] = useState(false);
    const [workerId, setWorkerId] = useState("");
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getForm = () => {
        // axios.get(`/api/work-contract/${worker.id}`).then((res) => {
        //     setContractForm(res.data.form ? true : false);
        //     setWorkerId(res.data.worker.worker_id);
        // });

        axios
            .get(`/api/getAllForms/${worker.id}`)
            .then((res) => {
                const formsData = res.data.forms;
                if (formsData.length > 0) {
                    setForms(formsData);
                    const contractFormExist = formsData.filter((f) =>
                        f.type.includes("contract")
                    )[0];
                    const saftyGearFormExist = formsData.filter((f) =>
                        f.type.includes("saftey-and-gear")
                    )[0];
                    const form101Exist = formsData.filter((f) =>
                        f.type.includes("form101")
                    )[0];
                    setContractForm(contractFormExist.length > 0);
                    setSafetyAndGearForm(saftyGearFormExist.length > 0);
                    setForm(form101Exist.length > 0 ? true : false);
                }
                setWorkerId(res.data.worker.worker_id);
            })
            .catch((e) => {
                alert.error(e.response.data.message);
            });

        // axios.get(`/api/getSafegear/${worker.id}`).then((res) => {
        //     if (res.data.form) {
        //         setSafetyAndGearForm(true);
        //     } else {
        //         setSafetyAndGearForm(false);
        //     }
        // });
    };

    useEffect(() => {
        getForm();
    }, []);

    const save = (data) => {
        axios
            .post(`/api/admin/form/save`, data, { headers })
            .then((res) => {
                alert.success("File Uploaded");
                getWorkerDetails();
                getForm();
            })
            .catch((err) => {
                alert.error("Error!");
            });
    };

    const handleFileChange = (e, type) => {
        const data = new FormData();
        data.append("id", worker.id);
        if (e.target.files.length > 0) {
            data.append(`${type}`, e.target.files[0]);
        }
        save(data);
    };

    const btnSelect = (type) => {
        document.getElementById(`${type}`).click();
    };

    const uploadFormDiv = (type) => {
        return (
            <div className="col-sm-2 col-2">
                <div>
                    <button
                        type="button"
                        className="ml-2 btn bg-blue m-3"
                        onClick={() => btnSelect(type)}
                    >
                        <i className="fa fa-upload"></i>
                    </button>

                    <input
                        className="form-control d-none"
                        id={type}
                        type="file"
                        accept="application/pdf"
                        onChange={(e) => {
                            e.preventDefault();
                            handleFileChange(e, type);
                        }}
                    ></input>
                </div>
            </div>
        );
    };
    const handleSendForm = (e, formType) => {
        e.preventDefault();
        const data = new FormData();
        data.append("workerId", worker.id);
        data.append("type", formType);
        axios
            .post(`/api/admin/form/send`, data, { headers })
            .then((res) => {
                alert.success("Form sent!!");
                getForm();
            })
            .catch((err) => {
                alert.error("Error!");
            });
    };
    return (
        <div
            className="tab-pane fade active show"
            id="customer-notes"
            role="tabpanel"
            aria-labelledby="customer-notes-tab"
        >
            <div className="text-right pb-3">
                <button
                    type="button"
                    onClick={(e) => handleSendForm(e, "form101")}
                    className="btn btn-success m-3"
                >
                    Send Form101
                </button>
            </div>
            <div
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
                        <div className="col-sm-2 col-2">
                            {(contractForm && workerId) ||
                            worker.worker_contract ? (
                                <div>
                                    <span className="btn btn-success m-3">
                                        Signed
                                    </span>
                                </div>
                            ) : (
                                <span className="btn btn-warning m-3">
                                    Not Signed{" "}
                                </span>
                            )}
                        </div>
                        <div className="col-sm-4 col-4">
                            {(contractForm && workerId) ||
                            worker.worker_contract ? (
                                <div>
                                    <Link
                                        target="_blank"
                                        to={
                                            worker.worker_contract
                                                ? `/storage/uploads/worker/contract/${worker.worker_contract}`
                                                : `/worker-contract/` +
                                                  Base64.encode(workerId)
                                        }
                                        className="m-2 btn btn-pink"
                                    >
                                        View Contract
                                    </Link>
                                </div>
                            ) : (
                                <span className="btn btn-warning m-3">-</span>
                            )}
                        </div>
                        {Object.is(worker.worker_contract, null) &&
                        worker.is_exist
                            ? uploadFormDiv("worker_contract")
                            : ""}
                    </div>
                </div>
            </div>
            {worker.is_exist && !form ? (
                <div
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
                            <div className="col-sm-2 col-2">
                                {!form && worker.form_101 ? (
                                    <div>
                                        <span className="btn btn-success m-3">
                                            Signed{" "}
                                        </span>
                                    </div>
                                ) : (
                                    <span className="btn btn-warning m-3">
                                        Not Signed{" "}
                                    </span>
                                )}
                            </div>
                            <div className="col-sm-4 col-4">
                                {!form && worker.form_101 ? (
                                    <div>
                                        <Link
                                            target="_blank"
                                            to={
                                                worker.form_101
                                                    ? `/storage/uploads/worker/form101/${worker.form_101}`
                                                    : `/form101/` +
                                                      Base64.encode(
                                                          worker.id.toString()
                                                      )
                                            }
                                            className="m-2 m-2 btn btn-pink"
                                        >
                                            View Form
                                        </Link>
                                    </div>
                                ) : (
                                    <span className="btn btn-warning m-3">
                                        -
                                    </span>
                                )}
                            </div>
                            {Object.is(worker.form_101, null) && worker.is_exist
                                ? uploadFormDiv("form_101")
                                : ""}
                        </div>
                    </div>
                </div>
            ) : (
                <></>
            )}
            <div
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
                                Safety and Gear
                            </span>
                        </div>
                        <div className="col-sm-2 col-2">
                            {safetyAndGearForm || worker.form_insurance ? (
                                <div>
                                    <span className="btn btn-success m-3">
                                        Signed
                                    </span>
                                </div>
                            ) : (
                                <span className="btn btn-warning m-3">
                                    Not Signed{" "}
                                </span>
                            )}
                        </div>
                        <div className="col-sm-4 col-4">
                            {safetyAndGearForm || worker.form_insurance ? (
                                <div>
                                    <Link
                                        target="_blank"
                                        to={
                                            worker.form_insurance
                                                ? `/storage/uploads/worker/safetygear/${worker.form_insurance}`
                                                : `/worker-safe-gear/` +
                                                  Base64.encode(
                                                      worker.id.toString()
                                                  )
                                        }
                                        className="m-2 m-2 btn btn-pink"
                                    >
                                        View Safety and Gear Form
                                    </Link>
                                </div>
                            ) : (
                                <span className="btn btn-warning m-3">-</span>
                            )}
                        </div>
                        {Object.is(worker.form_insurance, null) &&
                        worker.is_exist
                            ? uploadFormDiv("form_insurance")
                            : ""}
                    </div>
                </div>
            </div>
            <div
                className="card card-widget widget-user-2"
                style={{ boxShadow: "none" }}
            >
                <div className="card-comments cardforResponsive"></div>
                <Form101Table formdata={forms} workerId={worker.id} />
            </div>
        </div>
    );
}
