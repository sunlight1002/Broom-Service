import axios from "axios";
import React, { useState, useEffect } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import { Base64 } from "js-base64";
import Form101Table from "./Form101Table";
import { useTranslation } from "react-i18next";
import Swal from "sweetalert2";

export default function WorkerForms({ worker, getWorkerDetails }) {
    const { t } = useTranslation();
    const [forms, setForms] = useState([]);
    const [contractForm, setContractForm] = useState(false);
    const [safetyAndGearForm, setSafetyAndGearForm] = useState(false);
    const [insuranceForm, setInsuranceForm] = useState(false);
    const [ManpowerSaftyForm, setManpowerSaftyForm] = useState(false);
    const [form, setForm] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const navigate = useNavigate();

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
                    const _manpowerForm = formsData.find((f) =>
                        f.type.includes("manpower-saftey")
                    );
                    setContractForm(_contractForm);
                    setSafetyAndGearForm(_saftyGearForm);
                    setInsuranceForm(_insuranceForm);
                    setManpowerSaftyForm(_manpowerForm);
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



    const ResetForm = async (form_id, type) => {
        
        Swal.fire({
            title: "Are you sure?",
            text: "You want to reset this form!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes",
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await axios.post(`/api/admin/document/reset/${form_id}`, {}, { headers })
                    alert.success(res?.data?.message)
                    if(type == "form101"){
                        window.open(`/form101/${Base64.encode(worker.id.toString())}`, "_blank");
                    }else if(type == "contract"){
                        window.open(`/worker-contract/${Base64.encode(worker.id.toString())}`, "_blank");
                    }else if(type == "safety_and_gear_form"){
                        window.open(`/worker-safe-gear/${Base64.encode(worker.id.toString())}`, "_blank");
                    }else if(type == "form_insurance"){
                        window.open(`/insurance-form/${Base64.encode(worker.id.toString())}`, "_blank");
                    }else if(type == "2form101"){
                        window.open(`/form101/${Base64.encode(worker.id.toString())}/${Base64.encode(form_id.toString())}`, "_blank");
                    }else if(type == "manpower"){
                        window.open(`/manpower-safty-form/${Base64.encode(worker.id.toString())}`, "_blank");
                    }
                    getForm();
                } catch (error) {
                    alert.error(error.response?.data?.message);
                }
            }
        });
    }

    const handleNotSigned = (form_id, type) => {
        if(type == "form101"){
            window.open(`/form101/${Base64.encode(worker.id.toString())}`, "_blank");
        }else if(type == "contract"){
            window.open(`/worker-contract/${Base64.encode(worker.id.toString())}`, "_blank");
        }else if(type == "safety_and_gear_form"){
            window.open(`/worker-safe-gear/${Base64.encode(worker.id.toString())}`, "_blank");
        }else if(type == "form_insurance"){
            window.open(`/insurance-form/${Base64.encode(worker.id.toString())}`, "_blank");
        }else if(type == "2form101"){
            window.open(`/form101/${Base64.encode(worker.id.toString())}/${Base64.encode(form_id.toString())}`, "_blank");
        }else if(type == "manpower"){
            window.open(`/manpower-safty-form/${Base64.encode(worker.id.toString())}`, "_blank");
        }
    }

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
            {worker.company_type === "manpower" && (
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
                    <div className="d-flex justify-content-between align-items-center flex-res-column-505" >
                        <div className="mb-2">
                            <span className="noteDate font-weight-bold">
                                {t("formTxt.manpowerSaftyForm")}
                            </span>
                        </div>

                        <div className="d-flex ">
                            <div className=" mb-2 mr-4 text-center">
                                {(ManpowerSaftyForm?.submitted_at && worker)? (
                                    <span className="btn btn-success">
                                        {t("global.signed")}
                                    </span>
                                ) : (
                                    <span className="btn btn-warning "
                                    onClick={() => handleNotSigned(ManpowerSaftyForm?.id, "manpower")}
                                    >
                                        {t("global.notSigned")}
                                    </span>
                                )}
                            </div>

                            <div className=" mb-2 mr-4  text-center">
                                {(ManpowerSaftyForm?.submitted_at && worker) ? (
                                    <Link
                                        target="_blank"
                                        to={
                                            worker.ManpowerSaftyForm
                                                ? `/storage/uploads/worker/contract/${worker.ManpowerSaftyForm}`
                                                : `/worker-forms/` +
                                                Base64.encode(worker.id.toString())
                                        }
                                        className="btn btn-warning"
                                    >
                                        <i className="fa fa-eye"></i>
                                    </Link>
                                ) : (
                                    <span className="btn btn-warning">-</span>
                                )}
                            </div>

                            <div className="mb-2 mr-4 text-center">
                                {((ManpowerSaftyForm) && ManpowerSaftyForm?.pdf_name) ? (
                                    <div className="d-flex" style={{ gap: "22px" }}>
                                        <a
                                            href={`/storage/signed-docs/${ManpowerSaftyForm.pdf_name}`}
                                            target={"_blank"}
                                            download={`${ManpowerSaftyForm.type}.pdf`}
                                            className="btn btn-warning"
                                        >
                                            <i className="fa fa-download"></i>
                                        </a>
                                        <button onClick={() => ResetForm(ManpowerSaftyForm?.id, "manpower")} className="btn btn-warning">Reset</button>
                                    </div>
                                ) : (
                                    <span className="btn btn-warning">-</span>
                                )}
                            </div>

                            {/* {Object.is(worker.worker_contract, null) && worker.is_exist ? (
                                <div className="col-lg-12">
                                    {uploadFormDiv("worker_contract")}
                                </div>
                            ) : (
                                ""
                            )} */}
                        </div>
                    </div>
                </div>
            </div>
            )}
            {worker.company_type === "my-company" && (
                <>
                    <div className="text-right pb-3">
                        <button
                            type="button"
                            onClick={(e) => handleSendForm(e, "form101")}
                            className="btn btn-success m-3"
                            disabled={isSubmitting}
                        >
                            {t("global.sendForm101")}
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
                            <div className="d-flex justify-content-between align-items-center flex-res-column-505" >
                                <div className="mb-2">
                                    <span className="noteDate font-weight-bold">
                                        {t("formTxt.contractForm")}
                                    </span>
                                </div>

                                <div className="d-flex ">
                                    <div className=" mb-2 mr-4 text-center">
                                        {(contractForm?.submitted_at && worker) || worker.worker_contract ? (
                                            <span className="btn btn-success">
                                                {t("global.signed")}
                                            </span>
                                        ) : (
                                            <span className="btn btn-warning "
                                            onClick={() => handleNotSigned(contractForm?.id, "contract")}
                                            >
                                                {t("global.notSigned")}
                                            </span>
                                        )}
                                    </div>

                                    <div className=" mb-2 mr-4  text-center">
                                        {(contractForm?.submitted_at && worker) || worker.worker_contract ? (
                                            <Link
                                                target="_blank"
                                                to={
                                                    worker.worker_contract
                                                        ? `/storage/uploads/worker/contract/${worker.worker_contract}`
                                                        : `/worker-contract/` +
                                                        Base64.encode(worker.id.toString())
                                                }
                                                className="btn btn-warning"
                                            >
                                                <i className="fa fa-eye"></i>
                                            </Link>
                                        ) : (
                                            <span className="btn btn-warning">-</span>
                                        )}
                                    </div>

                                    <div className="mb-2 mr-4 text-center">
                                        {((contractForm || worker.form_insurance) && contractForm?.pdf_name) ? (
                                            <div className="d-flex" style={{ gap: "22px" }}>
                                                <a
                                                    href={`/storage/signed-docs/${contractForm.pdf_name}`}
                                                    target={"_blank"}
                                                    download={`${contractForm.type}.pdf`}
                                                    className="btn btn-warning"
                                                >
                                                    <i className="fa fa-download"></i>
                                                </a>
                                                <button onClick={() => ResetForm(contractForm?.id, "contract")} className="btn btn-warning">Reset</button>
                                            </div>
                                        ) : (
                                            <span className="btn btn-warning">-</span>
                                        )}
                                    </div>

                                    {Object.is(worker.worker_contract, null) && worker.is_exist ? (
                                        <div className="col-lg-12">
                                            {uploadFormDiv("worker_contract")}
                                        </div>
                                    ) : (
                                        ""
                                    )}
                                </div>
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
                                <div className="d-flex justify-content-between align-items-center flex-res-column-505" >
                                    <div className="mb-2">
                                        <span className="noteDate font-weight-bold">
                                            {t("form101.title")}
                                        </span>
                                    </div>

                                    <div className="d-flex">
                                        <div className="mb-2 text-center">
                                            {!form?.submitted_at && worker.form_101 ? (
                                                <span className="btn btn-success">
                                                    {t("global.signed")}
                                                </span>
                                            ) : (
                                                <span className="btn btn-warning"
                                                onClick={() => handleNotSigned(form?.id, "form101")}
                                                >
                                                    {t("global.notSigned")}
                                                </span>
                                            )}
                                        </div>
                                        <div className="mb-2 text-center">
                                            {!form?.submitted_at && worker.form_101 ? (
                                                <Link
                                                    target="_blank"
                                                    to={
                                                        worker.form_101
                                                            ? `/storage/uploads/worker/form101/${worker.form_101}`
                                                            : `/form101/` + Base64.encode(worker.id.toString())
                                                    }
                                                    className="btn btn-warning"
                                                >
                                                    <i className="fa fa-eye"></i>
                                                </Link>
                                            ) : (
                                                <span className="btn btn-warning">-</span>
                                            )}
                                        </div>
                                        <div className="mb-2 text-center">
                                            {((form || worker.form_insurance) && form?.pdf_name) ? (
                                                <div className="d-flex" style={{ gap: "22px" }}>
                                                    <a
                                                        href={`/storage/signed-docs/${form.pdf_name}`}
                                                        target={"_blank"}
                                                        download={`${form.type}.pdf`}
                                                        className="btn btn-warning"
                                                    >
                                                        <i className="fa fa-download"></i>
                                                    </a>
                                                    <button onClick={() => ResetForm(form?.id, "form101")} className="btn btn-warning">Reset</button>
                                                </div>
                                            ) : (
                                                <span className="btn btn-warning">-</span>
                                            )}
                                        </div>

                                        {Object.is(worker.form_101, null) && worker.is_exist && (
                                            <div className="col-12">
                                                {uploadFormDiv("form_101")}
                                            </div>
                                        )}
                                    </div>
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
                            <div className="d-flex justify-content-between align-items-center flex-res-column-505" >

                                <div className="mb-2">
                                    <span className="noteDate font-weight-bold">
                                        {t("global.safetyAbdGear")}
                                    </span>
                                </div>

                                <div className="d-flex">
                                    <div className="mb-2 mr-4 text-center">
                                        {safetyAndGearForm?.submitted_at || worker.safety_and_gear_form ? (
                                            <span className="btn btn-success ">
                                                {t("global.signed")}
                                            </span>
                                        ) : (
                                            <span className="btn btn-warning"
                                            onClick={() => handleNotSigned(safetyAndGearForm?.id, "safety_and_gear_form")}
                                            >
                                                {t("global.notSigned")}
                                            </span>
                                        )}
                                    </div>

                                    <div className=" mb-2 mr-4 text-center">
                                        {safetyAndGearForm?.submitted_at || worker.safety_and_gear_form ? (
                                            <Link
                                                target="_blank"
                                                to={
                                                    worker.safety_and_gear_form
                                                        ? `/storage/uploads/worker/safetygear/${worker.safety_and_gear_form}`
                                                        : `/worker-safe-gear/` + Base64.encode(worker.id.toString())
                                                }
                                                className="btn btn-warning"
                                            >
                                                <i className="fa fa-eye"></i>
                                            </Link>
                                        ) : (
                                            <span className="btn btn-warning">-</span>
                                        )}
                                    </div>

                                    <div className="mb-2 mr-4 text-center">
                                        {((safetyAndGearForm || worker.form_insurance) && safetyAndGearForm?.pdf_name) ? (
                                            <div className="d-flex" style={{ gap: "22px" }}>
                                                <a
                                                    href={`/storage/signed-docs/${safetyAndGearForm.pdf_name}`}
                                                    target="_blank"
                                                    download={`${safetyAndGearForm.type}.pdf`}
                                                    className="btn btn-warning"
                                                >
                                                    <i className="fa fa-download"></i>
                                                </a>
                                                <button onClick={() => ResetForm(safetyAndGearForm?.id, "safety_and_gear_form")} className="btn btn-warning">Reset</button>
                                            </div>

                                        ) : (
                                            <span className="btn btn-warning">-</span>
                                        )}
                                    </div>
                                    {Object.is(worker.safety_and_gear_form, null) &&
                                        worker.is_exist
                                        ? uploadFormDiv("safety_and_gear_form")
                                        : ""}
                                </div>
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
                        <div className="d-flex justify-content-between align-items-center flex-res-column-505" >
                            <div className=" mb-2">
                                <span className="noteDate font-weight-bold">
                                    {t("formTxt.insuranceForm")}
                                </span>
                            </div>

                            <div className="d-flex">
                                <div className=" mb-2 mr-4 text-center">
                                    {insuranceForm?.submitted_at || worker.form_insurance ? (
                                        <span className="btn btn-success">
                                            {t("global.signed")}
                                        </span>
                                    ) : (
                                        <span className="btn btn-warning"
                                        onClick={() => handleNotSigned(insuranceForm?.id, "form_insurance")}
                                        >
                                            {t("global.notSigned")}
                                        </span>
                                    )}
                                </div>

                                <div className="mb-2 mr-4 text-center">
                                    {insuranceForm?.submitted_at || worker.form_insurance ? (
                                        <Link
                                            target="_blank"
                                            to={
                                                worker.form_insurance
                                                    ? `/storage/uploads/worker/insurance/${worker.form_insurance}`
                                                    : `/insurance-form/` + Base64.encode(worker.id.toString())
                                            }
                                            className="btn btn-warning"
                                        >
                                            <i className="fa fa-eye"></i>

                                        </Link>
                                    ) : (
                                        <span className="btn btn-warning">-</span>
                                    )}
                                </div>

                                <div className=" mb-2 mr-4 text-center">
                                    {((insuranceForm || worker.form_insurance) && insuranceForm?.pdf_name) ? (
                                        <div className="d-flex" style={{ gap: "22px" }}>
                                            <a
                                                href={`/storage/signed-docs/${insuranceForm.pdf_name}`}
                                                target="_blank"
                                                download={`${insuranceForm.type}.pdf`}
                                                className="btn btn-warning "
                                            >
                                                <i className="fa fa-download"></i>
                                            </a>
                                            <button onClick={() => ResetForm(insuranceForm?.id, "form_insurance")} className="btn btn-warning">Reset</button>
                                        </div>
                                    ) : (
                                        <span className="btn btn-warning">-</span>
                                    )}
                                </div>

                                {Object.is(worker.form_insurance, null) &&
                                    worker.is_exist
                                    ? uploadFormDiv("form_insurance")
                                    : ""}
                            </div>
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
                    <Form101Table formdata={forms} workerId={worker.id} ResetForm={ResetForm} handleNotSigned={handleNotSigned}/>
                </div>
            )}
        </div>
    );
}
