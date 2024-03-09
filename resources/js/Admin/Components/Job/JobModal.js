import { useEffect, useRef, useState, useMemo } from "react";
import { Button, Modal } from "react-bootstrap";
import Select from "react-select";

const slot = [
    { value: "full day- 8am-16pm", label: "full day- 8am-16pm" },
    { value: "morning1 - 8am-10am", label: "morning1 - 8am-10am" },
    { value: "morning 2 - 10am-12pm", label: "morning 2 - 10am-12pm" },
    { value: "morning- 08am-12pm", label: "morning- 08am-12pm" },
    { value: "noon1 -12pm-14pm", label: "noon1 -12pm-14pm" },
    { value: "noon2 14pm-16pm", label: "noon2 14pm-16pm" },
    { value: "noon 12pm-16pm", label: "noon 12pm-16pm" },
    { value: "af1 16pm-18pm", label: "af1 16pm-18pm" },
    { value: "af2 18pm-20pm", label: "af2 18pm-20pm" },
    { value: "afternoon 16pm-20pm", label: "afternoon 16pm-20pm" },
    { value: "ev1 20pm-22pm", label: "ev1 20pm-22pm" },
    { value: "ev2 22pm-24pm", label: "ev2 22pm-24pm" },
    { value: "evening 20pm-24am", label: "evening 20pm-24am" },
];

const frequencyDays = [
    { value: "sunday", label: "Sunday" },
    { value: "monday", label: "Monday" },
    { value: "tuesday", label: "Tuesday" },
    { value: "wednesday", label: "Wednesday" },
    { value: "thursday", label: "Thursday" },
    { value: "friday", label: "Friday" },
    { value: "saturday", label: "Saturday" },
];

const monthDateArr = () => {
    let array = [];
    for (let i = 1; i <= 29; i++) {
        array.push(i);
    }
    return array;
};

