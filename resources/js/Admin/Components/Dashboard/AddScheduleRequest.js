import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useParams, useNavigate } from "react-router-dom";
import Sidebar from "../../Layouts/Sidebar";
import PhoneInput from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";
import axios from "axios";
import Select from "react-select";


export default function AddScheduleRequest({ mode }) {
    const { t } = useTranslation();
    const params = useParams();
    const alert = useAlert();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({}); // State to store validation errors
    const [allClients, setAllClients] = useState([]);
    const [allWorkers, setAllWorkers] = useState([]);
    const [workersInclude, setWorkersInclude] = useState([]);
    const [clientsInclude, setClientsInclude] = useState([])
    const [formValues, setFormValues] = useState({
        worker_ids: [],
        client_ids: [],
        reason: "",
        comment: ""
    });


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };


    const handleSubmit = async (e) => {
        e.preventDefault();
        const selectedWorkerInculdeIds = workersInclude.map(worker => worker.value);
        const selectedClientsInculdeIds = clientsInclude.map(client => client.value);

        const data = {
            worker_ids: selectedWorkerInculdeIds,
            client_ids: selectedClientsInculdeIds,
            reason: formValues.reason,
            comment: formValues.comment
        }
        try {
           const res = await axios.post(`/api/admin/add-schedule-request`, data, { headers });
           setFormValues({
               worker_ids: [],
               client_ids: [],
               reason: "",
               comment: ""
           })
           alert.success(res?.data?.message);
           navigate("/admin/schedule-requests");

           
        } catch (error) {
            if (error.response && error.response.data.errors) {
                setErrors(error.response.data.errors);
            } else {
                console.error("Something went wrong:", error);
            }
        }
    };

    const getWorkers = () => {
        axios.get("/api/admin/all-workers", { headers }).then((res) => {
            const { workers } = res.data;
            const mapWorkersArr = workers.map((w) => {
                let obj = {
                    value: w.id,
                    label: `${w.firstname} ${w.lastname}`,
                };
                return obj;
            });
            setAllWorkers(mapWorkersArr);
        });
    };

    const getClients = () => {
        axios.get("/api/admin/all-clients", { headers }).then((res) => {
            const { clients } = res.data;
            const mapClientsArr = clients.map((c) => {
                let obj = {
                    value: c.id,
                    label: `${c.firstname} ${c.lastname}`,
                };
                return obj;
            });
            setAllClients(mapClientsArr);
        });
    };

    useEffect(() => {
        getWorkers();
        getClients();
    }, []);


    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title editEmployer">
                        {t("global.add_schedule_request")}
                    </h1>
                    <div className="dashBox p-4" style={{ background: "inherit", border: "none" }}>
                        <form onSubmit={handleSubmit}>
                            <div className="row">
                                <div className="col-sm-4 ">
                                    <div
                                        className=" mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
                                        {t("admin.global.client_includes")}
                                    </div>
                                    <div className="d-flex align-items-center flex-wrap">
                                        <Select
                                            value={clientsInclude}
                                            name="clients"
                                            isMulti
                                            options={allClients}
                                            className="basic-multi-single skyBorder"
                                            isClearable={true}
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                            )}
                                            classNamePrefix="select"
                                            onChange={(newValue) =>
                                                setClientsInclude(newValue)
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="col-sm-4 ">
                                    <div
                                        className=" mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
                                        {t("admin.global.worker_includes")}
                                    </div>
                                    <div className="d-flex align-items-center flex-wrap">
                                        <Select
                                            value={workersInclude}
                                            name="workers"
                                            isMulti
                                            options={allWorkers}
                                            className="basic-multi-single skyBorder"
                                            isClearable={true}
                                            placeholder={t(
                                                "admin.leads.AddLead.addAddress.Options.pleaseSelect"
                                            )}
                                            classNamePrefix="select"
                                            onChange={(newValue) =>
                                                setWorkersInclude(newValue)
                                            }
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-6 mt-3">
                                    <div className="form-group">
                                        <label className="control-label">{t("global.reason")}</label>

                                        <input
                                            name="reason"
                                            type="text"
                                            value={formValues.reason}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    reason: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm-6">
                                    <div className="form-group">
                                        <label className="control-label">{t("global.comments")}</label>

                                        <textarea
                                            name="reason"
                                            type="text"
                                            value={formValues.comment}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    comment: e.target.value,
                                                });
                                            }}
                                            className="form-control"
                                            required
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                            <div className="d-flex justify-content-start">
                                <button
                                    type="submit"
                                    className="btn px-3 text-center navyblue"
                                > <span>{t("workerInviteForm.add")} <i className="btn-icon fas fa-plus-circle"></i></span></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
