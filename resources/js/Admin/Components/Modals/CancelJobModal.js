import { useEffect, useMemo, useState, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import moment from "moment";
import axios from "axios";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import { useTranslation } from "react-i18next";

export default function CancelJobModal({ setIsOpen, isOpen, job }) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        fee: "50",
        repeatancy: "",
        until_date: null,
    });
    const [minUntilDate, setMinUntilDate] = useState(null);
    const [loading, setLoading] = useState(false);

    const navigate = useNavigate();
    const { t } = useTranslation();
    const flatpickrRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleConfirmCancel = () => {
        if (!formValues.fee) {
            alert.error("The fee is missing");
            return false;
        }

        if (!formValues.repeatancy) {
            alert.error("The Repeatancy is missing");
            return false;
        }

        if (formValues.repeatancy == "until_date" && !formValues.until_date) {
            alert.error("The Until Date is missing");
            return false;
        }

        setLoading(true);

        axios
            .put(`/api/admin/jobs/${job.id}/cancel`, formValues, { headers })
            .then((response) => {
                setLoading(false);
                alert.success("Job cancelled successfully");
                navigate(`/admin/jobs`);
            });
    };

    useEffect(() => {
        setMinUntilDate(
            moment().startOf("day").add(1, "day").format("YYYY-MM-DD")
        );
    }, []);

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
                                {t(
                                    "admin.schedule.jobs.CancelModal.CancellationFee"
                                )}
                            </label>
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="radio"
                                    name="fee"
                                    id="fee0"
                                    value={0}
                                    checked={formValues.fee == 0}
                                    onChange={(e) => {
                                        setFormValues({
                                            ...formValues,
                                            fee: e.target.value,
                                        });
                                    }}
                                />
                                <label
                                    className="form-check-label"
                                    htmlFor="fee0"
                                >
                                    0%
                                </label>
                            </div>
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

                            {feeInAmount > 0 ? (
                                <p>{feeInAmount} ILS will be charged.</p>
                            ) : (
                                <p>
                                    {t(
                                        "admin.schedule.jobs.CancelModal.NoCharge"
                                    )}
                                </p>
                            )}
                        </div>

                        <div className="form-group">
                            <label className="control-label">
                                {t(
                                    "admin.schedule.jobs.CancelModal.Repeatancy"
                                )}
                            </label>

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
                                <option value="">
                                    {t(
                                        "admin.schedule.jobs.CancelModal.options.PleaseSelect"
                                    )}
                                </option>
                                <option value="one_time">
                                    {t(
                                        "admin.schedule.jobs.CancelModal.options.oneTime"
                                    )}
                                </option>
                                <option value="forever">
                                    {t(
                                        "admin.schedule.jobs.CancelModal.options.Forever"
                                    )}
                                </option>
                                <option value="until_date">
                                    {t(
                                        "admin.schedule.jobs.CancelModal.options.UntilDate"
                                    )}
                                </option>
                            </select>
                        </div>

                        {formValues.repeatancy == "until_date" && (
                            <div className="form-group">
                                <label className="control-label">
                                    {t(
                                        "admin.schedule.jobs.CancelModal.options.UntilDate"
                                    )}
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
                                    defaultValue={minUntilDate}
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
                    {t("admin.schedule.jobs.CancelModal.Close")}
                </Button>
                <Button
                    type="button"
                    onClick={handleConfirmCancel}
                    className="btn btn-danger"
                    disabled={loading}
                >
                    {t("admin.schedule.jobs.CancelModal.Cancel")}
                </Button>
            </Modal.Footer>
        </Modal>
    );
}
