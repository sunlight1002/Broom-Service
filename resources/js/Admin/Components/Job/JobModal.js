import { useEffect, useRef, useState } from "react";
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
    { value: "thursday", label: "Thrusday" },
    { value: "friday", label: "Friday" },
    { value: "saturday", label: "Saturday" },
];

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
                                {addresses.map((address) => (
                                    <option value={address.id}>
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
                        <div className="form-group">
                            <Select
                                name="days"
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
