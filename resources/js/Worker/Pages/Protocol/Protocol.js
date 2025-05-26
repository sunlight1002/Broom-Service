import React, { useEffect, useState } from 'react';
import Sidebar from "../../Layouts/WorkerSidebar";
import axios from 'axios';
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";

function Protocol() {
    const { t, i18n } = useTranslation();
    const [protocolFile, setProtocolFile] = useState(null);
    const [DecisionFile, setDecisionFile] = useState(null);
    const [error, setError] = useState(null);
    const [comment, setComment] = useState("");
    const [commentError, setCommentError] = useState("");
    const [claimText, setClaimText] = useState(null);
    const [showClaim, setShowClaim] = useState(false);
    const [hasClaim, setHasClaim] = useState(false);

    const workerId = localStorage.getItem("worker-id");
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    useEffect(() => {
        if (!workerId) {
            alert.error('Worker ID not found in local storage.');
            return;
        }

        axios.get(`/api/protocol?worker_id=${workerId}`, { headers })
            .then(response => {
                setProtocolFile(response.data.file);
            })
            .catch(error => {
                alert.error('Failed to load protocol document.');
            });
    }, [workerId]);


    useEffect(() => {
        if (!workerId) {
            alert.error('Worker ID not found in local storage.');
            return;
        }

        axios.get(`/api/decision_document?worker_id=${workerId}`, { headers })
            .then(response => {
                setDecisionFile(response.data.file);
            })
            .catch(error => {
                alert.error('Failed to load protocol document.');
            });
    }, [workerId]);

    useEffect(() => {
        if (!workerId) return;
    
        axios.get(`/api/worker-claim?worker_id=${workerId}`, { headers })
            .then(response => {
                const claim = response.data.claim_description;
                if (claim && claim.trim() !== "") {
                    setHasClaim(true);
                } else {
                    setHasClaim(false);
                }
            })
            .catch(() => setHasClaim(false));
    }, [workerId]);
    

    const handleCommentChange = (event) => {
        setComment(event.target.value);
    };

    const handleSubmitComment = () => {
        if (!comment.trim()) {
            alert.error("Please add comment");
            return;
        }
    
        setCommentError("");
    
        axios.post('/api/comments', { worker_id: workerId, comment: comment }, { headers })
            .then(response => {
                setComment("");
                alert.success("Comment added successfully");
            })
            .catch(error => {
                alert.error("Failed to submit comment.");
            });
    };    

    const handleFetchClaim = () => {
        if (!workerId) {
            alert.error("Worker ID not found.");
            return;
        }
    
        axios.get(`/api/worker-claim?worker_id=${workerId}`, { headers })
            .then(response => {
                const claim = response.data.claim_description;
                setClaimText(claim);
                setShowClaim(true);
                setHasClaim(true)
            })
            .catch(error => {
                alert.error("Failed to load claim description.");
                setHasClaim(false);
            });
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("worker.hearing.protocol.protocolDocument")}</h1>
                        </div>
                    </div>
                </div>
               
                <div className="d-flex gap-2 mb-3">
                    {hasClaim && (
                        <button className="btn navyblue" onClick={handleFetchClaim}>
                            {t("worker.hearing.protocol.viewClaim")}
                        </button>
                    )}

                    {protocolFile && (
                        <a 
                            href={protocolFile} 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            className="btn ml-2"
                            style={{ textDecoration: "none", background: "#2F4054", color: "white"}}
                        >
                            {t("worker.hearing.protocol.viewProtocolDocument")}
                        </a>
                    )}

                    { DecisionFile && (
                        <a 
                            href={DecisionFile} 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            className="btn ml-2"
                            style={{ textDecoration: "none", background: "#2F4054", color: "white"}}
                        >
                            {t("worker.hearing.protocol.viewDecisionDocument")}
                        </a>
                    )}

                </div>

                {showClaim && (
                    <div className="alert alert-secondary" style={{ whiteSpace: "pre-wrap", background: "white" }}>
                        {claimText || "No claim description found."}
                    </div>
                )}

                <div className="card" style={{ boxShadow: "none" }}>
                    {/* Comment Section */}
                    <div>
                        <textarea 
                            value={comment} 
                            onChange={handleCommentChange} 
                            placeholder="Enter your comment..." 
                            rows="4" 
                            className="form-control mb-3"
                        />
                        {commentError && <p className="text-danger">{commentError}</p>}
                        <button 
                            onClick={handleSubmitComment} 
                            className="btn navyblue"
                        >
                            {t("worker.hearing.protocol.submitComment")}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Protocol;
