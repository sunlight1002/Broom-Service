import React, { useEffect, useState } from 'react';
import Sidebar from "../../Layouts/WorkerSidebar";
import axios from 'axios';
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";

function Protocol() {
    const { t, i18n } = useTranslation();
    const [protocolFile, setProtocolFile] = useState(null);
    const [decisionFile, setDecisionFile] = useState(null);
    const [error, setError] = useState(null);
    const [comment, setComment] = useState("");
    const [messages, setMessages] = useState([]);
    const [fetchedComment, setFetchedComment] = useState(null);                           
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
            });
    }, [workerId]);

    useEffect(() => {
        if (!workerId) return;
    
        axios.get(`/api/worker-claim?worker_id=${workerId}`, { headers })
            .then(response => {
                const claim = response.data.claim_description;
                if (claim && claim.trim() !== "") {
                    setClaimText(claim);
                    setShowClaim(true);
                    setHasClaim(true);
                } else {
                    setHasClaim(false);
                    setShowClaim(false);
                }
            })
            .catch(() => {
                setHasClaim(false);
                setShowClaim(false);
            });
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

    useEffect(() => {
        if (workerId) {
            axios.get(`/api/hearing_protocol/comments?worker_id=${workerId}`, { headers })
                .then(response => {
                    if (response.data && response.data.comment) {
                        setFetchedComment(response.data.comment);
                        setMessages(prev => [
                            ...prev,
                            {
                                type: 'comment',
                                content: response.data.comment,
                            }
                        ]);
                    }
                })
                .catch(error => {
                    console.error('Error fetching comments:', error);
                    setError('Failed to load comments.');
                });
        }
    }, [workerId]);

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
               
                <div className="d-flex align-items-center mb-3">
                    {protocolFile && (
                        <a 
                            href={protocolFile} 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            className="btn px-4 mr-3"
                            style={{ textDecoration: "none", background: "#2F4054", color: "white"}}
                        >
                            {t("worker.hearing.protocol.viewProtocolDocument")}
                        </a>
                    )}

                    {decisionFile && (
                        <a 
                            href={decisionFile} 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            className="btn px-3 mr-3"
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
                    <div>
                        { hasClaim && (
                            fetchedComment ? (
                                <div className="alert alert-secondary" style={{ whiteSpace: "pre-wrap", background: "white" }}>
                                    <strong>Submitted Comment : &nbsp;</strong>
                                    {fetchedComment}
                                </div>
                            ) : (
                                <>
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
                                </>
                            )
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Protocol;
