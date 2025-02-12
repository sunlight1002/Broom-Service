import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useParams, useNavigate } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import PhoneInput from "react-phone-input-2";
import "react-phone-input-2/lib/style.css";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import axios from "axios";

export default function WorkerLeadView({ mode }) {
    const { t } = useTranslation();
    const params = useParams();
    const alert = useAlert();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({}); // State to store validation errors
    const [formValues, setFormValues] = useState({
        firstname: "",
        lastname: "",
        role: "cleaner",
        email: "",
        phone: "",
        lng: "heb",
        status: "pending",
        experience_in_house_cleaning: false,
        you_have_valid_work_visa: false,
        send_bot_message: false,
    });

    const statusArr = {
        pending: "pending",
        rejected: "rejected",
        irrelevant: "irrelevant",
        unanswered: "unanswered",
        hiring: "hiring",
        "will-think": "will-think",
        "not-hired": "not-hired",
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleGetWorkerLead = async () => {
        try {
            const response = await axios.get(
                `/api/admin/worker-leads/${params.id}/edit`,
                { headers }
            );
            const res = response?.data;
            setFormValues({
                firstname: res?.firstname,
                lastname: res?.lastname,
                role: res?.role,
                email: res?.email,
                phone: res?.phone,
                status: res?.status,
                experience_in_house_cleaning:
                    res?.experience_in_house_cleaning == 1 ? "true" : "false",
                you_have_valid_work_visa:
                    res?.you_have_valid_work_visa == 1 ? "true" : "false",
            });
        } catch (error) {
            console.log(error);
        }
    };

    const handleUpdate = async (e) => {
        e.preventDefault();
        try {
            await axios.put(
                `/api/admin/worker-leads/${params.id}`,
                formValues,
                { headers }
            );
            alert.success("Worker lead updated successfully");
            handleGetWorkerLead();
        } catch (error) {
            console.log(error);
            alert.error("Error updating worker lead");
        }
    };

    const handleAdd = async (e) => {
        e.preventDefault();
        console.log(formValues);

        try {
            await axios.post(`/api/admin/worker-leads/add`, formValues, {
                headers,
            });
            alert.success("Worker lead added successfully");
            navigate("/admin/worker-leads");
        } catch (error) {
            if (error.response && error.response.data.errors) {
                setErrors(error.response.data.errors);
            } else {
                console.error("Something went wrong:", error);
            }
        }
    };

    useEffect(() => {
        handleGetWorkerLead();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title editEmployer">
                        {mode === "edit"
                            ? `Edit Worker Lead #${params.id}`
                            : mode === "add"
                            ? "Add Worker Lead"
                            : `View Worker Lead #${params.id}`}
                    </h1>
                    <div
                        className="dashBox p-4"
                        style={{ background: "inherit", border: "none" }}
                    >
                        <form
                            onSubmit={
                                mode === "edit"
                                    ? handleUpdate
                                    : mode === "add"
                                    ? handleAdd
                                    : (e) => e.preventDefault()
                            }
                        >
                            <div className="row">
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.f_name")} *
                                        </label>
                                        <input
                                            type="text"
                                            value={formValues.firstname}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    firstname: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            readOnly={
                                                mode !== "edit" &&
                                                mode !== "add"
                                            }
                                            placeholder={t("admin.global.Name")}
                                        />
                                        {errors.firstname && (
                                            <small className="text-danger mb-1">
                                                {errors.firstname}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.l_name")} *
                                        </label>
                                        <input
                                            type="text"
                                            value={formValues.lastname}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    lastname: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            readOnly={
                                                mode !== "edit" &&
                                                mode !== "add"
                                            }
                                            placeholder={t("admin.global.Name")}
                                        />
                                        {errors.lastname && (
                                            <small className="text-danger mb-1">
                                                {errors.lastname}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("admin.global.Email")}
                                        </label>
                                        <input
                                            type="text"
                                            value={formValues.email}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    email: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            readOnly={
                                                mode !== "edit" &&
                                                mode !== "add"
                                            }
                                            placeholder={t(
                                                "admin.global.Email"
                                            )}
                                        />
                                        {errors.email && (
                                            <small className="text-danger mb-1">
                                                {errors.email}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("admin.global.Phone")}
                                        </label>
                                        <PhoneInput
                                            country={"il"}
                                            value={formValues.phone}
                                            onChange={(phone, country) => {
                                                // Remove leading '0' after country code
                                                const dialCode =
                                                    country.dialCode;
                                                let formattedPhone = phone;
                                                if (
                                                    phone.startsWith(
                                                        dialCode + "0"
                                                    )
                                                ) {
                                                    formattedPhone =
                                                        dialCode +
                                                        phone.slice(
                                                            dialCode.length + 1
                                                        );
                                                }
                                                setFormValues({
                                                    ...formValues,
                                                    phone: formattedPhone,
                                                });
                                            }}
                                            inputClass="form-control"
                                            placeholder={t("admin.leads.phone")} // Move placeholder out of inputProps
                                            name="phone" // Move name out of inputProps
                                            required={true} // Move required out of inputProps
                                            readOnly={
                                                mode !== "edit" &&
                                                mode !== "add"
                                            } // Set readOnly directly
                                        />
                                        {errors.phone && (
                                            <small className="text-danger mb-1">
                                                {errors.phone}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("admin.leads.areas")}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={
                                                formValues.experience_in_house_cleaning
                                            }
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    experience_in_house_cleaning:
                                                        e.target.value ===
                                                        "true"
                                                            ? true
                                                            : false, // Ensure boolean conversion
                                                });
                                            }}
                                        >
                                            <option value="true">Yes</option>
                                            <option value="false">No</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("nonIsrailContract.role")}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={formValues.role}
                                            onChange={(e) =>
                                                setFormValues({
                                                    ...formValues,
                                                    role: e.target.value,
                                                })
                                            }
                                        >
                                            <option value="cleaner">
                                                Cleaner
                                            </option>
                                            <option value="general_worker">
                                                General worker
                                            </option>
                                        </select>
                                        {errors.role && (
                                            <small className="text-danger mb-1">
                                                {errors.role}
                                            </small>
                                        )}
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(
                                                "admin.leads.you_have_valid_work_visa"
                                            )}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={
                                                formValues.you_have_valid_work_visa
                                            }
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    you_have_valid_work_visa:
                                                        e.target.value ===
                                                        "true"
                                                            ? true
                                                            : false, // Ensure boolean conversion
                                                });
                                            }}
                                        >
                                            <option value="true">Yes</option>
                                            <option value="false">No</option>
                                        </select>
                                    </div>
                                </div>
                                {mode == "add" && (
                                    <div className="col-sm-6">
                                        <div className="form-group d-flex align-items-center">
                                            <label
                                                htmlFor="waBot"
                                                className="control-label navyblueColor"
                                                style={{ width: "10rem" }}
                                            >
                                                {t(
                                                    "admin.leads.AddLead.SendWPBotMessage"
                                                )}
                                            </label>
                                            <input
                                                type="checkbox"
                                                id="waBot"
                                                value={
                                                    formValues.send_bot_message
                                                }
                                                onChange={(e) => {
                                                    setFormValues({
                                                        ...formValues,
                                                        send_bot_message:
                                                            e.target.checked,
                                                    });
                                                }}
                                            />
                                        </div>
                                    </div>
                                )}
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Status
                                        </label>

                                        <select
                                            name="status"
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    status: e.target.value,
                                                });
                                            }}
                                            value={formValues.status}
                                            className="form-control mb-3"
                                        >
                                            {Object.keys(statusArr).map((s) => (
                                                <option key={s} value={s}>
                                                    {statusArr[s]}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.status && (
                                            <small className="text-danger mb-1">
                                                {errors.status}
                                            </small>
                                        )}
                                    </div>
                                </div>
                            </div>
                            {(mode === "edit" || mode === "add") && (
                                <div className="form-group text-center">
                                    <button
                                        type="submit"
                                        className="btn px-3 text-center navyblue"
                                    >
                                        {" "}
                                        {mode === "add" ? (
                                            <span>
                                                {t("workerInviteForm.add")}{" "}
                                                <i className="btn-icon fas fa-plus-circle"></i>
                                            </span>
                                        ) : (
                                            t("workerInviteForm.update")
                                        )}
                                    </button>
                                </div>
                            )}
                        </form>
                    </div>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
