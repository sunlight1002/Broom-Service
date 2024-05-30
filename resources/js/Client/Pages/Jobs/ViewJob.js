import React, { useState, useEffect, useTransition } from "react";
import { useParams, useNavigate } from "react-router-dom";
import moment from "moment-timezone";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";
import { Base64 } from "js-base64";
import Sidebar from "../../Layouts/ClientSidebar";
import WorkerDetails from "../../Component/Job/WorkerDetails";
import Services from "../../Component/Job/Services";
import Comment from "../../Component/Job/Comment";
import CancelJobModal from "../../Component/Modals/CancelJobModal";

export default function ViewJob() {
    const params = useParams();
    const navigate = useNavigate();
    const [job, setJob] = useState([]);
    const [job_status, setJobStatus] = useState("completed");
    const [worker, setWorker] = useState([]);
    const [total, setTotal] = useState(0);
    const [isOpenCancelModal, setIsOpenCancelModal] = useState(false);

    const { t } = useTranslation();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const getJob = () => {
        axios
            .get(`/api/client/jobs/${Base64.decode(params.id)}`, { headers })
            .then((res) => {
                const r = res.data.job;
                if (r) {
                    setJob(r);
                    setJobStatus(r.status);
                    setWorker(r.worker);
                    setTotal(r.jobservice ? r.jobservice.total : 0);
                }
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

    const handleClick = () => {
        navigate(`/client/jobs`);
    };

    const handleCancel = () => {
        setIsOpenCancelModal(true);
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="view-applicant client-view-job">
                    <div className="worker-profile mt-4">
                        <div className="row">
                            <div className="col-sm-12">
                                <div className="row">
                                    <div className="col-lg-8 ">
                                        <h2 className="text-custom">
                                            {t(
                                                "client.jobs.view.worker_details"
                                            )}
                                        </h2>
                                        <WorkerDetails worker={worker} />
                                    </div>
                                    <div className="col-lg-2 col-6 text-right">
                                        {t("client.jobs.view.job_status")} :{" "}
                                        <h6
                                            className="text-custom"
                                            style={{
                                                textTransform: "capitalize",
                                            }}
                                        >
                                            {job.status}
                                        </h6>
                                    </div>
                                    <div className="col-lg-2 col-6">
                                        {job_status != "completed" &&
                                            job_status != "cancel" && (
                                                <button
                                                    type="button"
                                                    onClick={handleCancel}
                                                    className="btn btn-danger dangerous"
                                                >
                                                    {t(
                                                        "client.jobs.view.cancel"
                                                    )}
                                                </button>
                                            )}
                                    </div>
                                </div>
                            </div>
                            <div className="col-sm-12">
                                <Services job={job} />
                                <Comment />
                            </div>
                            <div className="col-sm-12 text-center">
                                <button
                                    type="button"
                                    onClick={handleClick}
                                    className="btn btn-pink addButton"
                                >
                                    {t("client.jobs.view.back")}
                                </button>
                            </div>
                        </div>
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
