import { useEffect, useMemo, useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import axios from "axios";

export default function CancelJobModal({ setIsOpen, isOpen, job }) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({ fee: "50" });
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleConfirmCancel = () => {
        setLoading(true);

        axios
            .put(`/api/admin/jobs/${job.id}/cancel`, formValues, { headers })
            .then((response) => {
                setLoading(false);
                alert.success("Job cancelled successfully");
                navigate(`/admin/jobs`);
            });
    };

    const feeInAmount = useMemo(() => {
        return job.offer.total * (formValues.fee / 100);
    }, [formValues.fee]);

    return (
        <Modal
            size="md"
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
        >
            <Modal.Header closeButton>
                <Modal.Title>Cancel Job</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                                Cancellation fee
                            </label>
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="radio"
                                    name="fee"
                                    id="fee50"
                                    value={50}
                                    checked={formValues.fee == 50}
                                    onChange={(e) => {
                                        setFormValues({
                                            ...formValues,
                                            fee: e.target.value,
                                        });
                                    }}
                                />
                                <label
                                    className="form-check-label"
                                    htmlFor="fee50"
                                >
                                    50%
                                </label>
                            </div>
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="radio"
                                    name="fee"
                                    id="fee100"
                                    value={100}
                                    checked={formValues.fee == 100}
                                    onChange={(e) => {
                                        setFormValues({
                                            ...formValues,
                                            fee: e.target.value,
                                        });
                                    }}
                                />
                                <label
                                    className="form-check-label"
                                    htmlFor="fee100"
                                >
                                    100%
                                </label>
                            </div>

                            <p>{feeInAmount} ILS will be charged.</p>
                        </div>
                    </div>
                </div>
            </Modal.Body>

            <Modal.Footer>
                <Button
                    type="button"
                    className="btn btn-secondary"
                    onClick={() => {
                        setIsOpen(false);
                    }}
                >
                    Close
                </Button>
                <Button
                    type="button"
                    onClick={handleConfirmCancel}
                    className="btn btn-danger"
                    disabled={loading}
                >
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
}
