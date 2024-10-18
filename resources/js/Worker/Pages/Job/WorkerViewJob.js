import React, { useState, useEffect, useRef, useMemo } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import Swal from "sweetalert2";

import WorkerSidebar from "../../Layouts/WorkerSidebar";
import ClientDetails from "../../Components/Job/ClientDetails";
import Services from "../../Components/Job/Services";
import Comment from "../../Components/Job/Comment";
import ChangeJobStatusModal from "../../Components/Modals/ChangeJobStatusModal";

export default function WorkerViewJob() {
    const params = useParams();
    const navigate = useNavigate();
    const [job, setJob] = useState([]);
    const [job_status, setJobStatus] = useState("completed");
    const [client, setClient] = useState([]);
    const [worker, setWorker] = useState([]);
    const [counter, setCounter] = useState("00:00:00");
    const [isRunning, setIsRunning] = useState(false);
    const [startTime, setStartTime] = useState(new Date());
    const [time_id, setTimeId] = useState(null);
    const [job_time, setJobTime] = useState([]);
    const [total_time, setTotalTime] = useState(0);
    const [address, setAddress] = useState({});
    const [allComment, setAllComment] = useState([]);
    const [isOpenChangeJobStatus, setIsOpenChangeJobStatus] = useState(false);
    const [isApproving, setIsApproving] = useState(false);
    // const [isCompleteBtnDisable, setIsCompleteBtnDisable] = useState(false);
    const [isButtonEnabled, setIsButtonEnabled] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [targetLanguage, setTargetLanguage] = useState('en');
    const [jobId, setJobId] = useState(null)
    const [commentId, setCommentId] = useState(null)
    const [speakModal, setSpeakModal] = useState(false)
    const [problem, setProblem] = useState("")
    const [clientID, setClientID] = useState(null)
    const [workerID, setWorkerID] = useState(null)
    const [skippedComments, setSkippedComments] = useState([])


    const alert = useAlert();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const getJob = () => {
        axios
            .get(`/api/jobs/${params.id}`, { headers })
            .then((res) => {
                const r = res.data.job;
                setJob(r);
                setJobStatus(r.status);
                setClient(r.client);
                setWorker(r.worker);
                setAddress(r.property_address ? r.property_address : null);
                handleStartTime(res.data.job.start_date);
                setClientID(r.client.id)
                setWorkerID(r.worker_id)
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

    const handleMarkComplete = () => {
        console.log(isCompleteBtnDisable);

        if (isCompleteBtnDisable !== false) {
            Swal.fire({
                title: "Error!",
                text:  "You can't mark a job as complete if it's not finished.",
                icon: "error",
            });
        }else{
            isRunning ? stopTimer() : "";
            // setIsCompleteBtnDisable(true);
            setIsOpenChangeJobStatus(true);
        }
    };

    const handleApproveJob = (e) => {
        e.preventDefault();

        setIsApproving(true);
        axios
            .post(
                `/api/worker/${worker.id}/jobs/${params.id}/approve`,
                {},
                { headers }
            )
            .then((res) => {
                getJob();
                alert.success(res.data.data);
                setIsApproving(false);
            })
            .catch((e) => {
                setIsApproving(false);
                alert.error(e.response.data.message);
            });
    };

    const handleOpeningTime = (e) => {
        e.preventDefault();
        e.target.setAttribute("disabled", true);
        let data = {
            job_id: params.id,
        };
        axios
            .post(`/api/job-opening-timestamp`, data, { headers })
            .then((res) => {
                getJob();
                alert.success(res.data.message);
            })
            .catch((err) => {
                alert.success(res.data.message);
            });
        e.target.setAttribute("disabled", false);
    };
    const getDateTime = () => {
        var now = new Date();
        var year = now.getFullYear();
        var month = now.getMonth() + 1;
        var day = now.getDate();
        var hour = now.getHours();
        var minute = now.getMinutes();
        var second = now.getSeconds();
        if (month.toString().length == 1) {
            month = "0" + month;
        }
        if (day.toString().length == 1) {
            day = "0" + day;
        }
        if (hour.toString().length == 1) {
            hour = "0" + hour;
        }
        if (minute.toString().length == 1) {
            minute = "0" + minute;
        }
        if (second.toString().length == 1) {
            second = "0" + second;
        }
        var dateTime =
            year +
            "-" +
            month +
            "-" +
            day +
            " " +
            hour +
            ":" +
            minute +
            ":" +
            second;
        return dateTime;
    };
    const startTimer = () => {
        setIsSubmitting(true);
        setCounter("00:00:00");
        setIsRunning(true);
        axios
            .post(`/api/jobs/${params.id}/start-time`, {}, { headers })
            .then((res) => {
                getTime();
                setTimeout(() => {
                    setIsSubmitting(false);
                }, 500);
            });
    };
    const stopTimer = () => {
        setIsSubmitting(true);
        setIsRunning(false);
        setStartTime(getDateTime());

        axios
            .post(`/api/jobs/${params.id}/end-time`, {}, { headers })
            .then((res) => {
                getTimes();
                setTimeout(() => {
                    setIsSubmitting(false);
                }, 500);
            });
    };
    const getTime = () => {
        let data = {
            job_id: params.id,
            filter_end_time: true,
        };
        axios.post(`/api/get-job-time`, data, { headers }).then((res) => {
            let t = res.data.time;
            if (t) {
                if (Object.keys(t).length) {
                    setTimeId(t.id);
                    setStartTime(t.start_time);
                    setIsRunning(true);
                }
            }
        });
    };
    const getTimes = () => {
        let data = {
            job_id: params.id,
        };
        axios.post(`/api/get-job-time`, data, { headers }).then((res) => {
            let t = res.data;
            setJobTime(t.time);
            setTotalTime(parseInt(t.total));
        });
    };

    useEffect(() => {
        const interval = setInterval(() => {
            let timeDiff =
                (new Date().getTime() - new Date(startTime).getTime()) / 1000;
            timeDiff = timeDiff + total_time;
            let hours = Math.floor(timeDiff / 3600);
            let minutes = Math.floor((timeDiff % 3600) / 60);
            let seconds = Math.floor(timeDiff % 60);
            hours = hours < 10 ? "0" + hours : hours;
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;
            setCounter(`${hours}h:${minutes}m:${seconds}s`);
        }, 1000);
        return () => clearInterval(interval);
    }, [startTime]);

    let time_difference = (start, end) => {
        const timeDiff =
            (new Date(end).getTime() - new Date(start).getTime()) / 1000;
        return calculateTime(timeDiff);
    };
    let calculateTime = (timeDiff) => {
        let hours = Math.floor(timeDiff / 3600);
        let minutes = Math.floor((timeDiff % 3600) / 60);
        let seconds = Math.floor(timeDiff % 60);
        hours = hours < 10 ? "0" + hours : hours;
        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;
        return `${hours}h:${minutes}m:${seconds}s`;
    };


    const getComments = () => {
        axios
            .get(`/api/jobs/${params.id}/comments`, {
                headers,
                params: {
                    id: jobId,
                    comment_id: commentId,
                    target_language: targetLanguage
                },
            })
            .then((res) => {
                setAllComment(res.data.comments);
            })
            .catch((error) => {
                console.error("Error fetching comments: ", error);
            });
    };

    const handleStartTime = (start_date) => {
        const today = new Date();
        const startDate = new Date(start_date);
        if (startDate > today) {
            setIsButtonEnabled(false);
        } else {
            setIsButtonEnabled(true);
        }
    };

    const handleSpeakToManager = async (e) => {
        e.preventDefault();

        const data = {
            job_id: params.id,
            client_id: clientID,
            worker_id: workerID,
            problem: problem
        };

        try {
            const res = await axios.post(`/api/client/jobs/speak-to-manager`, data, { headers });
            alert.success(res?.data?.message)
            setProblem("")
            setSpeakModal(false)
        } catch (error) {
            if (error.response) {
                console.error("Error response: ", error.response.data);
            } else if (error.request) {
                console.error("No response received: ", error.request);
            } else {
                console.error("Error in request setup: ", error.message);
            }
        }
    };

    const handleGetSkippedComments = async () => {
        try {
            const response = await axios.get(`/api/job-comments/skipped-comments`, { headers });
            setSkippedComments(response?.data)

        } catch (error) {
            console.log(error);

        }
    }
    useEffect(() => {
        handleGetSkippedComments()
    }, [])


    useEffect(() => {
        getTimes();
        getTime();
        getComments();
    }, [targetLanguage]);

    const isCompleteBtnDisable = useMemo(() => {
        if (allComment.length === 3) {
            // Filter out comments with status "approved"
            const relevantComments = allComment.filter(c => c.status !== "approved");
            console.log(relevantComments,"approved");


            // If any of the relevant comments have a null status, disable the button
            const hasNullStatus = relevantComments.some(c => c.status === null);
            console.log(hasNullStatus,"null");

            // If all the relevant comments have "completed" status, enable the button
            const allCompleted = relevantComments.every(c => c.status === "complete");
            console.log(allCompleted,"comple");

            // Disable the button if any status is null or if not all are completed
            return hasNullStatus || !allCompleted;
        }

        // If the condition for 3 comments isn't met, keep it disabled
        return true;
    }, [allComment]);


    return (
        <div id="container">
            <WorkerSidebar />
            <div id="content">
                <div className="view-applicant">
                    <div className="worker-profile worker-view-job">
                        {job && (
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="row mb-3 mt-4 gap-2">
                                        <div className="col-sm-6 col-12">
                                            <h2 className="text-custom">
                                                {t(
                                                    "worker.jobs.view.c_details"
                                                )}
                                            </h2>
                                        </div>

                                        {job.job_opening_timestamp === null &&
                                            job.worker_approved_at === null ? (
                                            <div className="d-flex" style={{ gap: "10px" }}>
                                                <button
                                                    type="button"
                                                    onClick={handleApproveJob}
                                                    disabled={isApproving}
                                                    className="btn btn-primary"
                                                >
                                                    {t(
                                                        "worker.jobs.view.approve"
                                                    )}
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() => setSpeakModal(prev => !prev)}
                                                    // disabled={isApproving}
                                                    className="btn btn-primary"
                                                >
                                                    Speak to manager
                                                </button>
                                            </div>
                                        ) : job.job_opening_timestamp ===
                                            null &&
                                            job.worker_approved_at !== null ? (
                                            <div className="col-sm-3 col-xl-2 col-6">
                                                <button
                                                    type="button"
                                                    onClick={handleOpeningTime}
                                                    className="btn btn-success"
                                                    disabled={!isButtonEnabled}
                                                >
                                                    {t(
                                                        "worker.jobs.view.going_to_start"
                                                    )}
                                                </button>
                                            </div>
                                        ) : (
                                            <>
                                                <div className="col-sm-3 col-xl-2 col-6">
                                                    {job_status !=
                                                        "completed" &&
                                                        job_status !=
                                                        "cancel" && (
                                                            <button
                                                                type="button"
                                                                onClick={
                                                                    handleMarkComplete
                                                                }
                                                                // disabled={
                                                                //     isCompleteBtnDisable
                                                                // }
                                                                className="btn btn-success"
                                                            >
                                                                {t(
                                                                    "worker.jobs.view.completebtn"
                                                                )}
                                                            </button>
                                                        )}
                                                </div>
                                                {job_status != "completed" &&
                                                    job_status != "cancel" ? (
                                                    <div className="col-sm-2 col-6">
                                                        {!isRunning && (
                                                            <>
                                                                <button
                                                                    disabled={
                                                                        isSubmitting
                                                                    }
                                                                    onClick={
                                                                        startTimer
                                                                    }
                                                                    className="btn btn-primary"
                                                                >
                                                                    {job_time.length >
                                                                        0
                                                                        ? t(
                                                                            "worker.jobs.view.resbtn"
                                                                        )
                                                                        : t(
                                                                            "worker.jobs.view.startbtn"
                                                                        )}
                                                                </button>
                                                                <h4>
                                                                    {job_time.length >
                                                                        0
                                                                        ? calculateTime(
                                                                            total_time
                                                                        )
                                                                        : ""}
                                                                </h4>
                                                            </>
                                                        )}
                                                        {isRunning && (
                                                            <>
                                                                <button
                                                                    disabled={
                                                                        isSubmitting
                                                                    }
                                                                    onClick={
                                                                        stopTimer
                                                                    }
                                                                    className="btn btn-danger dangerous"
                                                                >
                                                                    {t(
                                                                        "worker.jobs.view.stopbtn"
                                                                    )}
                                                                </button>
                                                                <h4>
                                                                    {counter}
                                                                </h4>
                                                            </>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <div className="col-sm-2">
                                                        {t(
                                                            "worker.jobs.view.job_status"
                                                        )}{" "}
                                                        :{" "}
                                                        <h6 className="text-custom">
                                                            {job.status}
                                                        </h6>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </div>

                                    <ClientDetails
                                        client={client}
                                        address={address}
                                    />
                                </div>
                                <div className="col-sm-12">
                                    <Services job={job} />
                                </div>
                                <div className="col-sm-12">
                                    <h2 className="text-custom">
                                        {t("worker.jobs.view.w_time")}
                                    </h2>
                                    <div className="dashBox p-0 p-md-4 mb-4">
                                        <div className="table-responsive">
                                            {job_time.length > 0 ? (
                                                <Table className="table table-bordered responsiveTable">
                                                    <Thead>
                                                        <Tr>
                                                            <Th scope="col">
                                                                {t(
                                                                    "worker.jobs.view.start_time"
                                                                )}
                                                            </Th>
                                                            <Th scope="col">
                                                                {t(
                                                                    "worker.jobs.view.end_time"
                                                                )}
                                                            </Th>
                                                            <Th scope="col">
                                                                {t(
                                                                    "worker.jobs.view.time"
                                                                )}
                                                            </Th>
                                                        </Tr>
                                                    </Thead>
                                                    <Tbody>
                                                        {job_time.map(
                                                            (item, index) => {
                                                                let w_t =
                                                                    item.end_time
                                                                        ? time_difference(
                                                                            item.start_time,
                                                                            item.end_time
                                                                        )
                                                                        : "";
                                                                return (
                                                                    <Tr
                                                                        key={
                                                                            index
                                                                        }
                                                                    >
                                                                        <Td>
                                                                            {
                                                                                item.start_time
                                                                            }
                                                                        </Td>
                                                                        <Td>
                                                                            {
                                                                                item.end_time
                                                                            }
                                                                        </Td>
                                                                        <Td>
                                                                            {
                                                                                w_t
                                                                            }
                                                                        </Td>
                                                                    </Tr>
                                                                );
                                                            }
                                                        )}
                                                        <Tr>
                                                            <Td colSpan="2">
                                                                {t(
                                                                    "worker.jobs.view.total_time"
                                                                )}
                                                            </Td>
                                                            <Td>
                                                                {calculateTime(
                                                                    total_time
                                                                )}
                                                            </Td>
                                                        </Tr>
                                                    </Tbody>
                                                </Table>
                                            ) : (
                                                <p className="text-center mt-5">
                                                    {t(
                                                        "worker.jobs.view.timing_not"
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                    </div>


                                    <div className="">
                                        <Comment
                                            allComment={allComment}
                                            setAllComment={setAllComment}
                                            handleGetComments={getComments}
                                            setTargetLanguage={setTargetLanguage}
                                            setJobId={setJobId}
                                            setCommentId={setCommentId}
                                            handleGetSkippedComments={handleGetSkippedComments}
                                            setSkippedComments={setSkippedComments}
                                            skippedComments={skippedComments}
                                        />
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                </div>
            </div>
            {
                speakModal && (
                    <Modal
                    size="md"
                    className="modal-container"
                    show={speakModal}
                    onHide={() => setSpeakModal(false)}
                    backdrop="static"
                >
                    <Modal.Header closeButton>
                        <Modal.Title>Speak to Manager</Modal.Title>
                    </Modal.Header>

                    <Modal.Body>
                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">{t("worker.jobs.view.cmt")}</label>

                                    <textarea
                                        type="text"
                                        value={problem}
                                        onChange={(e) => setProblem(e.target.value)}
                                        className="form-control"
                                        required
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </Modal.Body>

                    <Modal.Footer>
                        <Button
                            type="button"
                            className="btn btn-secondary"
                            onClick={() => setSpeakModal(false)}
                        >
                            {t("modal.close")}
                        </Button>
                        <Button
                            type="button"
                            onClick={handleSpeakToManager}
                            className="btn btn-primary"
                        >
                            {t("global.send")}
                        </Button>
                    </Modal.Footer>
                </Modal>
                )
            }


            {isOpenChangeJobStatus && (
                <ChangeJobStatusModal
                    allComment={allComment}
                    skippedComments={skippedComments}
                    jobId={params.id}
                    jobStatus={job_status}
                    setIsOpen={setIsOpenChangeJobStatus}
                    isOpen={isOpenChangeJobStatus}
                    handleGetComments={getComments}
                    setTargetLanguage={setTargetLanguage}
                    setJobId={setJobId}
                    setCommentId={setCommentId}
                    onSuccess={() => {
                        getComments();
                        getJob();
                        setIsOpenChangeJobStatus(false);
                    }}
                />
            )}
        </div>
    );
}


