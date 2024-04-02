import React, { useState, useEffect } from "react";
import Sidebar from "../../Layouts/Sidebar";
import { SelectPicker } from "rsuite";
import "rsuite/dist/rsuite.min.css";
import axios from "axios";
import { useAlert } from "react-alert";
import { useNavigate, useParams } from "react-router-dom";
import Swal from "sweetalert2";

import TeamAvailability from "../../Components/Job/TeamAvailability";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";

export default function EditJob() {
    const alert = useAlert();
    const navigate = useNavigate();
    const params = useParams();
    const [service, setService] = useState(null);
    const [client, setClient] = useState("");
    const [address, setAddress] = useState("");

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getJob = () => {
        axios
            .get(`/api/admin/jobs/${params.id}`, { headers })
            .then((res) => {
                const r = res.data.job;
                setClient(r.client.firstname + " " + r.client.lastname);
                setAddress(r?.property_address?.address_name);
                setService(r.jobservice);
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };
    useEffect(() => {
        getJob();
    }, []);

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="edit-customer">
                    <h1 className="page-title editJob">Add Job</h1>
                    <div id="calendar"></div>
                    <div className="card">
                        <div className="card-body">
                            <form>
                                <div className="row">
                                    <div className="col-sm-2">
                                        <div className="form-group">
                                            <label>Client</label>
                                            <p>{client}</p>
                                        </div>
                                    </div>
                                    <div className="col-sm-2">
                                        <div className="form-group">
                                            <label>Services</label>

                                            <p>
                                                {service ? service.name : "NA"}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="col-sm-2">
                                        <div className="form-group">
                                            <label>Time to Complete</label>

                                            <p>
                                                {service
                                                    ? convertMinsToDecimalHrs(
                                                          service.duration_minutes
                                                      ) + " hours"
                                                    : "NA"}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="col-sm-4">
                                        <div className="form-group">
                                            <label>Address</label>
                                            <p>{address}</p>
                                        </div>
                                    </div>
                                    <div className="col-sm-12">
                                        <div className="mt-3 mb-3">
                                            <h3 className="text-center">
                                                Worker Availability
                                            </h3>
                                        </div>
                                    </div>
                                    <div className="col-sm-12">
                                        <TeamAvailability />
                                        <div className="mb-3">&nbsp;</div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
