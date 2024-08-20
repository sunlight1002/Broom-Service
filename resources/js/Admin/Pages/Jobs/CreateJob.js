import React, { useState, useEffect } from "react";
import "rsuite/dist/rsuite.min.css";
import axios from "axios";
import { useParams } from "react-router-dom";

import Sidebar from "../../Layouts/Sidebar";
import CreateJobCalender from "../../Components/Job/CreateJobCalender";

export default function CreateJob() {
    const params = useParams();
    const [services, setServices] = useState([]);
    const [client, setClient] = useState(null);
    const [loading, setLoading] = useState(false);
    const [selectedService, setSelectedService] = useState(0);
    const [selectedServiceIndex, setSelectedServiceIndex] = useState(0);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getJob = () => {
        setLoading(true);
        axios
            .get(`/api/admin/contract/${params.id}`, { headers })
            .then((res) => {
                const r = res.data.contract;
                setClient(r.client);
                let _services = JSON.parse(r.offer.services);
                _services = _services.map((n) => {
                    n["contract_id"] = parseInt(params.id);
                    setLoading(false);
                    return n;
                });
                setServices(_services);
            });
    };

    useEffect(() => {
        getJob();
    }, []);

    useEffect(() => {
        if (services.length) {
            $("#edit-work-time").modal({
                backdrop: "static",
                keyboard: false,
            });
        }
    }, [services]);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="view-applicant">
                    <h1 className="page-title editJob">Add Job</h1>
                    <div id="calendar"></div>
                    <div className="card" style={{ boxShadow: "none" }}>
                        {client && (
                            <div className="card-body">
                                <form>
                                    <div className="row">
                                        <div className="col-sm-4 col-lg-2">
                                            <div className="form-group">
                                                <label>Client</label>
                                                <p>
                                                    {client.firstname +
                                                        " " +
                                                        client.lastname}
                                                </p>
                                            </div>
                                        </div>

                                        {services.length > 0 && selectedServiceIndex !== null && (
                                            <>
                                                <div className="col-sm-4 col-lg-2">
                                                    <div className="form-group">
                                                        <label>Services</label>
                                                        <p className={`services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`}>
                                                            {services[selectedServiceIndex].service === "10"
                                                                ? services[selectedServiceIndex].other_title
                                                                : services[selectedServiceIndex].name}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="col-sm-4 col-lg-2">
                                                    <div className="form-group">
                                                        <label>Frequency</label>
                                                        <p className={`services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`}>
                                                            {services[selectedServiceIndex].freq_name}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="col-sm-4 col-lg-2">
                                                    <div className="form-group">
                                                        <label>Time to Complete</label>
                                                        {services[selectedServiceIndex]?.workers?.map((worker, i) => (
                                                            <p key={i} className={`services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`}>
                                                                {worker.jobHours} hours (Worker {i + 1})
                                                            </p>
                                                        ))}
                                                    </div>
                                                </div>

                                                <div className="col-sm-4">
                                                    <div className="form-group">
                                                        <label>Property</label>
                                                        <p className={`services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`}>
                                                            {services[selectedServiceIndex]?.address?.address_name}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="col-sm-4">
                                                    <div className="form-group">
                                                        <label>Pet animals</label>
                                                        <p className={`services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`}>
                                                            {services[selectedServiceIndex]?.address?.is_cat_avail
                                                                ? "Cat"
                                                                : services[selectedServiceIndex]?.address?.is_dog_avail
                                                                ? "Dog"
                                                                : "NA"}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="col-sm-4">
                                                    <div className="form-group">
                                                        <label>Gender preference</label>
                                                        <p className={`services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`} style={{ textTransform: "capitalize" }}>
                                                            {services[selectedServiceIndex]?.address?.prefer_type}
                                                        </p>
                                                    </div>
                                                </div>
                                            </>
                                        )}

                                        <div className="col-sm-12">
                                            <CreateJobCalender
                                                services={services}
                                                client={client}
                                                loading={loading}
                                                setSelectedService={setSelectedService}
                                                setSelectedServiceIndex={setSelectedServiceIndex}
                                            />
                                            <div className="mb-3">&nbsp;</div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
