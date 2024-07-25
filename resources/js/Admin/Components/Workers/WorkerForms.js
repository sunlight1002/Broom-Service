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
    const [insuranceForm, setInsuranceForm] = useState(false);
    const [form, setForm] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getForm = () => {
        axios
            .get(`/api/getAllForms/${worker.id}`)
            .then((res) => {
                const formsData = res.data.forms;
                if (formsData.length > 0) {
                    setForms(formsData);
                    const _contractForm = formsData.find((f) =>
                        f.type.includes("contract")
                    );
                    const _saftyGearForm = formsData.find((f) =>
                        f.type.includes("saftey-and-gear")
                    );
                    const _form101Forms = formsData.filter((f) =>
                        f.type.includes("form101")
                    );
                    const _insuranceForm = formsData.find((f) =>
                        f.type.includes("insurance")
                    );
                    setContractForm(_contractForm);
                    setSafetyAndGearForm(_saftyGearForm);
                    setInsuranceForm(_insuranceForm);
                    setForm(_form101Forms.length > 0);
                }
            })
            .catch((e) => {
                alert.error(e.response.data?.message);
            });
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
        setIsSubmitting(true);
        const data = new FormData();
        data.append("workerId", worker.id);
        data.append("type", formType);
        axios
            .post(`/api/admin/form/send`, data, { headers })
            .then((res) => {
                setIsSubmitting(false);
                alert.success("Form sent!!");
                getForm();
            })
            .catch((err) => {
                setIsSubmitting(false);
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
            {worker.company_type === "my-company" && (
                <>
                    <div className="text-right pb-3">
                        <button
                            type="button"
                            onClick={(e) => handleSendForm(e, "form101")}
                            className="btn btn-success m-3"
                            disabled={isSubmitting}
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
                                    {(contractForm && worker) ||
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
                                    {(contractForm && worker) ||
                                    worker.worker_contract ? (
                                        <div>
                                            <Link
                                                target="_blank"
                                                to={
                                                    worker.worker_contract
                                                        ? `/storage/uploads/worker/contract/${worker.worker_contract}`
                                                        : `/worker-contract/` +
                                                          Base64.encode(
                                                            worker.id.toString()
                                                          )
                                                        
                                                }
                                                className="m-2 btn btn-pink"
                                            >
                                                View Contract
                                            </Link>
                                        </div>
                                    ) : (
                                        <span className="btn btn-warning m-3">
                                            -
                                        </span>
                                    )}
                                </div>
                                <div className="col-sm-2 col-2">
                                    {((contractForm || worker.form_insurance) && contractForm?.pdf_name) ? (
                                        <div>
                                            <a
                                                href={`/storage/signed-docs/${contractForm.pdf_name}`}
                                                target={"_blank"}
                                                download={`${contractForm.type}.pdf`}
                                                className="m-2 m-2 btn btn-pink"
                                            >
                                                <span className="btn-default">
                                                    <i className="fa fa-download"></i>
                                                </span>
                                            </a>
                                        </div>
                                    ) : (
                                        <span className="btn btn-warning m-3">
                                            -
                                        </span>
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
                                    <div className="col-sm-2 col-2">
                                        {((form || worker.form_insurance) && form?.pdf_name) ? (
                                            <div>
                                                <a
                                                    href={`/storage/signed-docs/${form.pdf_name}`}
                                                    target={"_blank"}
                                                    download={`${form.type}.pdf`}
                                                    className="m-2 m-2 btn btn-pink"
                                                >
                                                    <span className="btn-default">
                                                        <i className="fa fa-download"></i>
                                                    </span>
                                                </a>
                                            </div>
                                        ) : (
                                            <span className="btn btn-warning m-3">
                                                -
                                            </span>
                                        )}
                                    </div>
                                    {Object.is(worker.form_101, null) &&
                                    worker.is_exist
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
                                    {safetyAndGearForm ||
                                    worker.safety_and_gear_form ? (
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
                                    {safetyAndGearForm ||
                                    worker.safety_and_gear_form ? (
                                        <div>
                                            <Link
                                                target="_blank"
                                                to={
                                                    worker.safety_and_gear_form
                                                        ? `/storage/uploads/worker/safetygear/${worker.safety_and_gear_form}`
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
                                        <span className="btn btn-warning m-3">
                                            -
                                        </span>
                                    )}
                                </div>
                                <div className="col-sm-2 col-2">
                                    {((safetyAndGearForm || worker.form_insurance) && safetyAndGearForm?.pdf_name) ? (
                                        <div>
                                            <a
                                                href={`/storage/signed-docs/${safetyAndGearForm.pdf_name}`}
                                                target={"_blank"}
                                                download={`${safetyAndGearForm.type}.pdf`}
                                                className="m-2 m-2 btn btn-pink"
                                            >
                                                <span className="btn-default">
                                                    <i className="fa fa-download"></i>
                                                </span>
                                            </a>
                                        </div>
                                    ) : (
                                        <span className="btn btn-warning m-3">
                                            -
                                        </span>
                                    )}
                                </div>
                                {Object.is(worker.safety_and_gear_form, null) &&
                                worker.is_exist
                                    ? uploadFormDiv("safety_and_gear_form")
                                    : ""}
                            </div>
                        </div>
                    </div>
                </>
            )}
            {(worker && worker.country !== "Israel") && (
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
                                    Insurance
                                </span>
                            </div>
                            <div className="col-sm-2 col-2">
                                {insuranceForm || worker.form_insurance ? (
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
                                {insuranceForm || worker.form_insurance ? (
                                    <div>
                                        <Link
                                            target="_blank"
                                            to={
                                                worker.form_insurance
                                                    ? `/storage/uploads/worker/insurance/${worker.form_insurance}`
                                                    : `/insurance-form/` +
                                                      Base64.encode(
                                                          worker.id.toString()
                                                      )
                                            }
                                            className="m-2 m-2 btn btn-pink"
                                        >
                                            View Insurance Form
                                        </Link>
                                    </div>
                                ) : (
                                    <span className="btn btn-warning m-3">
                                        -
                                    </span>
                                )}
                            </div>
                            <div className="col-sm-2 col-2">
                                {((insuranceForm || worker.form_insurance) && insuranceForm?.pdf_name) ? (
                                    <div>
                                        <a
                                            href={`/storage/signed-docs/${insuranceForm.pdf_name}`}
                                            target={"_blank"}
                                            download={`${insuranceForm.type}.pdf`}
                                            className="m-2 m-2 btn btn-pink"
                                        >
                                            <span className="btn-default">
                                                <i className="fa fa-download"></i>
                                            </span>
                                        </a>
                                    </div>
                                ) : (
                                    <span className="btn btn-warning m-3">
                                        -
                                    </span>
                                )}
                            </div>
                            {Object.is(worker.form_insurance, null) &&
                            worker.is_exist
                                ? uploadFormDiv("form_insurance")
                                : ""}
                        </div>
                    </div>
                </div>
            )}
            {worker.company_type === "my-company" && (
                <div
                    className="card card-widget widget-user-2"
                    style={{ boxShadow: "none" }}
                >
                    <div className="card-comments cardforResponsive"></div>
                    <Form101Table formdata={forms} workerId={worker.id} />
                </div>
            )}
        </div>
    );
}
