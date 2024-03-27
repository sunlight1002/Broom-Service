import { useEffect, useMemo, useState, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import axios from "axios";
import moment from "moment";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";

export default function CancelJobModal({ setIsOpen, isOpen, job }) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        repeatancy: "",
        until_date: null,
    });
    const [minUntilDate, setMinUntilDate] = useState(null);
    const [loading, setLoading] = useState(false);

    const navigate = useNavigate();
    const flatpickrRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const handleConfirmCancel = () => {
        setLoading(true);

        axios
            .put(`/api/client/jobs/${job.id}/cancel`, formValues, { headers })
            .then((response) => {
                setLoading(false);
                alert.success("Job cancelled successfully");
                navigate(`/client/jobs`);
            });
    };

    useEffect(() => {
        setMinUntilDate(
            moment().startOf("day").add(1, "day").format("YYYY-MM-DD")
        );
    }, []);

    const feeInAmount = useMemo(() => {
        const diffInDays = moment(job.start_date).diff(
            moment().startOf("day"),
            "days"
        );

        const _feePercentage = diffInDays >= 1 ? 50 : 100;

        return job.offer.total * (_feePercentage / 100);
    }, [job]);

    useEffect(() => {
        if (formValues.repeatancy == "until_date") {
            setFormValues({
                ...formValues,
                until_date: minUntilDate,
            });
        } else {
            setFormValues({
                ...formValues,
                until_date: null,
            });
        }
    }, [formValues.repeatancy]);

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
                        <p className="mb-4">
                            Cancellation fee of {feeInAmount} ILS will be
                            charged.
                        </p>

                        <div className="form-group">
                            <label className="control-label">Repeatancy</label>

                            <select
                                name="repeatancy"
                                onChange={(e) => {
                                    setFormValues({
                                        ...formValues,
                                        repeatancy: e.target.value,
                                    });
                                }}
                                value={formValues.repeatancy}
                                className="form-control mb-3"
                            >
                                <option value=""> --- Please select ---</option>
                                <option value="one_time">
                                    One Time ( for single job )
                                </option>
                                <option value="forever">Forever</option>
                                <option value="until_date">Until Date</option>
                            </select>
                        </div>

                        {formValues.repeatancy == "until_date" && (
                            <div className="form-group">
                                <label className="control-label">
                                    Until Date
                                </label>
                                <Flatpickr
                                    name="date"
                                    className="form-control"
                                    onChange={(
                                        selectedDates,
                                        dateStr,
                                        instance
                                    ) => {
                                        setFormValues({
                                            ...formValues,
                                            until_date: dateStr,
                                        });
                                    }}
                                    options={{
                                        disableMobile: true,
                                        minDate: minUntilDate,
                                        disable: [
                                            (date) => {
                                                // return true to disable
                                                return date.getDay() === 6;
                                            },
                                        ],
                                    }}
                                    value={formValues.until_date}
                                    ref={flatpickrRef}
                                />
                            </div>
                        )}
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
