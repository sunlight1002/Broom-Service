import React, { useState, useEffect } from "react";
import { useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { Base64 } from "js-base64";
import Swal from "sweetalert2";
import { useNavigate } from "react-router-dom";
import logo from "../Assets/image/sample.svg";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";


export default function ContactManager() {
    const alert = useAlert();
    const params = useParams();
    const { t } = useTranslation();
    const navigate = useNavigate();
    const [speakModal, setSpeakModal] = useState(false)
    const [problem, setProblem] = useState("")
    const [isSubmitted, setIsSubmitted] = useState(false)

    useEffect(() => {
            setSpeakModal(true);
    }, [params])

    const handleSpeakToManager = async (e) => {
        e.preventDefault();
        if (!problem) {
            return alert.error("Please enter your comments")
        }

        const data = {
            uuid: params.uuid,
            problem: problem
        };

        try {
            const res = await axios.post(`/api/jobs/contact-to-manager`, data);
            console.log(res);
            setIsSubmitted(true)
            
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


    return (
        <div className="container">
            <div className="thankyou dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                <div className="mt-4">
                    <button
                        type="button"
                        onClick={() => setSpeakModal(prev => !prev)}
                        disabled={isSubmitted}
                        className="btn btn-primary"
                    >
                        Contact Manager
                    </button>
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
                            <Modal.Title>Contact Manager</Modal.Title>
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
        </div>
    );
}
