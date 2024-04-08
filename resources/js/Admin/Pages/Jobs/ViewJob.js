import React, { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import Swal from "sweetalert2";

import Sidebar from "../../Layouts/Sidebar";
import ClientDetails from "../../Components/Job/ClientDetails";
import WorkerDetails from "../../Components/Job/WorkerDetails";
import Services from "../../Components/Job/Services";
import Comment from "../../Components/Job/Comment";
import WorkerTiming from "../../Components/Job/WorkerTiming";
import CancelJobModal from "../../Components/Modals/CancelJobModal";

export default function ViewJob() {
    const params = useParams();
    const navigate = useNavigate();
    const [job, setJob] = useState([]);
    const [client, setClient] = useState([]);
    const [worker, setWorker] = useState([]);
    const [address, setAddress] = useState({});

    const [isOpenCancelModal, setIsOpenCancelModal] = useState(false);
    const handleCancel = () => {
        setIsOpenCancelModal(true);
    };

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
                setJob(r);
                setClient(r.client);
                setWorker(r.worker);
                setAddress(r.property_address ? r.property_address : {});
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
        setTimeout(() => {
            document.querySelector(".cdiv").style.display = "block";
        }, 1000);
    }, []);

    const handleClick = () => {
        navigate(`/admin/jobs`);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="view-applicant">
                    <div className="worker-profile">
                        {job && (
                            <div className="row">
                                <div className="col-sm-6">
                                    <ClientDetails
                                        client={client}
                                        address={address}
                                    />
                                </div>
                                <div className="col-sm-6">
                                    <div
                                        className="cdiv"
                                        style={{ display: "none" }}
                                    >
                                        {job.status != "completed" ? (
                                            job.status == "cancel" ? (
                                                <h4 className="text-danger float-right font-weight-bold mt-2">
                                                    {" "}
                                                    Cancelled{" "}
                                                </h4>
                                            ) : (
                                                <button
                                                    className="btn btn-danger float-right mt-2"
                                                    onClick={(e) =>
                                                        handleCancel(e)
                                                    }
                                                >
                                                    Cancel
                                                </button>
                                            )
                                        ) : (
                                            <h4 className="text-success float-right font-weight-bold mt-2">
                                                {" "}
                                                Completed{" "}
                                            </h4>
                                        )}
                                    </div>

                                    <WorkerDetails worker={worker} job={job} />
                                </div>
                                <div className="col-sm-12">
                                    <Services job={job} />
                                    <WorkerTiming job={job} />
                                    <Comment />
                                </div>
                                <div className="col-sm-12 text-center">
                                    <button
                                        type="button"
                                        onClick={handleClick}
                                        className="btn btn-pink addButton"
                                    >
                                        Back
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {isOpenCancelModal && (
                <CancelJobModal
                    setIsOpen={setIsOpenCancelModal}
                    isOpen={isOpenCancelModal}
                    job={job}
                />
            )}
        </div>
    );
}
