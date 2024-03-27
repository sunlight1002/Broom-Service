import React, { useState, useEffect, useMemo } from "react";
import axios from "axios";
import Moment from "moment";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import Select from "react-select";
import { SelectPicker } from "rsuite";
import Swal from "sweetalert2";

import Sidebar from "../../../Layouts/Sidebar";

export default function AddOrder() {
    const [selectedClientID, setSelectedClientID] = useState(null);
    const [selectedJobID, setSelectedJobID] = useState(null);
    const [loading, setLoading] = useState(false);
    const [formValues, setFormValues] = useState({
        description: null,
        unitprice: 0,
        quantity: 0,
    });
    const [lng, setLng] = useState();

    const queryParams = new URLSearchParams(window.location.search);
    const jobID = queryParams.get("j");
    const clientID = queryParams.get("c");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const [clients, setClients] = useState();
    const [cjobs, setCjobs] = useState();
    const [jservice, setjService] = useState(null);

    const alert = useAlert();
    const navigate = useNavigate();

    const getCustomers = () => {
        axios.get(`/api/admin/all-clients`, { headers }).then((res) => {
            setClients(res.data.clients);
        });
    };

    const getJobs = (cid) => {
        setCjobs([]);
        axios
            .post(`/api/admin/invoice-jobs`, { cid }, { headers })
            .then((res) => {
                setCjobs(res.data.jobs);
            });
    };

    const getServices = (sel) => {
        axios
            .post(`/api/admin/order-jobs`, { id: sel }, { headers })
            .then((res) => {
                let _service = res.data.data.jobservice;
                let lng = res.data.data.client.lng;

                if (_service) {
                    setjService(_service);
                    setTimeout(() => {
                        let st = 0;
                        let d = Moment(res.data.data.start_date).format(
                            "DD MMM, Y"
                        );

                        setFormValues({
                            description:
                                lng == "heb"
                                    ? _service.heb_name + " - " + d
                                    : _service.name + " - " + d,
                            unitprice: _service.total,
                            quantity: 1,
                        });
                    }, 200);
                }
            });
    };

    const curr = (_value) => {
        return _value.toLocaleString("en-US", {
            style: "currency",
            currency: "ILS",
        });
    };

    const cData =
        clients &&
        clients.map((c, i) => {
            return { value: c.id, label: c.firstname + " " + c.lastname };
        });

    const handleChangeClient = (_clientID) => {
        if (_clientID) {
            axios
                .get(`/api/admin/clients/${_clientID}`, { headers })
                .then((res) => {
                    setLng(res.data.client.lng);
                });

            getJobs(_clientID);
            setSelectedClientID(_clientID);
        }
    };

    const handleChangeJob = (_jobID) => {
        if (_jobID) {
            setSelectedJobID(_jobID);
            getServices(_jobID);
        }
    };

    useEffect(() => {
        setSelectedJobID(jobID);
        setSelectedClientID(clientID);
        getCustomers();
    }, []);

    const totalAmount = useMemo(() => {
        return formValues.unitprice * formValues.quantity;
    }, [formValues.unitprice, formValues.quantity]);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (lng == undefined) {
            alert.error("Client language is not set!");
            return;
        }

        if (selectedClientID == null) {
            alert.error("Please select client");
            return;
        }

        if (selectedJobID == null) {
            alert.error("Please select job");
            return;
        }

        setLoading(true);
        axios
            .post(
                `/api/admin/create-order`,
                {
                    client_id: selectedClientID,
                    job_id: selectedJobID,
                    services: [formValues],
                },
                { headers }
            )
            .then((res) => {
                setLoading(false);
                alert.success("Order created successfully");
                setTimeout(() => {
                    navigate("/admin/orders");
                }, 1000);
            })
            .catch((e) => {
                setLoading(false);
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title addEmployer">Create Order</h1>
                    <div className="card card-body">
                        <form>
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Customer
                                        </label>
                                        <SelectPicker
                                            data={cData}
                                            defaultValue={parseInt(clientID)}
                                            onChange={(value, event) => {
                                                handleChangeClient(value);
                                            }}
                                            size="lg"
                                            required
                                        />
                                    </div>

                                    <div className="form-group">
                                        <label className="control-label">
                                            Job
                                        </label>
                                        <select
                                            className="form-control"
                                            onChange={(e) => {
                                                handleChangeJob(e.target.value);
                                            }}
                                            value={jobID}
                                        >
                                            <option value={0}>
                                                --- Select Job ---
                                            </option>
                                            {cjobs &&
                                                cjobs.map((j, i) => {
                                                    return (
                                                        <option
                                                            value={j.id}
                                                            key={i}
                                                        >
                                                            {" "}
                                                            {j.start_date +
                                                                " | " +
                                                                j.shifts +
                                                                " | " +
                                                                j.service_name}
                                                        </option>
                                                    );
                                                })}
                                        </select>
                                    </div>
                                </div>

                                {jservice && (
                                    <div
                                        className="row col-sm-12"
                                        style={{ margin: "3px" }}
                                    >
                                        <div className="">
                                            <span className="hpoint">
                                                &#9755;
                                            </span>
                                        </div>

                                        <div className="col-sm-3">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Details
                                                </label>
                                                <input
                                                    type="text"
                                                    name="details"
                                                    value={
                                                        formValues.description
                                                    }
                                                    className={`form-control details`}
                                                    placeholder="Service"
                                                    required
                                                    readOnly
                                                />
                                            </div>
                                        </div>

                                        <div className="col-sm-2">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Unit Price
                                                </label>
                                                <input
                                                    type="number"
                                                    name="unitprice"
                                                    value={formValues.unitprice}
                                                    onChange={(e) =>
                                                        setFormValues({
                                                            ...formValues,
                                                            unitprice:
                                                                e.target.value,
                                                        })
                                                    }
                                                    className={`form-control price`}
                                                    placeholder="Unit Price"
                                                    required
                                                />
                                            </div>
                                        </div>

                                        <div className="col-sm-3">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Quantity
                                                </label>
                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    value={formValues.quantity}
                                                    className={`form-control quantity`}
                                                    placeholder="quantity"
                                                    readOnly
                                                />
                                            </div>
                                        </div>

                                        <div className="col-sm-3">
                                            <div className="form-group">
                                                <label className="control-label">
                                                    Total
                                                </label>
                                                <input
                                                    type="number"
                                                    value={totalAmount}
                                                    className={`form-control`}
                                                    placeholder="Total"
                                                    readOnly
                                                />
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {totalAmount != 0 && (
                                    <div className="col-sm-12">
                                        <div className="form-group text-center">
                                            <h5>
                                                Total Amount :{" "}
                                                <span className="total">
                                                    {curr(totalAmount)}
                                                </span>
                                            </h5>
                                        </div>
                                    </div>
                                )}

                                <div className="form-group text-center col-sm-12">
                                    <input
                                        type="submit"
                                        value="Generate Document"
                                        onClick={handleSubmit}
                                        className="btn btn-success saveBtn"
                                        disabled={totalAmount == 0 || loading}
                                    />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
