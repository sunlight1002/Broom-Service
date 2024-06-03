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

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getJob = () => {
        axios
            .get(`/api/admin/contract/${params.id}`, { headers })
            .then((res) => {
                const r = res.data.contract;
                setClient(r.client);
                let _services = JSON.parse(r.offer.services);
                _services = _services.map((n) => {
                    n["contract_id"] = parseInt(params.id);
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
                    <div className="card">
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
                                        <div className="col-sm-4 col-lg-2">
                                            <div className="form-group">
                                                <label>Services</label>
                                                {services.map((item, index) => {
                                                    return (
                                                        <p
                                                            className={`services-${item.service}-${item.contract_id}`}
                                                            key={index}
                                                        >
                                                            {item.service ==
                                                            "10"
                                                                ? item.other_title
                                                                : item.name}
                                                        </p>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                        <div className="col-sm-4 col-lg-2">
                                            <div className="form-group">
                                                <label>Frequency</label>
                                                {services.map((item, index) => (
                                                    <p
                                                        className={`services-${item.service}-${item.contract_id}`}
                                                        key={index}
                                                    >
                                                        {item.freq_name}
                                                    </p>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="col-sm-4 col-lg-2">
                                            <div className="form-group">
                                                <label>Time to Complete</label>
                                                {services.map((item, index) => (
                                                    <div key={index}>
                                                        {item?.workers?.map(
                                                            (worker, i) => (
                                                                <p
                                                                    className={`services-${item.service}-${item.contract_id}`}
                                                                    key={i}
                                                                >
                                                                    {
                                                                        worker.jobHours
                                                                    }{" "}
                                                                    hours (Worker {i + 1})
                                                                </p>
                                                            )
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>Property</label>
                                                {services.map((item, index) => (
                                                    <p
                                                        className={`services-${item.service}-${item.contract_id}`}
                                                        key={index}
                                                    >
                                                        {
                                                            item?.address
                                                                ?.address_name
                                                        }
                                                    </p>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>Pet animals</label>
                                                {services.map((item, index) => (
                                                    <p
                                                        className={`services-${item.service}-${item.contract_id}`}
                                                        key={index}
                                                    >
                                                        {item?.address
                                                            ?.is_cat_avail
                                                            ? "Cat ,"
                                                            : item?.address
                                                                  ?.is_dog_avail
                                                            ? "Dog"
                                                            : !item?.address
                                                                  ?.is_cat_avail &&
                                                              !item?.address
                                                                  ?.is_dog_avail
                                                            ? "NA"
                                                            : ""}
                                                    </p>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="col-sm-4">
                                            <div className="form-group">
                                                <label>Gender preference</label>
                                                {services.map((item, index) => (
                                                    <p
                                                        className={`services-${item.service}-${item.contract_id}`}
                                                        key={index}
                                                        style={{
                                                            textTransform:
                                                                "capitalize",
                                                        }}
                                                    >
                                                        {
                                                            item?.address
                                                                ?.prefer_type
                                                        }
                                                    </p>
                                                ))}
                                            </div>
                                        </div>
                                        {/* <div className="col-sm-12">
                                            <div className="mt-3 mb-3">
                                                <h3 className="text-center">
                                                    Worker Availability
                                                </h3>
                                            </div>
                                        </div> */}
                                        <div className="col-sm-12">
                                            <CreateJobCalender
                                                services={services}
                                                client={client}
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
