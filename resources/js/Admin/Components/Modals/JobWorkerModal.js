import { useEffect, useState, useMemo, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import moment from "moment";

import { createHourlyTimeArray } from "../../../Utils/job.utils";

const initialValues = {
    date: null,
    worker_id: "",
    worker_name: "",
    shifts: [{ start: "", end: "" }],
};

export default function JobWorkerModal({
    setIsOpen,
    isOpen,
    service,
    tmpFormValues,
    handleSaveForm,
    editIndex,
}) {
    const alert = useAlert();
    const [workers, setWorkers] = useState([]);
    const [selectedWorker, setSelectedWorker] = useState(null);
    const [calendarMaxDate, setCalendarMaxDate] = useState(null);
    const flatpickrRef = useRef(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const calendarMinDate = useMemo(() => {
        const _weekDay = moment().day();

        if (_weekDay == 5) {
            // include friday for now.
            // return moment().add(2, "days").format("YYYY-MM-DD");
        } else if (_weekDay == 6) {
            return moment().add(1, "days").format("YYYY-MM-DD");
        }

        return moment().format("YYYY-MM-DD");
    }, []);

    const [workerForm, setWorkerForm] = useState(
        editIndex > -1
            ? tmpFormValues
            : {
                  ...initialValues,
                  date: calendarMinDate,
              }
    );

    const checkValidation = (_formValues) => {
        if (!workerForm.date) {
            alert.error("The date is not selected");
            return false;
        }

        if (workerForm.worker_id == "") {
            alert.error("The worker is not selected");
            return false;
        }

        let workerIssue = true;
        for (let index = 0; index < workerForm.shifts.length; index++) {
            const _timing = workerForm.shifts[index];

            if (!_timing.start) {
                alert.error("The start value is missing");
                workerIssue = false;
                break;
            }

            if (!_timing.end) {
                alert.error("The end value is missing");
                workerIssue = false;
                break;
            }
        }

        if (!workerIssue) {
            return workerIssue;
        }

        return true;
    };

    const handleTimingForm = (index, tmpvalue) => {
        const _shifts = workerForm.shifts.map((_timing, wIndex) => {
            if (wIndex == index) {
                return tmpvalue;
            }
            return _timing;
        });

        setWorkerForm({
            ...workerForm,
            shifts: _shifts,
        });
    };

    const handleRemoveTiming = (_index) => {
        let _shifts = workerForm.shifts;
        _shifts.splice(_index, 1);

        setWorkerForm({
            ...workerForm,
            shifts: _shifts,
        });
    };

    const handleInputChange = (e) => {
        let newFormValues = { ...workerForm };

        newFormValues[e.target.name] = e.target.value;
        if (e.target.name == "worker_id") {
            if (e.target.value) {
                const _worker = workers.find((w) => w.id == e.target.value);
                newFormValues[
                    "worker_name"
                ] = `${_worker.firstname} ${_worker.lastname}`;
            } else {
                newFormValues["worker_name"] = "";
            }

            newFormValues["shifts"] = [{ start: "", end: "" }];
        }

        setWorkerForm({ ...newFormValues });
    };

    useEffect(() => {
        if (workerForm.worker_id && workers.length) {
            const _worker = workers.find((w) => w.id == workerForm.worker_id);

            let _times = [];
            if (_worker.wjobs[workerForm.date]) {
                const _shifts = _worker.wjobs[workerForm.date];
                const _shiftsArr = _shifts.split(",");

                _times = _shiftsArr.map((_shift) => {
                    const _time = _shift.split("-");

                    return { start: _time[0], end: _time[1] };
                });
            }

            let _shift = null;
            if (_worker.aval[workerForm.date]) {
                const _availableShifts = _worker.aval[workerForm.date][0];

                const _availableShiftsArr = _availableShifts.split("-");
                _shift = {
                    start:
                        _availableShiftsArr[0]
                            .match(/\d+/)[0]
                            .padStart(2, "0") + ":00",
                    end:
                        _availableShiftsArr[1]
                            .match(/\d+/)[0]
                            .padStart(2, "0") + ":00",
                };
            }

            setSelectedWorker({
                id: _worker.id,
                name: `${_worker.firstname} ${_worker.lastname}`,
                booked_shifts: _times,
                shift: _shift,
            });
        } else {
            setSelectedWorker(null);
        }
    }, [workers, workerForm.worker_id]);

    const handleDateChange = (_date) => {
        setWorkerForm({
            ...initialValues,
            date: _date,
        });
    };

    const handleSubmit = () => {
        let hasError = false;
        const valid = checkValidation(workerForm);
        if (!valid) {
            hasError = true;
        }
        if (!hasError) {
            handleSaveForm(editIndex, workerForm);
            setIsOpen(false);
        }
    };

    const handleAddTime = () => {
        setWorkerForm({
            ...workerForm,
            shifts: [...workerForm.shifts, { start: "", end: "" }],
        });
    };

    const getWorkers = (_service, _date) => {
        axios
            .get(`/api/admin/all-workers`, {
                headers,
                params: {
                    filter: true,
                    service_id: _service.service,
                    has_cat: _service.address.is_cat_avail,
                    has_dog: _service.address.is_dog_avail,
                    prefer_type: _service.address.prefer_type,
                    worker_ids: _service.address.not_allowed_worker_ids,
                    available_date: _date,
                },
            })
            .then((res) => {
                setWorkers(res.data.workers);
            });
    };

    useEffect(() => {
        setCalendarMaxDate(moment().endOf("week").add(2, "weeks").toDate());
    }, []);

    useEffect(() => {
        if (workerForm.date) {
            getWorkers(service, workerForm.date);
        }
    }, [workerForm.date, service]);

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
                <Modal.Title>
                    {editIndex == -1 ? "Add Worker" : "Edit Worker"}
                </Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Date</label>
                            <Flatpickr
                                name="date"
                                className="form-control"
                                onChange={(
                                    selectedDates,
                                    dateStr,
                                    instance
                                ) => {
                                    handleDateChange(dateStr);
                                }}
                                options={{
                                    disableMobile: true,
                                    minDate: calendarMinDate,
                                    maxDate: calendarMaxDate,
                                    // disable: [
                                    //     (date) => {
                                    //         // return true to disable
                                    //         return date.getDay() === 6;
                                    //     },
                                    // ],
                                }}
                                defaultValue={calendarMinDate}
                                ref={flatpickrRef}
                            />
                        </div>
                    </div>
                </div>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Worker</label>
                            <select
                                name="worker_id"
                                className="form-control"
                                value={workerForm.worker_id}
                                onChange={(e) => {
                                    handleInputChange(e);
                                }}
                            >
                                <option value="">--Please select--</option>
                                {workers.map((w, i) => (
                                    <option value={w.id} key={i}>
                                        {w.firstname} {w.lastname}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                {selectedWorker && (
                    <>
                        <div className="bg-dark text-white py-2 px-2 mb-2">
                            <strong>Shifts</strong>
                        </div>

                        <div className="row">
                            <div className="col-sm-4 col-5">
                                <div className="form-group mb-0">
                                    <label className="control-label">
                                        Start Time
                                    </label>
                                </div>
                            </div>

                            <div className="col-sm-4 col-5">
                                <div className="form-group mb-0">
                                    <label className="control-label">
                                        End Time
                                    </label>
                                </div>
                            </div>
                        </div>

                        {workerForm.shifts.map((_t, _index) => (
                            <TimingForm
                                formValues={_t}
                                handleTmpValue={handleTimingForm}
                                handleRemoveTiming={handleRemoveTiming}
                                selectedWorker={selectedWorker}
                                workerForm={workerForm}
                                index={_index}
                                key={_index}
                            />
                        ))}

                        <div className="row">
                            <div className="col-sm-4 col-6">
                                <button
                                    type="button"
                                    className="btn btn-success"
                                    onClick={handleAddTime}
                                >
                                    Add time
                                </button>
                            </div>
                        </div>
                    </>
                )}
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
                    onClick={handleSubmit}
                    className="btn btn-primary"
                >
                    Save
                </Button>
            </Modal.Footer>
        </Modal>
    );
}

const TimingForm = ({
    formValues,
    handleTmpValue,
    index,
    handleRemoveTiming,
    selectedWorker,
    workerForm,
}) => {
    const handleInputChange = (e) => {
        let newFormValues = { ...formValues };

        newFormValues[e.target.name] = e.target.value;

        handleTmpValue(index, newFormValues);
    };

    const timeOptions = useMemo(() => {
        if (!selectedWorker.shift) {
            return [];
        }

        return createHourlyTimeArray(
            selectedWorker.shift.start,
            selectedWorker.shift.end
        );
    }, [selectedWorker.shift]);

    const startTimeOptions = useMemo(() => {
        // start time options depends on worker selected.
        if (!workerForm.worker_id) {
            return [];
        }

        const _timeOptions = timeOptions.filter((_option) => {
            if (_option == "24:00") {
                return false;
            }

            const _startTime = moment(_option, "ha");
            return !selectedWorker.booked_shifts.some((shift) => {
                const _shiftStartTime = moment(shift.start, "ha");
                const _shiftEndTime = moment(shift.end, "ha");

                return (
                    _shiftStartTime.isSame(_startTime) ||
                    _startTime.isBetween(_shiftStartTime, _shiftEndTime)
                );
            });
        });

        return _timeOptions;
    }, [workerForm.worker_id, selectedWorker.booked_shifts, timeOptions]);

    const endTimeOptions = useMemo(() => {
        // end time options depends on start time, just for UX.
        if (!workerForm.worker_id || !formValues.start) {
            return [];
        }

        const startIndex = timeOptions.indexOf(formValues.start);

        const _timeOptions = timeOptions
            .slice(startIndex + 1)
            .filter((_option) => {
                if (_option == "08:00") {
                    return false;
                }

                const _startTime = moment(formValues.start, "ha");
                const _endTime = moment(_option, "ha");
                return !selectedWorker.booked_shifts.some((shift) => {
                    const _shiftStartTime = moment(shift.start, "ha");
                    const _shiftEndTime = moment(shift.end, "ha");

                    return (
                        _shiftEndTime.isSame(_endTime) ||
                        _endTime.isBetween(_shiftStartTime, _shiftEndTime) ||
                        (_startTime.isBefore(_shiftStartTime) &&
                            _endTime.isAfter(_shiftEndTime))
                    );
                });
            });

        return _timeOptions;
    }, [startTimeOptions, formValues.start]);

    return (
        <div className="row">
            <div className="col-sm-4 col-5">
                <div className="form-group">
                    <select
                        name="start"
                        className="form-control"
                        value={formValues.start}
                        onChange={(e) => {
                            handleInputChange(e);
                        }}
                    >
                        <option value="">--Select--</option>
                        {startTimeOptions.map((t, i) => {
                            return (
                                <option value={t} key={i}>
                                    {" "}
                                    {t}{" "}
                                </option>
                            );
                        })}
                    </select>
                </div>
            </div>

            <div className="col-sm-4 col-5">
                <div className="form-group">
                    <select
                        name="end"
                        className="form-control"
                        value={formValues.end}
                        onChange={(e) => {
                            handleInputChange(e);
                        }}
                    >
                        <option value="">--Select--</option>
                        {endTimeOptions.map((t, i) => {
                            return (
                                <option value={t} key={i}>
                                    {" "}
                                    {t}{" "}
                                </option>
                            );
                        })}
                    </select>
                </div>
            </div>

            {index > 0 && (
                <div className="col-sm-4 col-2">
                    <div className="form-group">
                        <button
                            type="button"
                            className="btn btn-icon btn-danger"
                            onClick={() => handleRemoveTiming(index)}
                        >
                            <i className="fa fa-close"></i>
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
};
