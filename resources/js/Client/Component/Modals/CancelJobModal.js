import { useEffect, useMemo, useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import axios from "axios";
import moment from "moment";

export default function CancelJobModal({ setIsOpen, isOpen, job }) {
    const alert = useAlert();
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const handleConfirmCancel = () => {
        setLoading(true);

        axios
            .put(`/api/client/jobs/${job.id}/cancel`, {}, { headers })
            .then((response) => {
                setLoading(false);
                alert.success("Job cancelled successfully");
                navigate(`/client/jobs`);
            });
    };

    const feeInAmount = useMemo(() => {
        const diffInDays = moment(job.start_date).diff(
            moment().startOf("day"),
            "days"
        );

        const _feePercentage = diffInDays >= 1 ? 50 : 100;

        return job.offer.total * (_feePercentage / 100);
    }, [job]);

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
                        <p>
                            Cancellation fee of {feeInAmount} ILS will be
                            charged.
                        </p>
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
