import React, { useState, useEffect } from "react";
import "rsuite/dist/rsuite.min.css";
import axios from "axios";
import { useParams } from "react-router-dom";
import { FaPeopleGroup } from "react-icons/fa6";
import { BsBuildings } from "react-icons/bs";
import { PiSuitcaseBold } from "react-icons/pi";
import { RiTimerFlashLine } from "react-icons/ri";
import { GiSandsOfTime } from "react-icons/gi";
import { LiaPawSolid } from "react-icons/lia";
import { FaPeopleArrows } from "react-icons/fa";

import Sidebar from "../../Layouts/Sidebar";
import CreateJobCalender from "../../Components/Job/CreateJobCalender";
import FullPageLoader from "../../../Components/common/FullPageLoader";

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
                // console.log(res);
                
                const r = res.data.contract;
                setClient(r.client);
                let _services = JSON.parse(r.offer.services);
                _services = _services.map((n) => {
                    n["contract_id"] = parseInt(params.id);
                    return n;
                });
                setServices(_services);
                setLoading(false);
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
                    <h1 className="page-title editJob border-0">Add Job</h1>
                    <div id="calendar"></div>
                    <div className="card" style={{ boxShadow: "none" }}>
                        {client && (
                            <>
                                <div className="d-flex flex-wrap justify-content-between">
                                    <div className="dashBox d-flex mr-2 mt-2 h-100 jobcard" style={{ width: "207px" }}>
                                        <div className="dashIcon d-flex align-items-center">
                                            <i className=""><FaPeopleGroup className="font-30" style={{ color: "#1F78BD" }} /></i>
                                        </div>
                                        <div className="dashText ml-2">
                                            <p className="font-15 navyblueColor" style={{ fontWeight: "500" }}>{client.firstname + " " + client.lastname}</p>
                                            <label>Client</label>
                                        </div>
                                    </div>
                                    {services.length > 0 && selectedServiceIndex !== null && (
                                        <>
                                            <div className="dashBox d-flex mr-2 mt-2 h-100 jobcard" style={{ width: "207px" }}>
                                                <div className="dashIcon d-flex align-items-center">
                                                    <i className=""><BsBuildings className="font-30" style={{ color: "#1F78BD" }} /></i>
                                                </div>
                                                <div className="dashText ml-2">
                                                    <p className={`font-15 navyblueColor services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`} style={{ fontWeight: "500" }}>
                                                        {services[selectedServiceIndex]?.address?.address_name}
                                                    </p>
                                                    <label>Property</label>
                                                </div>
                                            </div>
                                            <div className="dashBox d-flex mr-2 mt-2 h-100 jobcard" style={{ width: "207px" }}>
                                                <div className="dashIcon d-flex align-items-center">
                                                    <i className=""><PiSuitcaseBold className="font-30" style={{ color: "#1F78BD" }} /></i>
                                                </div>
                                                <div className="dashText ml-2">
                                                    <p className={`font-15 navyblueColor services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`} style={{ fontWeight: "500" }}>
                                                        {services[selectedServiceIndex].service === "10"
                                                            ? services[selectedServiceIndex].other_title
                                                            : services[selectedServiceIndex].name}
                                                    </p>
                                                    <label>Services</label>
                                                </div>
                                            </div>
                                            <div className="dashBox d-flex mr-2 mt-2 h-100 jobcard" style={{ width: "207px" }}>
                                                <div className="dashIcon d-flex align-items-center">
                                                    <i className=""><RiTimerFlashLine className="font-30" style={{ color: "#1F78BD" }} /></i>
                                                </div>
                                                <div className="dashText ml-2">
                                                    <p className={`font-15 navyblueColor services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`} style={{ fontWeight: "500" }}>
                                                        {services[selectedServiceIndex].freq_name}
                                                    </p>
                                                    <label>Frequency</label>
                                                </div>
                                            </div>
                                            <div className="dashBox d-flex mr-2 mt-2 h-100 jobcard" style={{ width: "207px" }}>
                                                <div className="dashIcon d-flex align-items-center">
                                                    <i className=""><GiSandsOfTime className="font-30" style={{ color: "#1F78BD" }} /></i>
                                                </div>
                                                <div className="dashText ml-2">
                                                    {services[selectedServiceIndex]?.workers?.map((worker, i) => (
                                                        <p key={i} className={`font-15 navyblueColor services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`} style={{ fontWeight: "500" }}>
                                                            {worker.jobHours} hours (Worker {i + 1})
                                                        </p>
                                                    ))}
                                                    <label>Time to Complete</label>
                                                </div>
                                            </div>
                                            <div className="dashBox d-flex mr-2 mt-2 h-100 jobcard" style={{ width: "207px" }}>
                                                <div className="dashIcon d-flex align-items-center">
                                                    <i className=""><LiaPawSolid className="font-30" style={{ color: "#1F78BD" }} /></i>
                                                </div>
                                                <div className="dashText ml-2">
                                                    <p className={`font-15 navyblueColor services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`} style={{ fontWeight: "500" }}>
                                                        {services[selectedServiceIndex]?.address?.is_cat_avail
                                                            ? "Cat"
                                                            : services[selectedServiceIndex]?.address?.is_dog_avail
                                                                ? "Dog"
                                                                : "NA"}
                                                    </p>
                                                    <label>Pet animals</label>
                                                </div>
                                            </div>
                                            <div className="dashBox d-flex mr-2 mt-2 h-100 jobcard" style={{ width: "207px" }}>
                                                <div className="dashIcon d-flex align-items-center">
                                                    <i className=""><FaPeopleArrows className="font-30" style={{ color: "#1F78BD" }} /></i>
                                                </div>
                                                <div className="dashText ml-2">
                                                    <p className={`font-15 navyblueColor services-${services[selectedServiceIndex].service}-${services[selectedServiceIndex].contract_id}`} style={{ textTransform: "capitalize", fontWeight: "500" }}>
                                                        {services[selectedServiceIndex]?.address?.prefer_type}
                                                    </p>
                                                    <label>Gender preference</label>
                                                </div>
                                            </div>
                                        </>
                                    )}

                                </div>
                                <div className="card-body">
                                    <form>
                                        <div className="row">
                                            {/* <div className="col-sm-4 col-lg-2">
                                                <div className="form-group">
                                                    <label>Client</label>
                                                    <p>
                                                        {client.firstname +
                                                            " " +
                                                            client.lastname}
                                                    </p>
                                                </div>
                                            </div> */}

                                            {/* {services.length > 0 && selectedServiceIndex !== null && (
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
                                            )} */}

                                            <div className="col-sm-12">
                                                <CreateJobCalender
                                                    services={services}
                                                    client={client}
                                                    loading={loading}
                                                    setLoading={loading}
                                                    selectedService={selectedService}
                                                    setSelectedService={setSelectedService}
                                                    setSelectedServiceIndex={setSelectedServiceIndex}
                                                />
                                                <div className="mb-3">&nbsp;</div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}
