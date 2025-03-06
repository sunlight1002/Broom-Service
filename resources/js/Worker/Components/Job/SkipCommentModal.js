import React, { useState } from 'react'
import { Button, Modal } from "react-bootstrap";
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { useAlert } from 'react-alert';
import { useParams } from 'react-router-dom';

const SkipCommentModal = ({
    isOpen,
    setIsOpen,
    comment,
    handleGetSkippedComments,
    uuid = null
}) => {
    const [requestText, setRequestText] = useState('')
    const { t } = useTranslation();
    const alert = useAlert();

    const params = useParams()    

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };


    const handleSubmit = async () => {
        console.log(uuid);
        
        try {
            const formData = new FormData();
            formData.append('comment_id', comment.id);
            formData.append('request_text', requestText);

            let url = uuid ? `/api/job-comments/add-skip-comment` : `/api/job-comments/skip-comment`;

            const config = uuid ? {} : { headers };

            const response = await axios.post(url, formData, config);

            if (response.data.success) {
                alert.success('Skip request submitted successfully!');
                setIsOpen(false);
               await handleGetSkippedComments(); // Refresh the comments list
            }
        } catch (error) {
            console.error('Error submitting skip request', error);
            alert.error('Failed to submit skip request');
        }
    };

    return (
        <div>
            <Modal
                size="md"
                className="modal-container"
                show={isOpen}
                onHide={() => setIsOpen(false)}
                backdrop="static"
            >
                <Modal.Header closeButton>
                    <Modal.Title>Requested Speak to manager</Modal.Title>
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
                                    value={requestText}
                                    onChange={(e) => setRequestText(e.target.value)}
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
                        onClick={handleSubmit}
                        className="btn btn-primary"
                    >
                        Send
                    </Button>
                </Modal.Footer>
            </Modal>
        </div>
    )
}

export default SkipCommentModal;