const JobModal = (props) => {
    const {
        setIsOpen,
        isOpen,
        addresses,
        worker,
        AllServices,
        AllFreq,
        handleInputChange,
        tmpFormValue,
        handleSaveJobForm,
        isAdd,
        index,
    } = props;
    const [filteredWorkers, setFilteredWorkers] = useState([]);
    const [toggleOtherService, setToggleOtherService] = useState(false);

    useEffect(() => {
        if (tmpFormValue.address) {
            const getAddress = addresses[tmpFormValue.address];
            const tmpWorker = worker.filter((w) => {
                return (
                    (getAddress.prefer_type !== "default" &&
                    getAddress.prefer_type !== "both"
                        ? w.gender === getAddress.prefer_type
                        : true) &&
                    (Boolean(getAddress.is_cat_avail)
                        ? Boolean(w.is_afraid_by_cat)
                            ? false
                            : !Boolean(w.is_afraid_by_cat)
                        : true) &&
                    (Boolean(getAddress.is_dog_avail)
                        ? Boolean(w.is_afraid_by_dog)
                            ? false
                            : !Boolean(w.is_afraid_by_dog)
                        : true)
                );
            });
            setFilteredWorkers(tmpWorker);
        }
    }, [tmpFormValue, worker, addresses]);

    const showWeekDayOption = useMemo(() => {
        return ["2w", "3w", "4w", "5w", "m", "2m", "3m", "6m", "y"].includes(
            tmpFormValue.period
        );
    }, [tmpFormValue]);

    const showMonthOption = useMemo(() => {
        return ["2m", "3m", "6m", "y"].includes(tmpFormValue.period);
    }, [tmpFormValue.period]);

    const showMonthDateOption = useMemo(() => {
        return ["m", "2m", "3m", "6m", "y"].includes(tmpFormValue.period);
    }, [tmpFormValue.period]);

    const monthOptions = useMemo(() => {
        let _monthArr = [];
        if (["2m", "3m", "6m"].includes(tmpFormValue.period)) {
            _monthArr = [
                { value: 1, label: "first" },
                { value: 2, label: "second" },
                { value: 3, label: "third" },
                { value: 4, label: "fourth" },
                { value: 5, label: "fifth" },
                { value: 6, label: "sixth" },
                { value: 7, label: "seventh" },
                { value: 8, label: "eighth" },
                { value: 9, label: "ninth" },
                { value: 10, label: "tenth" },
                { value: 11, label: "eleventh" },
                { value: 12, label: "twelfth" },
            ];

            const _monthCount = parseInt(tmpFormValue.period.charAt(0));
            _monthArr = _monthArr.slice(0, _monthCount);
        } else if (tmpFormValue.period == "y") {
            _monthArr = [
                { value: 1, label: "January" },
                { value: 2, label: "February" },
                { value: 3, label: "March" },
                { value: 4, label: "April" },
                { value: 5, label: "May" },
                { value: 6, label: "June" },
                { value: 7, label: "July" },
                { value: 8, label: "August" },
                { value: 9, label: "September" },
                { value: 10, label: "October" },
                { value: 11, label: "November" },
                { value: 12, label: "December" },
            ];
        }

        return _monthArr;
    }, [tmpFormValue.period]);

    return (
        <Modal
            size="lg"
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
        >
            <Modal.Header closeButton>
                <Modal.Title>Add Job</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                                Property address
                            </label>
                            <select
                                className="form-control"
                                name="address"
                                value={tmpFormValue.address || ""}
                                onChange={(e) => {
                                    handleInputChange(0, e);
                                }}
                            >
                                <option value="">--Please select--</option>
                                {addresses.map((address, i) => (
                                    <option value={address.id} key={i}>
                                        {address.geo_address}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Worker</label>
                            <select
                                name="worker"
                                className="form-control  mb-2"
                                value={tmpFormValue.worker || 0}
                                onChange={(e) => {
                                    handleInputChange(0, e);
                                }}
                            >
                                <option value={0}>--Please select--</option>
                                {filteredWorkers &&
                                    filteredWorkers.map((w, i) => {
                                        return (
                                            <option
                                                name={
                                                    w.firstname +
                                                    " " +
                                                    w.lastname
                                                }
                                                value={w.id}
                                                key={i}
                                            >
                                                {w.firstname + " " + w.lastname}
                                            </option>
                                        );
                                    })}
                            </select>
                        </div>
                        <div className="form-group">
                            <Select
                                name="shift"
                                isMulti
                                options={slot}
                                className="basic-multi-single "
                                isClearable={true}
                                placeholder="--Please select--"
                                classNamePrefix="select"
                                onChange={(newValue, actionMeta) => {
                                    const e = {
                                        target: {
                                            name: actionMeta.name,
                                            option: newValue,
                                        },
                                    };
                                    handleInputChange(0, e);
                                }}
                            />
                        </div>
                    </div>
                </div>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Service</label>
                            <select
                                name="service"
                                className="form-control"
                                value={tmpFormValue.service || 0}
                                onChange={(e) => {
                                    handleInputChange(0, e);
                                    if (e.target.value === "10") {
                                        setToggleOtherService(true);
                                    } else {
                                        setToggleOtherService(false);
                                    }
                                }}
                            >
                                <option value={0}> -- Please select --</option>
                                {AllServices &&
                                    AllServices.map((s, i) => {
                                        return (
                                            <option
                                                name={s.name}
                                                template={s.template}
                                                value={s.id}
                                                key={i}
                                            >
                                                {" "}
                                                {s.name}{" "}
                                            </option>
                                        );
                                    })}
                            </select>
                        </div>
                        {toggleOtherService && (
                            <div className="form-group">
                                <textarea
                                    type="text"
                                    name="other_title"
                                    id={`other_title` + "0"}
                                    placeholder="Service Title"
                                    className="form-control"
                                    value={tmpFormValue.other_title || ""}
                                    onChange={(e) => handleInputChange(0, e)}
                                />
                            </div>
                        )}
                    </div>
                </div>
                <div className="row">
                    <div className="col-sm-4">
                        <div className="form-group">
                            <label className="control-label">Type</label>
                            <select
                                name="type"
                                className="form-control"
                                value={tmpFormValue.type || "fixed"}
                                onChange={(e) => {
                                    handleInputChange(0, e);
                                }}
                            >
                                <option value="fixed">Fixed</option>
                                <option value="hourly">Hourly</option>
                            </select>
                        </div>
                    </div>
                    <div className="col-sm-4">
                        <div className="form-group">
                            <label className="control-label">Job Hours</label>
                            <input
                                type="number"
                                name="jobHours"
                                value={tmpFormValue.jobHours || ""}
                                onChange={(e) => handleInputChange(0, e)}
                                className="form-control jobhr"
                                required
                                placeholder="Enter job Hrs"
                            />
                        </div>
                    </div>
                    <div className="col-sm-4">
                        <div className="form-group">
                            <label className="control-label">Price</label>
                            {tmpFormValue.type !== "hourly" && (
                                <input
                                    type="number"
                                    name="fixed_price"
                                    value={tmpFormValue.fixed_price || ""}
                                    onChange={(e) => handleInputChange(0, e)}
                                    className="form-control jobprice"
                                    required
                                    placeholder="Enter job price"
                                />
                            )}
                            {tmpFormValue.type === "hourly" && (
                                <input
                                    type="text"
                                    name="rateperhour"
                                    value={tmpFormValue.rateperhour || ""}
                                    onChange={(e) => handleInputChange(0, e)}
                                    className="form-control jobrate"
                                    required
                                    placeholder="Enter rate P/Hr"
                                />
                            )}
                        </div>
                    </div>
                </div>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Frequency</label>
                            <select
                                name="frequency"
                                className="form-control mb-2"
                                value={tmpFormValue.frequency || 0}
                                onChange={(e) => handleInputChange(0, e)}
                            >
                                <option value={0}> -- Please select --</option>
                                {AllFreq &&
                                    AllFreq.map((s, i) => {
                                        return (
                                            <option
                                                cycle={s.cycle}
                                                period={s.period}
                                                name={s.name}
                                                value={s.id}
                                                key={i}
                                            >
                                                {" "}
                                                {s.name}{" "}
                                            </option>
                                        );
                                    })}
                            </select>
                        </div>
                        <div
                            className="form-group"
                            style={{
                                display: tmpFormValue.period ? "block" : "none",
                            }}
                        >
                            <label className="control-label">Start from</label>
                            <input
                                type="date"
                                name="start_date"
                                className="form-control"
                                value={tmpFormValue.start_date}
                                onChange={(e) => {
                                    handleInputChange(index, e);
                                }}
                            />
                        </div>

                        <div
                            className="form-group"
                            style={{
                                display: showMonthDateOption ? "block" : "none",
                            }}
                        >
                            <div className="d-inline">
                                <input
                                    type="radio"
                                    name="monthday_selection_type"
                                    value="date"
                                    onChange={(e) => {
                                        handleInputChange(index, e);
                                    }}
                                    checked={
                                        tmpFormValue.monthday_selection_type ==
                                        "date"
                                    }
                                />

                                <span> Day </span>

                                <select
                                    name="month_date"
                                    className="choosen-select"
                                    onChange={(e) => {
                                        handleInputChange(index, e);
                                    }}
                                >
                                    {monthDateArr().map((i) => {
                                        return (
                                            <option value={i} key={i}>
                                                {i}
                                            </option>
                                        );
                                    })}
                                </select>

                                <div
                                    className={
                                        showMonthOption ? "d-inline" : "d-none"
                                    }
                                >
                                    <span> of </span>

                                    <select
                                        name="month_occurrence"
                                        className="choosen-select"
                                        value={tmpFormValue.month_occurrence}
                                        onChange={(e) => {
                                            handleInputChange(index, e);
                                        }}
                                    >
                                        {monthOptions.map((m, i) => {
                                            return (
                                                <option value={m.value} key={i}>
                                                    {m.label}
                                                </option>
                                            );
                                        })}
                                    </select>

                                    <span> month</span>
                                </div>
                            </div>
                        </div>

                        <div
                            className="form-group"
                            style={{
                                display: showWeekDayOption ? "block" : "none",
                            }}
                        >
                            <div className="d-inline">
                                <input
                                    type="radio"
                                    name="monthday_selection_type"
                                    value="weekday"
                                    onChange={(e) => {
                                        handleInputChange(index, e);
                                    }}
                                    checked={
                                        tmpFormValue.monthday_selection_type ==
                                        "weekday"
                                    }
                                />

                                <span> The </span>

                                <select
                                    name="weekday_occurrence"
                                    className="choosen-select"
                                    onChange={(e) => {
                                        handleInputChange(index, e);
                                    }}
                                >
                                    <option value="1">first</option>
                                    <option value="2">second</option>
                                    <option value="3">third</option>
                                    <option value="last">last</option>
                                </select>

                                <select
                                    name="weekday"
                                    className="ml-2 choosen-select"
                                    onChange={(e) => {
                                        handleInputChange(index, e);
                                    }}
                                >
                                    {frequencyDays.map((wd, i) => {
                                        return (
                                            <option value={wd.value} key={i}>
                                                {wd.label}
                                            </option>
                                        );
                                    })}
                                </select>

                                <div
                                    className={
                                        showMonthOption ? "d-inline" : "d-none"
                                    }
                                >
                                    <span> of </span>

                                    <select
                                        name="month_occurrence"
                                        className="choosen-select"
                                        value={tmpFormValue.month_occurrence}
                                        onChange={(e) => {
                                            handleInputChange(index, e);
                                        }}
                                    >
                                        {monthOptions.map((m, i) => {
                                            return (
                                                <option value={m.value} key={i}>
                                                    {m.label}
                                                </option>
                                            );
                                        })}
                                    </select>

                                    <span> month</span>
                                </div>
                            </div>
                        </div>

                        <div
                            className="form-group"
                            style={{
                                display:
                                    tmpFormValue.period == "w" &&
                                    tmpFormValue.cycle > 1
                                        ? "block"
                                        : "none",
                            }}
                        >
                            <Select
                                name="weekdays"
                                isMulti
                                options={frequencyDays}
                                className="basic-multi-single "
                                isClearable={true}
                                placeholder="--Please select--"
                                classNamePrefix="select"
                                onChange={(newValue, actionMeta) => {
                                    const e = {
                                        target: {
                                            name: actionMeta.name,
                                            option: newValue,
                                        },
                                    };
                                    handleInputChange(0, e);
                                }}
                                style={{
                                    display:
                                        tmpFormValue.period == "w"
                                            ? "block"
                                            : "none",
                                }}
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
                    onClick={(e) => {
                        handleSaveJobForm(isAdd ? "" : index);
                        setIsOpen(false);
                    }}
                    className="btn btn-primary"
                >
                    Save
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default JobModal;
