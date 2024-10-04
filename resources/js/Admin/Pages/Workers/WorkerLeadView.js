import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useParams, useNavigate } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import axios from "axios";

export default function WorkerLeadView({ mode }) {
    const { t } = useTranslation();
    const params = useParams();
    const alert = useAlert();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [formValues, setFormValues] = useState({
        name: "",
        email: "",
        phone: "",
        status: "pending",
        ready_to_get_best_job: null,
        ready_to_work_in_house_cleaning: null,
        experience_in_house_cleaning: null,
        areas_aviv_herzliya_ramat_gan_kiryat_ono_good: null,
        none_id_visa: "none",
        you_have_valid_work_visa: null,
        work_sunday_to_thursday_fit_schedule_8_10am_12_2pm: null,
        full_or_part_time: "full time"
    });
    

    const statusArr = {
        "pending": "pending",
        "rejected": "rejected",
        "irrelevant": "irrelevant",
        "unanswered": "unanswered"
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleGetWorkerLead = async () => {
        try {
            const response = await axios.get(`/api/admin/worker-leads/${params.id}/edit`, { headers });
            const res = response?.data;            
            setFormValues({
                name: res?.name,
                email: res?.email,
                phone: res?.phone,
                status: res?.status,
                ready_to_get_best_job: res?.ready_to_get_best_job == 1 ? "true" : "false",
                ready_to_work_in_house_cleaning: res?.ready_to_work_in_house_cleaning == 1 ? "true" : "false",
                experience_in_house_cleaning: res?.experience_in_house_cleaning == 1 ? "true" : "false",
                areas_aviv_herzliya_ramat_gan_kiryat_ono_good: res?.areas_aviv_herzliya_ramat_gan_kiryat_ono_good == 1 ? "true" : "false",
                none_id_visa: res?.none_id_visa,
                you_have_valid_work_visa: res?.you_have_valid_work_visa == 1 ? "true" : "false",
                work_sunday_to_thursday_fit_schedule_8_10am_12_2pm: res?.work_sunday_to_thursday_fit_schedule_8_10am_12_2pm == 1 ? "true" : "false",
                full_or_part_time: res?.full_or_part_time

            });
        } catch (error) {
            console.log(error);
        }
    }

    const handleUpdate = async (e) => {
        e.preventDefault(); 
        try {
            await axios.put(`/api/admin/worker-leads/${params.id}`, formValues, { headers });
            alert.success("Worker lead updated successfully");
            handleGetWorkerLead();
        } catch (error) {
            console.log(error);
            alert.error("Error updating worker lead");
        }
    }

    const handleAdd = async (e) => {
        e.preventDefault(); 
        try {
            await axios.post(`/api/admin/worker-leads/add`, formValues, { headers });
            alert.success("Worker lead added successfully");
            navigate('/admin/worker-leads'); 
        } catch (error) {
            console.log(error);
            alert.error("Error adding worker lead");
        }
    }

    useEffect(() => {        
            handleGetWorkerLead();
    }, []);
    

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title editEmployer">
                        {mode === "edit" ? `Edit Worker Lead #${params.id}` : mode === "add" ? "Add Worker Lead" : `View Worker Lead #${params.id}`}
                    </h1>
                    <div className="dashBox p-4" style={{ background: "inherit", border: "none" }}>
                        <form onSubmit={mode === "edit" ? handleUpdate : mode === "add" ? handleAdd : (e) => e.preventDefault()}>
                            <div className="row">
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">{t("admin.global.Name")}</label>
                                        <input
                                            type="text"
                                            value={formValues.name}
                                            onChange={(e) => {
                                                setFormValues({ ...formValues, name: e.target.value });
                                            }}
                                            className="form-control"
                                            readOnly={mode !== "edit" && mode !== "add"}
                                            placeholder={t("admin.global.Name")}
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">{t("admin.global.Email")}</label>
                                        <input
                                            type="text"
                                            value={formValues.email}
                                            onChange={(e) => {
                                                setFormValues({ ...formValues, email: e.target.value });
                                            }}
                                            className="form-control"
                                            readOnly={mode !== "edit" && mode !== "add"}
                                            placeholder={t("admin.global.Email")}
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">{t("admin.global.Phone")}</label>
                                        <PhoneInput
                                            country={'il'}
                                            value={formValues.phone}
                                            onChange={(phone) => {
                                                setFormValues({ ...formValues, phone });
                                            }}
                                            inputClass="form-control"
                                            placeholder={t("admin.leads.phone")} // Move placeholder out of inputProps
                                            name="phone" // Move name out of inputProps
                                            required={true} // Move required out of inputProps
                                            readOnly={mode !== "edit" && mode !== "add"} // Set readOnly directly
                                        />

                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("admin.leads.ready_to_get_best_job")} *
                                        </label>
                                        <select
                                            className="form-control"
                                            value={ formValues.ready_to_get_best_job}  // Handle boolean to string conversion
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    ready_to_get_best_job: e.target.value === "true" ? true : false,  // Ensure boolean conversion
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
                                            {t("admin.leads.ready_to_work_in_house_cleaning")}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={ formValues.ready_to_work_in_house_cleaning}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    ready_to_work_in_house_cleaning: e.target.value === "true" ? true : false,  // Ensure boolean conversion
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
                                            {t("admin.leads.areas")}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={ formValues.experience_in_house_cleaning}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    experience_in_house_cleaning: e.target.value === "true" ? true : false,  // Ensure boolean conversion
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
                                            {t("admin.leads.none_id_visa")} (ILS)
                                        </label>
                                        <select
                                            className="form-control"
                                            value={formValues.none_id_visa}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    none_id_visa: e.target.value,
                                                });
                                            }}
                                        >
                                            <option value="none">None</option>
                                            <option value="id">ID</option>
                                            <option value="visa">Visa</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("admin.leads.you_have_valid_work_visa")}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={ formValues.you_have_valid_work_visa }
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    you_have_valid_work_visa: e.target.value === "true" ? true : false,  // Ensure boolean conversion
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
                                            {t("admin.leads.work_sunday_to_thursday_fit_schedule_8_10am_12_2pm")} *
                                        </label>
                                        <select
                                            className="form-control"
                                            value={ formValues.work_sunday_to_thursday_fit_schedule_8_10am_12_2pm}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    work_sunday_to_thursday_fit_schedule_8_10am_12_2pm: e.target.value === "true" ? true : false,  // Ensure boolean conversion
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
                                            {t("admin.leads.full_or_part_time")}
                                        </label>

                                        <select
                                            className="form-control"
                                            value={formValues.full_or_part_time}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    full_or_part_time: e.target.value,
                                                });
                                            }}
                                        >
                                            <option value="full time">Full Time</option>
                                            <option value="part time">Part Time</option>
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">Status</label>

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
                                    </div>
                                </div>
                            </div>
                            {(mode === "edit" || mode === "add") && (
                                <div className="form-group text-center">
                                    <button
                                        type="submit"
                                        className="btn px-3 text-center navyblue"
                                    > {mode === "add" ? <span>{t("workerInviteForm.add")} <i className="btn-icon fas fa-plus-circle"></i></span>: t("workerInviteForm.update")}</button>
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
