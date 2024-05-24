import { useEffect, useMemo, useState, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import axios from "axios";

export default function JobExtraModal({ setIsOpen, isOpen, job, onSuccess }) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        extra_amount: job.extra_amount ?? 0,
    });
    const [loading, setLoading] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSave = () => {
        if (!formValues.extra_amount) {
            alert.error("The amount value is missing");
            return false;
        }

        setLoading(true);

        axios
            .post(`/api/admin/jobs/${job.id}/extra-amount`, formValues, {
                headers,
            })
            .then((response) => {
                setLoading(false);
                alert.success("Job extra amount saved successfully");
                onSuccess();
            })
            .catch((e) => {
                setLoading(false);
                alert.error(e.response.data.message);
            });
    };

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
                <Modal.Title>Job Extra</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                                Extra Amount
                            </label>

                            <input
                                type="number"
                                name="extra_amount"
                                onChange={(e) => {
                                    setFormValues({
                                        ...formValues,
                                        extra_amount: e.target.value,
                                    });
                                }}
                                value={formValues.extra_amount}
                                className="form-control mb-3"
                            />
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
                    onClick={handleSave}
                    className="btn btn-primary"
                    disabled={loading}
                >
                    Save
                </Button>
            </Modal.Footer>
        </Modal>
    );
}
