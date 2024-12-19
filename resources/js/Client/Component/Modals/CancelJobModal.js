import { useEffect, useMemo, useState, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useParams, useNavigate } from "react-router-dom";
import { useAlert } from "react-alert";
import axios from "axios";
import moment from "moment";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import { useTranslation } from "react-i18next";

export default function CancelJobModal({ setIsOpen, isOpen, job }) {
    const alert = useAlert();
    const { t } = useTranslation();
    const [formValues, setFormValues] = useState({
        repeatancy: "one_time",
        until_date: null,
    });
    const [minUntilDate, setMinUntilDate] = useState(null);
    const [loading, setLoading] = useState(false);
    const [totalAmount, setTotalAmount] = useState(0);

    const navigate = useNavigate();
    const flatpickrRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const getOpenedJobAmountByGroup = () => {
        axios
            .get(`/api/client/jobs/${job.id}/total-amount-by-group`, {
                headers,
                params: {
                    group_id: job.job_group_id,
                    repeatancy: formValues.repeatancy,
                    until_date: formValues.until_date,
                },
            })
            .then((response) => {
                setTotalAmount(response.data.total_amount);
            })
            .catch((e) => { });
    };

    const handleConfirmCancel = () => {
        if (!formValues.repeatancy) {
            alert.error(t("alert.errors.repeatancy_missing"));
            return false;
        }

        if (formValues.repeatancy == "until_date" && !formValues.until_date) {
            alert.error(t("alert.errors.until_date_missing"));
            return false;
        }

        setLoading(true);

        axios
            .put(`/api/client/jobs/${job.id}/cancel`, formValues, { headers })
            .then((response) => {
                setLoading(false);
                alert.success(t("alert.success.job_cancelled"));
                navigate(`/client/jobs`);
            })
            .catch((e) => {
                setLoading(false);
                alert.error(e.response.data.message);
            });
    };

    useEffect(() => {
        setMinUntilDate(
            moment().startOf("day").add(1, "day").format("YYYY-MM-DD")
        );
    }, []);

    useEffect(() => {
        getOpenedJobAmountByGroup();
    }, [formValues.repeatancy, formValues.until_date]);

    // const feePercentage = useMemo(() => {
    //     const currentDay = moment().format("dddd"); // e.g., "Wednesday"
    //     const endOfWeek = moment().endOf("week");
    //     const endOfNextWeek = moment().add(1, "week").endOf("week");
    //     const jobStartDate = moment(job.start_date);
    //     const timeDifference = jobStartDate.diff(moment(), "hours", true); // Difference in hours

    //     let _feePercentage = 0;

    //     if (currentDay === "Wednesday") {
    //         if (timeDifference <= 24) {
    //             // If cancellation is within 24 hours, charge 100%
    //             _feePercentage = 100;
    //         } else if (jobStartDate.isSameOrBefore(endOfWeek)) {
    //             // Charge 50% for jobs canceled till the end of this week
    //             _feePercentage = 50;
    //         } else {
    //             // No charge for jobs after this week
    //             _feePercentage = 0;
    //         }
    //     } else {
    //         if (timeDifference <= 24) {
    //             // If cancellation is within 24 hours, charge 100%
    //             _feePercentage = 100;
    //         } else if (jobStartDate.isSameOrBefore(endOfNextWeek)) {
    //             // Charge 50% for jobs till the end of next week
    //             _feePercentage = 50;
    //         } else {
    //             // No charge for jobs after next week
    //             _feePercentage = 0;
    //         }
    //     }

    //     return _feePercentage;
    // }, [job.start_date]);

    // console.log(feePercentage, totalAmount);


    // const feeInAmount = useMemo(() => {
    //     return totalAmount * (feePercentage / 100);
    // }, [feePercentage, totalAmount]);


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
                <Modal.Title>{t("admin.global.cancelJob")}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <p className="mb-4">
                            {t("client.jobs.change.Cancellationfee", { feeInAmount: totalAmount })}
                        </p>

                        <div className="form-group">
                            <label className="control-label">{t("client.jobs.change.Repeatancy")}</label>

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
                                {/* <option value=""> --- Please select ---</option> */}
                                <option value="one_time">
                                    {t("client.jobs.change.oneTime")}
                                </option>
                                <option value="forever">{t("client.jobs.change.Forever")}</option>
                                <option value="until_date">{t("client.jobs.change.UntilDate")}</option>
                            </select>
                        </div>

                        {formValues.repeatancy == "until_date" && (
                            <div className="form-group">
                                <label className="control-label">
                                    {t("client.jobs.change.UntilDate")}
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
                    {t("modal.close")}
                </Button>
                <Button
                    type="button"
                    onClick={handleConfirmCancel}
                    className="btn btn-danger"
                    disabled={loading}
                >
                    {t("modal.cancel")}
                </Button>
            </Modal.Footer>
        </Modal>
    );
}
