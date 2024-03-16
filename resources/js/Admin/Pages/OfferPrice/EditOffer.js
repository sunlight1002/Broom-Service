import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import axios from "axios";
import { useAlert } from "react-alert";
import { useNavigate, useParams } from "react-router-dom";
import { SelectPicker } from "rsuite";
import OfferServiceMenu from "../../Pages/OfferPrice/OfferServiceMenu";

export default function EditOffer() {
    const alert = useAlert();
    const navigate = useNavigate();
    const param = useParams();
    const [clientID, setClientID] = useState("");
    const [formValues, setFormValues] = useState([
        {
            service: "",
            name: "",
            type: "",
            freq_name: "",
            frequency: "",
            fixed_price: "",
            jobHours: "",
            rateperhour: "",
            other_title: "",
            totalamount: "",
            template: "",
            cycle: "",
            period: "",
        },
    ]);
    const [status, setStatus] = useState("");
    const [services, setServices] = useState([]);
    const [frequencies, setFrequencies] = useState([]);
    const [addresses, setAddresses] = useState([]);
    const [clientOptions, setClientOptions] = useState([]);

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

    const handleSave = (indexKey, tmpJobData) => {
        let newFormValues = [...formValues];
        if (indexKey > -1 && indexKey !== "" && indexKey !== undefined) {
            newFormValues[indexKey] = tmpJobData;
        } else {
            newFormValues = [...formValues, ...tmpJobData];
        }
        setFormValues(newFormValues);
    };

    let removeFormFields = (i) => {
        let newFormValues = [...formValues];
        newFormValues.splice(i, 1);
        setFormValues(newFormValues);
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

    const handleServiceLng = (_client) => {
        axios.get(`/api/admin/clients/${_client}`, { headers }).then((res) => {
            getServices(res.data.client.lng);
            getFrequency(res.data.client.lng);
        });
    };

    let handleUpdate = (event) => {
        event.preventDefault();
        let to = 0;
        let taxper = 17;

        for (let t in formValues) {
            if (formValues[t].service == "" || formValues[t].service == 0) {
                alert.error("One of the service is not selected");
                return false;
            }

            if (formValues[t].frequency == "" || formValues[t].frequency == 0) {
                alert.error("One of the frequency is not selected");
                return false;
            }
            !formValues[t].type ? (formValues[t].type = "fixed") : "";
            if (formValues[t].type == "hourly") {
                if (formValues[t].jobHours == "") {
                    alert.error("One of the job hours value is missing");
                    return false;
                }
                if (formValues[t].service == "") {
                    alert.error("One of the rate per hour value is missing");
                    return false;
                }
                formValues[t].totalamount = parseInt(
                    formValues[t].jobHours * formValues[t].rateperhour
                );
                to += parseInt(formValues[t].totalamount);
            } else {
                if (formValues[t].fixed_price == "") {
                    alert.error("One of the job price is missing");
                    return false;
                }
                formValues[t].totalamount = parseInt(formValues[t].fixed_price);
                to += parseInt(formValues[t].fixed_price);
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

            if (formValues[t].frequency) {
            }
        }

        let tax = (taxper / 100) * to;
        const data = {
            client_id: clientID,
            status: status,
            subtotal: to,
            total: to + tax,
            services: JSON.stringify(formValues),
            action: event.target.value,
        };

        event.target.setAttribute("disabled", true);
        event.target.value =
            event.target.value == "Save" ? "Saving.." : "Sending..";
        axios
            .put(`/api/admin/offers/${param.id}`, data, { headers })
            .then((response) => {
                if (response.data.errors) {
                    for (let e in response.data.errors) {
                        alert.error(response.data.errors[e]);
                    }
                    document
                        .querySelector(".saveBtn")
                        .removeAttribute("disabled");
                    document.querySelector(".saveBtn").value =
                        event.target.value == "Save" ? "Save" : "Save and Send";
                } else {
                    alert.success(response.data.message);
                    setTimeout(() => {
                        navigate(`/admin/offered-price`);
                    }, 1000);
                }
            });
    };

    const getOffer = () => {
        axios
            .get(`/api/admin/offers/${param.id}/edit`, { headers })
            .then((res) => {
                const d = res.data.offer[0];
                setClientID(d.client_id);
                handleServiceLng(d.client_id);
                setStatus(d.status);
                setFormValues(JSON.parse(d.services));
            });
    };

    const getFrequency = (lng) => {
        axios
            .post("/api/admin/all-service-schedule", { lng }, { headers })
            .then((res) => {
                setFrequencies(res.data.schedules);
            });
    };

    useEffect(() => {
        getClients();
        getOffer();
    }, []);

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
                    <h1 className="page-title addEmployer">Edit Offer</h1>
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
                                                defaultValue={clientID}
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
                                                    handleSaveForm={
                                                        handleSave
                                                    }
                                                    handleRemoveFormFields={
                                                        removeFormFields
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="text-right">
                                    <input
                                        type="submit"
                                        value="Save"
                                        className="btn btn-success saveBtn"
                                        onClick={handleUpdate}
                                        style={{ marginInline: "6px" }}
                                    />
                                    <input
                                        type="submit"
                                        value="Save and Send"
                                        className="btn btn-pink saveBtn"
                                        onClick={handleUpdate}
                                    />
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
