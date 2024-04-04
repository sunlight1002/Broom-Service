import { useEffect, useState, useMemo, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";

import { createHourlyTimeArray } from "../../../Utils/job.utils";

export default function FreezeWorkerShiftModal({
    setIsOpen,
    isOpen,
    workerId,
}) {
    const alert = useAlert();
    const [formValues, setFormValues] = useState({
        start_time: "",
        end_time: "",
    });
    const [isLoading, setIsLoading] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const checkValidation = () => {
        if (formValues.end_time && !formValues.start_time) {
            alert.error("The start value is missing");
            return false;
        }

        if (formValues.start_time && !formValues.end_time) {
            alert.error("The end value is missing");
            return false;
        }

        return true;
    };

    const timeOptions = useMemo(() => {
        return createHourlyTimeArray("08:00", "24:00");
    }, []);

    const startTimeOptions = useMemo(() => {
        const _timeOptions = timeOptions.filter((_option) => {
            if (_option == "24:00") {
                return false;
            }

            return true;
        });

        return _timeOptions;
    }, [timeOptions]);

    const endTimeOptions = useMemo(() => {
        // end time options depends on start time, just for UX.
        if (!formValues.start_time) {
            return [];
        }

        const startIndex = timeOptions.indexOf(formValues.start_time);

        const _timeOptions = timeOptions
            .slice(startIndex + 1)
            .filter((_option) => {
                if (_option == "08:00") {
                    return false;
                }

                return true;
            });

        return _timeOptions;
    }, [startTimeOptions, formValues.start_time]);

    const handleInputChange = (e) => {
        let newFormValues = { ...formValues };

        newFormValues[e.target.name] = e.target.value;

        setFormValues({ ...newFormValues });
    };

    const getWorker = () => {
        axios
            .get(`/api/admin/workers/${workerId}/edit`, {
                headers,
            })
            .then((response) => {
                if (response.data.worker) {
                    const _worker = response.data.worker;

                    if (
                        _worker.freeze_shift_start_time &&
                        _worker.freeze_shift_end_time
                    ) {
                        const _start_time = _worker.freeze_shift_start_time;
                        const _end_time = _worker.freeze_shift_end_time;

                        setFormValues({
                            start_time: _start_time.substr(
                                0,
                                _start_time.lastIndexOf(":")
                            ),
                            end_time: _end_time.substr(
                                0,
                                _end_time.lastIndexOf(":")
                            ),
                        });
                    }
                }
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
                .post(
                    `/api/admin/workers/${workerId}/freeze-shift`,
                    formValues,
                    {
                        headers,
                    }
                )
                .then((response) => {
                    Swal.fire(
                        "Updated!",
                        "Freeze shift has been updated.",
                        "success"
                    );
                    setIsOpen(false);
                    setIsLoading(false);
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
        getWorker();
    }, [workerId]);

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
                <Modal.Title>Freeze shift</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-6">
                        <div className="form-group">
                            <label className="control-label">Start Time</label>
                            <select
                                name="start_time"
                                className="form-control"
                                value={formValues.start_time}
                                onChange={(e) => {
                                    handleInputChange(e);
                                }}
                            >
                                <option value="">--Select--</option>
                                {startTimeOptions.map((t, i) => {
                                    return (
                                        <option value={t} key={i}>
                                            {t}
                                        </option>
                                    );
                                })}
                            </select>
                        </div>
                    </div>

                    <div className="col-sm-6">
                        <div className="form-group">
                            <label className="control-label">End Time</label>
                            <select
                                name="end_time"
                                className="form-control"
                                value={formValues.end_time}
                                onChange={(e) => {
                                    handleInputChange(e);
                                }}
                            >
                                <option value="">--Select--</option>
                                {endTimeOptions.map((t, i) => {
                                    return (
                                        <option value={t} key={i}>
                                            {t}
                                        </option>
                                    );
                                })}
                            </select>
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
                    disabled={isLoading}
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    Save
                </Button>
            </Modal.Footer>
        </Modal>
    );
}
