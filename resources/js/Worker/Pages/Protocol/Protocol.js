import React, { useEffect, useState } from 'react';
import Sidebar from "../../Layouts/WorkerSidebar";
import axios from 'axios';
import { useAlert } from "react-alert";

function Protocol() {
    const [protocolFile, setProtocolFile] = useState(null);
    const [error, setError] = useState(null);
    const [comment, setComment] = useState("");
    const [commentError, setCommentError] = useState("");
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

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">Protocol Document</h1>
                        </div>
                    </div>
                </div>
                <div className="card" style={{ boxShadow: "none" }}>
                    <div className="card-body">
                        <div className="boxPanel">
                            {error ? (
                                <p>{error}</p>
                            ) : protocolFile ? (
                                <div>
                                    <a 
                                        href={protocolFile} 
                                        target="_blank" 
                                        rel="noopener noreferrer" 
                                        className="btn btn-primary mb-3"
                                    >
                                        View Claim
                                    </a>
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
                                            Submit Comment
                                        </button>
                                    </div>
                                </div>
                            ) : (
                                <p>Loading...</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Protocol;
