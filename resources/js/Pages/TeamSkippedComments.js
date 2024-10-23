import axios from "axios";
import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import Button from 'react-bootstrap/Button'; // Ensure you have the import for Button
import Modal from 'react-bootstrap/Modal'; // Ensure you have the import for Modal
import { useTranslation } from "react-i18next";
import { useParams } from "react-router-dom";
import logo from "../Assets/image/sample.svg";

export default function TeamSkippedComments() {
    const { t } = useTranslation();
    const alert = useAlert();
    const [clientID, setClientID] = useState(null)
    const [workerID, setWorkerID] = useState(null)
    const [skippedComments, setSkippedComments] = useState([])
    const [job, setJob] = useState([]);
    const [isOpen, setIsOpen] = useState(false)
    const [rejectedText, setRejectedText] = useState("")

    const params = useParams();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const handleGetSkippedComments = async () => {
        try {
            const response = await axios.get(`/api/job-comments/skipped-comments`, { headers });
            console.log(response);
            setSkippedComments(response?.data)
        } catch (error) {
            console.log(error);
        }
    }

    useEffect(() => {
        handleGetSkippedComments()
    }, [])

    const handleUpdateStatus = async (status, rejectionText = "") => {
        try {
            const formData = new FormData();
            formData.append('comment_id', params.id);
            formData.append('status', status);
            if (status === 'Rejected') {
                formData.append('response_text', rejectionText);
            }

            const response = await axios.post('/api/job-comments/update-status', formData, { headers });

            if (response.data.success) {
                if (status === 'Approved') {
                    alert.success('Approved Successfully');
                } else {
                    alert.success('Rejected Successfully');
                }
                setRejectedText('')
                setIsOpen(false)
            }
        } catch (error) {
            console.error('Error submitting skip request', error);
            alert.error('Failed to submit skip request');
        }
    };

    const handleOpenModal = () => {
        setIsOpen(true)
    }

    return (
        <div className="container meeting" style={{ display: "block" }}>
            <div className="thankyou meet-status dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                <div className="cta">
                    <div id="content">
                        <div className="titleBox customer-title">
                            <div className="row">
                                <div className="col-sm-6">
                                    <h1 className="page-title">Buttons:</h1>
                                </div>
                            </div>
                        </div>
                        {/* Display relevant details of the first skipped comment */}
                        {skippedComments.length > 0 && (
                            <div className="comment-details mb-3">
                                <p>Comment Details</p>
                                <p>Comment: {skippedComments[0].comment.comment}</p>
                                <p>Requested Text: {skippedComments[0].request_text}</p>
                                <p>Created At: {new Date(skippedComments[0].comment.created_at).toLocaleString()}</p>
                            </div>
                        )}
                        <div className="card">
                            <div className="card-body d-flex justify-content-around align-items-center flex-wrap">
                                <div className="ml-2">
                                    <button
                                        className="btn btn-pink addButton mt-2"
                                        style={{ textTransform: "none", width: "9rem" }}
                                        type="button"
                                        onClick={() => handleUpdateStatus('Approved')}
                                    >
                                        Approved
                                    </button>
                                </div>

                                <div className="ml-2">
                                    <button
                                        className="btn btn-pink addButton mt-2"
                                        style={{ textTransform: "none", width: "9rem" }}
                                        type="button"
                                        onClick={handleOpenModal}
                                    >
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <Modal
                size="md"
                className="modal-container"
                show={isOpen}
                onHide={() => setIsOpen(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Rejected:</Modal.Title>
                </Modal.Header>

                <Modal.Body>
                    <div className="row">
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">
                                    {t("worker.jobs.view.cmt")}
                                </label>
                                <textarea
                                    type="text"
                                    value={rejectedText}
                                    onChange={(e) => setRejectedText(e.target.value)}
                                    className="form-control"
                                    required
                                    placeholder={t("worker.jobs.view.cmt_box")}
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </Modal.Body>

                <Modal.Footer>
                    <Button
                        type="button"
                        className="btn btn-secondary"
                        onClick={() => setIsOpen(false)}
                    >
                        {t("worker.jobs.view.close")}
                    </Button>
                    <Button
                        type="button"
                        onClick={() => handleUpdateStatus("Rejected", rejectedText)} // Pass rejectedText here
                        className="btn btn-primary"
                    >
                        Send
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    );
}
