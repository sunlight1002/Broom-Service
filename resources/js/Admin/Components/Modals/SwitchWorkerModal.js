import { useEffect, useState, useMemo, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import moment from "moment";
import Swal from "sweetalert2";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";

export default function SwitchWorkerModal({
    setIsOpen,
    isOpen,
    job,
    onSuccess,
}) {
    const { t } = useTranslation();
    const alert = useAlert();
    const [workers, setWorkers] = useState([]);
    const [formValues, setFormValues] = useState({
        worker_id: "",
        repeatancy: "one_time",
        until_date: null,
        fee: "0",
    });
    const [minUntilDate, setMinUntilDate] = useState(null);
    const [isLoading, setIsLoading] = useState(false);

    const flatpickrRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    console.log(formValues);
    

    const checkValidation = () => {
        if (!formValues.worker_id) {
            alert.error("The worker is missing");
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

        // if (!formValues.fee) {
        //     alert.error("The fee is missing");
        //     return false;
        // }

        return true;
    };

    const handleInputChange = (e) => {
        let newFormValues = { ...formValues };

        newFormValues[e.target.name] = e.target.value;

        setFormValues({ ...newFormValues });
    };

    const getWorkerToSwitch = () => {
        axios
            .get(`/api/admin/jobs/${job.id}/worker-to-switch`, {
                headers,
            })
            .then((response) => {
                console.log(response.data);
                
                setWorkers(response.data.data);
            });
    };

    const handleSubmit = () => {
        let hasError = false;
        const valid = checkValidation();
        if (!valid) {
            hasError = true;
        }
        if (!hasError) {
            setIsLoading(true);
            axios
                .post(`/api/admin/jobs/${job.id}/switch-worker`, formValues, {
                    headers,
                })
                .then((response) => {
                    Swal.fire(
                        "Updated!",
                        "Worker switched successfully.",
                        "success"
                    );
                    setIsOpen(false);
                    setIsLoading(false);
                    onSuccess();
                })
                .catch((e) => {
                    Swal.fire({
                        title: "Error!",
                        text: e.response.data.message,
                        icon: "error",
                    });
                    setIsLoading(false);
                });
        }
    };

    useEffect(() => {
        getWorkerToSwitch();
    }, [job.id]);

    const handleFeeChange = (_value) => {
        if (formValues.fee == _value) {
            setFormValues((values) => {
                return { ...values, fee: "0" };
            });
        } else {
            setFormValues((values) => {
                return { ...values, fee: _value };
            });
        }
    };

    useEffect(() => {
        setMinUntilDate(
            moment().startOf("day").add(1, "day").format("YYYY-MM-DD")
        );
    }, []);

    const feeInAmount = useMemo(() => {
        return job.total_amount * (formValues.fee / 100);
    }, [formValues.fee]);

    return (
        <Modal
            size="md"
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
            backdrop="static"
        >
            <Modal.Header closeButton>
                <Modal.Title>{t("global.switchWorker")}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">{t("global.worker")}</label>
                            <select
                                name="worker_id"
                                className="form-control"
                                value={formValues.worker_id}
                                onChange={(e) => {
                                    handleInputChange(e);
                                }}
                            >
                                <option value="">{t("admin.leads.AddLead.AddLeadClient.JobModal.pleaseSelect")}</option>
                                {workers.map((w, i) => (
                                    <option value={w.id} key={i}>
                                        {w.firstname} {w.lastname}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">{t("global.repeatancy")}</label>

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
                                <option value="one_time">
                                    {t("client.jobs.change.oneTime")}
                                </option>
                                <option value="until_date">{t("client.jobs.change.UntilDate")}</option>
                                <option value="forever">{t("client.jobs.change.Forever")}</option>
                            </select>
                        </div>
                    </div>

                    {formValues.repeatancy == "until_date" && (
                        <div className="col-sm-12">
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
                                    }}
                                    value={formValues.until_date}
                                    ref={flatpickrRef}
                                />
                            </div>
                        </div>
                    )}

                    {/* <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                                {t(
                                    "admin.schedule.jobs.CancelModal.CancellationFee"
                                )}
                            </label>
                            <div className="form-check">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    name="fee"
                                    id="fee50"
                                    value={50}
                                    checked={formValues.fee == 50}
                                    onChange={(e) => {
                                        handleFeeChange(e.target.value);
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
                                    type="checkbox"
                                    name="fee"
                                    id="fee100"
                                    value={100}
                                    checked={formValues.fee == 100}
                                    onChange={(e) => {
                                        handleFeeChange(e.target.value);
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
                    </div> */}
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
                    disabled={isLoading}
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    {t("modal.save")}
                </Button>
            </Modal.Footer>

            <FullPageLoader visible={isLoading} />
        </Modal>
    );
}
