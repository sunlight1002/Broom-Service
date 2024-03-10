import { useEffect, memo, useState, useMemo } from "react";
import { Button, Modal } from "react-bootstrap";
import Select from "react-select";
import { getWorkerBasedOnProperty } from "../../Utils/job.utils";
import { useAlert } from "react-alert";
import { useParams } from "react-router-dom";

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

const JobModal = memo(function JobModal({
    setIsOpen,
    isOpen,
    addresses,
    worker,
    AllServices,
    AllFreq,
    tmpFormValues,
    handleTmpValue,
    handleSaveJobForm,
    isAdd,
    index,
}) {
    const [filteredWorkers, setFilteredWorkers] = useState([]);
    const [toggleOtherService, setToggleOtherService] = useState(false);
    const alert = useAlert();

    const handleInputChange = (e) => {
        let newFormValues = { ...tmpFormValues };

        newFormValues[e.target.name] = e.target.value;
        if (e.target.name == "service") {
            newFormValues["name"] =
                e.target.options[e.target.selectedIndex].getAttribute("name");
            newFormValues["template"] =
                e.target.options[e.target.selectedIndex].getAttribute(
                    "template"
                );
        }
        if (e.target.name == "frequency") {
            newFormValues["freq_name"] =
                e.target.options[e.target.selectedIndex].getAttribute("name");
            newFormValues["cycle"] =
                e.target.options[e.target.selectedIndex].getAttribute("cycle");
            newFormValues["period"] =
                e.target.options[e.target.selectedIndex].getAttribute("period");
        }
        if (e.target.name == "worker") {
            newFormValues["woker_name"] =
                e.target.options[e.target.selectedIndex].getAttribute("name");
        }
        if (e.target.name == "weekdays") {
            const _weekdays = e.target.option.map((i) => i.value);

            if (
                _weekdays.length > newFormValues["cycle"] &&
                newFormValues["cycle"] != 0
            ) {
                window.alert(
                    "You can select at most " +
                        newFormValues["cycle"] +
                        " day(s) for this frequency"
                );
            } else {
                newFormValues["weekdays"] = _weekdays;
                newFormValues["week_days"] = e.target.option;
            }
        }
        if (e.target.name == "shift") {
            var result = "";
            var sAr = [];
            var options = e.target.option;
            var opt;

            for (var k = 0, iLen = options.length; k < iLen; k++) {
                opt = options[k];
                // if (opt.selected) {
                sAr.push(opt.value);
                result += opt.value + ", ";
                // }
            }
            newFormValues["shift_ar"] = sAr;
            newFormValues["shift"] = result.replace(/,\s*$/, "");

            newFormValues["shift_default"] = e.target.option;
        }
        handleTmpValue(newFormValues);
    };

    const checkValidation = () => {
        if (tmpFormValues.address == "") {
            alert.error("The address is not selected");
            return false;
        }
        if (
            !tmpFormValues.worker ||
            tmpFormValues.worker == "" ||
            tmpFormValues.worker == 0
        ) {
            alert.error("The worker is not selected");
            return false;
        }
        if (!tmpFormValues.shift || tmpFormValues.shift == "") {
            alert.error("The shift is not selected");
            return false;
        }
        if (tmpFormValues.service == "" || tmpFormValues.service == 0) {
            alert.error("The service is not selected");
            return false;
        }

        let ot = document.querySelector("#other_title");

        if (tmpFormValues.service == "10" && ot != undefined) {
            if (tmpFormValues.other_title == "") {
                alert.error("Other title cannot be blank");
                return false;
            }
            tmpFormValues.other_title =
                document.querySelector("#other_title").value;
        } else {
            tmpFormValues.other_title = "";
        }

        if (tmpFormValues.jobHours == "") {
            alert.error("The job hours value is missing");
            return false;
        }
        !tmpFormValues.type ? (tmpFormValues.type = "fixed") : "";
        if (tmpFormValues.type == "hourly") {
            if (tmpFormValues.rateperhour == "") {
                alert.error("The rate per hour value is missing");
                return false;
            }
        } else {
            if (tmpFormValues.fixed_price == "") {
                alert.error("The job price is missing");
                return false;
            }
        }

        if (tmpFormValues.frequency == "" || tmpFormValues.frequency == 0) {
            alert.error("The frequency is not selected");
            return false;
        }
        if (
            tmpFormValues.weekdays.length > 0 &&
            tmpFormValues.weekdays.length > tmpFormValues.cycle &&
            tmpFormValues.cycle != "0"
        ) {
            alert.error("The frequency days are invalid");
            return false;
        }

        return true;
    };
    let param = useParams();
    useEffect(() => {
        if (tmpFormValues.address) {
            const getAddress = param.id
                ? addresses.filter((a) => a.id == tmpFormValues.address)[0]
                : addresses[tmpFormValues.address];
            const tmpWorker = getWorkerBasedOnProperty(worker, getAddress);
            setFilteredWorkers(tmpWorker);
        }
    }, [tmpFormValues, worker, addresses]);

    const showWeekDayOption = useMemo(() => {
        return ["2w", "3w", "4w", "5w", "m", "2m", "3m", "6m", "y"].includes(
            tmpFormValues.period
        );
    }, [tmpFormValues]);

    const showMonthOption = useMemo(() => {
        return ["2m", "3m", "6m", "y"].includes(tmpFormValues.period);
    }, [tmpFormValues.period]);

    const showMonthDateOption = useMemo(() => {
        return ["m", "2m", "3m", "6m", "y"].includes(tmpFormValues.period);
    }, [tmpFormValues.period]);

    const monthOptions = useMemo(() => {
        let _monthArr = [];
        if (["2m", "3m", "6m"].includes(tmpFormValues.period)) {
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

            const _monthCount = parseInt(tmpFormValues.period.charAt(0));
            _monthArr = _monthArr.slice(0, _monthCount);
        } else if (tmpFormValues.period == "y") {
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
    }, [tmpFormValues.period]);

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
                <Modal.Title>{isAdd ? "Add Job" : "Edit Job"}</Modal.Title>
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
                                value={tmpFormValues.address || ""}
                                onChange={(e) => {
                                    handleInputChange(e);
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
                                value={tmpFormValues.worker || 0}
                                onChange={(e) => {
                                    handleInputChange(e);
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
                                defaultValue={tmpFormValues.shift_default}
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
                                    handleInputChange(e);
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
                                value={tmpFormValues.service || 0}
                                onChange={(e) => {
                                    handleInputChange(e);
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
                                    id={`other_title`}
                                    placeholder="Service Title"
                                    className="form-control"
                                    value={tmpFormValues.other_title || ""}
                                    onChange={(e) => handleInputChange(e)}
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
                                value={tmpFormValues.type || "fixed"}
                                onChange={(e) => {
                                    handleInputChange(e);
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
                                value={tmpFormValues.jobHours || ""}
                                onChange={(e) => handleInputChange(e)}
                                className="form-control jobhr"
                                required
                                placeholder="Enter job Hrs"
                            />
                        </div>
                    </div>
                    <div className="col-sm-4">
                        <div className="form-group">
                            <label className="control-label">Price</label>
                            {tmpFormValues.type !== "hourly" && (
                                <input
                                    type="number"
                                    name="fixed_price"
                                    value={tmpFormValues.fixed_price || ""}
                                    onChange={(e) => handleInputChange(e)}
                                    className="form-control jobprice"
                                    required
                                    placeholder="Enter job price"
                                />
                            )}
                            {tmpFormValues.type === "hourly" && (
                                <input
                                    type="text"
                                    name="rateperhour"
                                    value={tmpFormValues.rateperhour || ""}
                                    onChange={(e) => handleInputChange(e)}
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
                                value={tmpFormValues.frequency || 0}
                                onChange={(e) => handleInputChange(e)}
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
                                display: tmpFormValues.period
                                    ? "block"
                                    : "none",
                            }}
                        >
                            <label className="control-label">Start from</label>
                            <input
                                type="date"
                                name="start_date"
                                className="form-control"
                                value={tmpFormValues.start_date}
                                onChange={(e) => {
                                    handleInputChange(e);
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
                                        handleInputChange(e);
                                    }}
                                    checked={
                                        tmpFormValues.monthday_selection_type ==
                                        "date"
                                    }
                                />

                                <span> Day </span>

                                <select
                                    name="month_date"
                                    className="choosen-select"
                                    onChange={(e) => {
                                        handleInputChange(e);
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
                                        value={tmpFormValues.month_occurrence}
                                        onChange={(e) => {
                                            handleInputChange(e);
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
                                        handleInputChange(e);
                                    }}
                                    checked={
                                        tmpFormValues.monthday_selection_type ==
                                        "weekday"
                                    }
                                />

                                <span> The </span>

                                <select
                                    name="weekday_occurrence"
                                    className="choosen-select"
                                    onChange={(e) => {
                                        handleInputChange(e);
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
                                        handleInputChange(e);
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
                                        value={tmpFormValues.month_occurrence}
                                        onChange={(e) => {
                                            handleInputChange(e);
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
                                    tmpFormValues.period == "w" &&
                                    tmpFormValues.cycle > 1
                                        ? "block"
                                        : "none",
                            }}
                        >
                            <Select
                                defaultValue={tmpFormValues.week_days}
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
                                    handleInputChange(e);
                                }}
                                style={{
                                    display:
                                        tmpFormValues.period == "w"
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
                        if (checkValidation()) {
                            handleSaveJobForm(
                                isAdd ? "" : index,
                                tmpFormValues
                            );
                            setIsOpen(false);
                        }
                    }}
                    className="btn btn-primary"
                >
                    Save
                </Button>
            </Modal.Footer>
        </Modal>
    );
});

export default JobModal;
