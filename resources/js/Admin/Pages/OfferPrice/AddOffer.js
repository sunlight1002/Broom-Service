import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { SelectPicker } from "rsuite";
import "rsuite/dist/rsuite.min.css";
import axios from "axios";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import OfferServiceMenu from "../../Pages/OfferPrice/OfferServiceMenu";

export default function AddOffer() {
    const alert = useAlert();
    const navigate = useNavigate();
    const queryParams = new URLSearchParams(window.location.search);
    const cid = parseInt(queryParams.get("c"));
    const [clientID, setClientID] = useState(cid ? cid : "");
    const [formValues, setFormValues] = useState([]);
    const [services, setServices] = useState([]);
    const [frequencies, setFrequencies] = useState([]);
    const [addresses, setAddresses] = useState([]);
    const [clientOptions, setClientOptions] = useState([]);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSave = (indexKey, tmpJobData) => {
        let newFormValues = [...formValues];
        if (indexKey > -1) {
            newFormValues[indexKey] = tmpJobData;
        } else {
            newFormValues.push(tmpJobData);
        }
        setFormValues(newFormValues);
    };

    let removeFormFields = (i) => {
        let newFormValues = [...formValues];
        newFormValues.splice(i, 1);
        setFormValues(newFormValues);
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getSelectedClient = () => {
        axios
            .get(`/api/admin/clients/${clientID}/edit`, { headers })
            .then((response) => {
                if (response.data.client) {
                    setAddresses(response.data.client.property_addresses);
                } else {
                    setAddresses([]);
                }
            });
    };

    const getClients = () => {
        axios.get("/api/admin/all-clients", { headers }).then((res) => {
            setClientOptions(
                res.data.clients.map((c, i) => {
                    return {
                        value: c.id,
                        label: c.firstname + " " + c.lastname,
                    };
                })
            );
        });
    };

    const getServices = (lng) => {
        axios
            .post("/api/admin/all-services", { lng }, { headers })
            .then((res) => {
                setServices(res.data.services);
            });
    };
    const getFrequency = (lng) => {
        axios
            .post("/api/admin/all-service-schedule", { lng }, { headers })
            .then((res) => {
                setFrequencies(res.data.schedules);
            });
    };

    const handleServiceLng = (_client) => {
        axios.get(`/api/admin/clients/${_client}`, { headers }).then((res) => {
            const lng = res.data.client.lng;
            getServices(lng);
            getFrequency(lng);
        });
    };

    useEffect(() => {
        getClients();
        if (cid) {
            handleServiceLng(cid);
        }
    }, []);

    let handleSubmit = (event, _action) => {
        event.preventDefault();

        for (let t in formValues) {
            if (formValues[t].service == "" || formValues[t].service == 0) {
                alert.error("One of the service is not selected");
                return false;
            }

            let ot = document.querySelector("#other_title" + t);

            if (formValues[t].service == "10" && ot != undefined) {
                if (formValues[t].other_title == "") {
                    alert.error("Other title cannot be blank");
                    return false;
                }
                formValues[t].other_title = document.querySelector(
                    "#other_title" + t
                ).value;
            }

            if (formValues[t].frequency == "" || formValues[t].frequency == 0) {
                alert.error("One of the frequency is not selected");
                return false;
            }

            let workerIssue = true;
            for (let index = 0; index < formValues[t].workers.length; index++) {
                const _worker = formValues[t].workers[index];

                if (_worker.jobHours == "") {
                    alert.error("One of the job hours value is missing");
                    workerIssue = false;
                    break;
                }
            }

            if (!workerIssue) {
                return workerIssue;
            }

            !formValues[t].type ? (formValues[t].type = "fixed") : "";
            if (formValues[t].type == "hourly") {
                if (formValues[t].rateperhour == "") {
                    alert.error("One of the rate per hour value is missing");
                    return false;
                }
            } else {
                if (formValues[t].fixed_price == "") {
                    alert.error("One of the job price is missing");
                    return false;
                }
            }
        }
        setIsSubmitting(true);

        const data = {
            client_id: clientID,
            status: "sent",
            services: JSON.stringify(formValues),
            action: _action,
        };

        axios.post(`/api/admin/offers`, data, { headers }).then((response) => {
            if (response.data.errors) {
                for (let e in response.data.errors) {
                    alert.error(response.data.errors[e]);
                }
            } else {
                alert.success(response.data.message);
                setTimeout(() => {
                    navigate(`/admin/offered-price`);
                }, 1000);
            }
            setIsSubmitting(false);
        });
    };

    useEffect(() => {
        if (clientID) {
            getSelectedClient();
        }
    }, [clientID]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="AddOffer">
                    <h1 className="page-title addEmployer">Add Offer</h1>
                    <div className="card">
                        <div className="card-body">
                            <form>
                                <div className="row">
                                    <div className="col-sm-12">
                                        <div className="form-group">
                                            <label className="control-label">
                                                Client
                                            </label>
                                            <SelectPicker
                                                data={clientOptions}
                                                value={clientID}
                                                onChange={(value, event) => {
                                                    setClientID(value);
                                                    handleServiceLng(value);
                                                }}
                                                size="lg"
                                                required
                                            />
                                        </div>

                                        <div className="card card-dark">
                                            <div className="card-header card-black">
                                                <h3 className="card-title">
                                                    Services
                                                </h3>
                                            </div>
                                            <div className="card-body">
                                                <OfferServiceMenu
                                                    addresses={addresses}
                                                    services={services}
                                                    frequencies={frequencies}
                                                    formValues={formValues}
                                                    handleSaveForm={handleSave}
                                                    handleRemoveFormFields={
                                                        removeFormFields
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <button
                                        type="submit"
                                        value="Save"
                                        disabled={isSubmitting}
                                        className="btn btn-success"
                                        onClick={(e) => {
                                            handleSubmit(e, "Save");
                                        }}
                                        style={{ marginInline: "6px" }}
                                    >
                                        Save
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={isSubmitting}
                                        className="btn btn-pink"
                                        onClick={(e) => {
                                            handleSubmit(e, "Save and Send");
                                        }}
                                    >
                                        Save and Send
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
