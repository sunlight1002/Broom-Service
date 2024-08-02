import { useEffect, useState, useMemo } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Select from "react-select";

// import Select from "react-select";

const initialValues = {
    service: "",
    name: "",
    type: "fixed",
    freq_name: "",
    frequency: "",
    fixed_price: "",
    rateperhour: "",
    other_title: "",
    template: "",
    cycle: "",
    period: "",
    address: "",
    weekdays: [],
    weekday_occurrence: "1",
    weekday: "sunday",
    month_occurrence: 1,
    month_date: 1,
    monthday_selection_type: "weekday",
    workers: [
        {
            jobHours: "",
        },
    ],
};

// const frequencyDays = [
//     { value: "sunday", label: "Sunday" },
//     { value: "monday", label: "Monday" },
//     { value: "tuesday", label: "Tuesday" },
//     { value: "wednesday", label: "Wednesday" },
//     { value: "thursday", label: "Thursday" },
//     { value: "friday", label: "Friday" },
//     { value: "saturday", label: "Saturday" },
// ];

// const monthDateArr = () => {
//     let array = [];
//     for (let i = 1; i <= 29; i++) {
//         array.push(i);
//     }
//     return array;
// };

export default function OfferServiceModal({
    setIsOpen,
    isOpen,
    addresses,
    services,
    frequencies,
    tmpFormValues,
    handleSaveForm,
    isAdd,
    editIndex,
}) {
    const alert = useAlert();
    const [offerServiceTmp, setOfferServiceTmp] = useState(
        isAdd ? initialValues : tmpFormValues
    );
    const [toggleOtherService, setToggleOtherService] = useState(false);
    const [toggleAirbnbService, setToggleAirbnbService] = useState(false);
    const [selectedSubServices, setSelectedSubServices] = useState(offerServiceTmp.subService || []);
    const [subData, setSubData] = useState([]);

    const adminlng = localStorage.getItem("admin-lng");

    const transformedSubData = subData.map(s => ({
        value: s.id,
        label: adminlng === "en" ? s.name_en : s.name_heb
    }));


    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };


    useEffect(() => {
        if (offerServiceTmp.service === '29') {
            setToggleAirbnbService(true);
            handleGetSubServices(29);
        } else if (offerServiceTmp.service === '10') {
            setToggleOtherService(true);
        }
    }, [offerServiceTmp.service]);

    const handleChangeWorkerCount = (e) => {
        const _noOfWorker = e.target.value > 0 ? e.target.value : 1;

        const _workerForms = Array.from(
            { length: _noOfWorker },
            () => initialValues.workers[0]
        );

        setOfferServiceTmp({ ...offerServiceTmp, workers: _workerForms });
    };

    const checkValidation = (_formValues) => {
        if (_formValues.address == "") {
            alert.error("The address is not selected");
            return false;
        }
        if (_formValues.service == "" || _formValues.service == 0) {
            alert.error("The service is not selected");
            return false;
        }

        let ot = document.querySelector("#other_title");

        if (_formValues.service == "10" && ot != undefined) {
            if (_formValues.other_title == "") {
                alert.error("Other title cannot be blank");
                return false;
            }
            _formValues.other_title =
                document.querySelector("#other_title").value;
        } else {
            _formValues.other_title = "";
        }

        !_formValues.type ? (_formValues.type = "fixed") : "";
        if (_formValues.type == "hourly") {
            if (_formValues.rateperhour == "") {
                alert.error("The rate per hour value is missing");
                return false;
            }
        } else {
            if (_formValues.fixed_price == "") {
                alert.error("The job price is missing");
                return false;
            }
        }

        if (_formValues.frequency == "" || _formValues.frequency == 0) {
            alert.error("The frequency is not selected");
            return false;
        } else {
            //     if (_formValues.cycle == "1") {
            //         if (
            //             ["w", "2w", "3w", "4w", "5w"].includes(
            //                 _formValues.period
            //             ) &&
            //             _formValues.weekday == ""
            //         ) {
            //             alert.error("The weekday is not selected");
            //             return false;
            //         } else if (
            //             _formValues.monthday_selection_type == "weekday" &&
            //             ["m", "2m", "3m", "6m", "y"].includes(_formValues.period) &&
            //             _formValues.weekday == ""
            //         ) {
            //             alert.error("The weekday is not selected");
            //             return false;
            //         }
            //         if (
            //             _formValues.monthday_selection_type == "date" &&
            //             ["m", "2m", "3m", "6m", "y"].includes(_formValues.period)
            //         ) {
            //             if (_formValues.month_date == "") {
            //                 alert.error("The month date is not selected");
            //                 return false;
            //             }
            //         }
            //     }
            //     if (
            //         _formValues.period == "w" &&
            //         _formValues.cycle != "0" &&
            //         _formValues.cycle != "1" &&
            //         _formValues.weekdays.length != _formValues.cycle
            //     ) {
            //         alert.error("The frequency week-days are invalid");
            //         return false;
            //     }
        }

        let workerIssue = true;
        for (let index = 0; index < _formValues.workers.length; index++) {
            const _worker = _formValues.workers[index];

            if (_worker.jobHours == "") {
                alert.error("The job hours value is missing");
                workerIssue = false;
                break;
            }
        }

        if (!workerIssue) {
            return workerIssue;
        }

        return true;
    };

    const handleGetSubServices = async (id) => {
        try {
            const res = await axios.get(`/api/admin/get-sub-services/${id}`, { headers });
            setSubData(res.data.subServices || []);
        } catch (error) {
            console.log("Error fetching sub-services:", error);
        }
    };


    const selectedOptions = transformedSubData.filter(option => selectedSubServices.includes(option.value));

    const handleSubServices = (selectedOptions) => {
        const selectedValues = selectedOptions ? selectedOptions.map(option => option.value) : [];
        setSelectedSubServices(selectedValues);

        setOfferServiceTmp((prevState) => ({
            ...prevState,
            sub_services: selectedValues
        }));
    };

    // console.log(offerServiceTmp);

    // useEffect(() => {
    //     handleGetSubServices();
    // }, [toggleAirbnbService])


    const handleWorkerForm = (index, tmpvalue) => {
        const _workers = offerServiceTmp.workers.map((worker, wIndex) => {
            if (wIndex == index) {
                return tmpvalue;
            }
            return worker;
        });

        setOfferServiceTmp({
            ...offerServiceTmp,
            workers: _workers,
        });
    };

    const handleInputChange = (e) => {
        let newFormValues = { ...offerServiceTmp };

        newFormValues[e.target.name] = e.target.value;
        if (e.target.name == "service") {
            const _selectedServiceOption =
                e.target.options[e.target.selectedIndex];

            newFormValues["name"] = _selectedServiceOption.getAttribute("name");
            newFormValues["template"] =
                _selectedServiceOption.getAttribute("template");
        }

        if (e.target.name == "frequency") {
            const _selectedFrequencyOption =
                e.target.options[e.target.selectedIndex];

            newFormValues["freq_name"] =
                _selectedFrequencyOption.getAttribute("name");
            newFormValues["cycle"] =
                _selectedFrequencyOption.getAttribute("cycle");
            newFormValues["period"] =
                _selectedFrequencyOption.getAttribute("period");
        }

        // if (e.target.name == "weekdays") {
        //     const _weekdays = e.target.option.map((i) => i.value);

        //     if (
        //         _weekdays.length > newFormValues["cycle"] &&
        //         newFormValues["cycle"] != 0
        //     ) {
        //         newFormValues["weekdays"] = [];
        //         window.alert(
        //             "You can select at most " +
        //                 newFormValues["cycle"] +
        //                 " day(s) for this frequency"
        //         );
        //     } else {
        //         newFormValues["weekdays"] = _weekdays;
        //     }
        // }

        setOfferServiceTmp({ ...newFormValues });
    };

    const handleSubmit = () => {
        let hasError = false;
        const valid = checkValidation(offerServiceTmp);
        if (!valid) {
            hasError = true;
        }
        if (!hasError) {
            handleSaveForm(isAdd ? -1 : editIndex, offerServiceTmp);
            setIsOpen(false);
        }
    };

    // const showWeekDayOption = useMemo(() => {
    //     return offerServiceTmp.period == "w" && offerServiceTmp.cycle == 1;
    // }, [offerServiceTmp.period, offerServiceTmp.cycle]);

    // const showWeekDayRadioOption = useMemo(() => {
    //     return ["2w", "3w", "4w", "5w", "m", "2m", "3m", "6m", "y"].includes(
    //         offerServiceTmp.period
    //     );
    // }, [offerServiceTmp.period]);

    // const showMonthOption = useMemo(() => {
    //     return ["2m", "3m", "6m", "y"].includes(offerServiceTmp.period);
    // }, [offerServiceTmp.period]);

    // const showMonthDateRadioOption = useMemo(() => {
    //     return ["m", "2m", "3m", "6m", "y"].includes(offerServiceTmp.period);
    // }, [offerServiceTmp.period]);

    // const weekDayOccurrenceOptions = useMemo(() => {
    //     let _occurrenceArr = [
    //         { value: 1, label: "first" },
    //         { value: 2, label: "second" },
    //         { value: 3, label: "third" },
    //         { value: 4, label: "fourth" },
    //     ];

    //     if (["2w", "3w", "4w", "5w"].includes(offerServiceTmp.period)) {
    //         const _weekCount = parseInt(offerServiceTmp.period.charAt(0));
    //         _occurrenceArr = _occurrenceArr.slice(0, _weekCount - 1);
    //     } else if (
    //         ["m", "2m", "3m", "6m", "y"].includes(offerServiceTmp.period)
    //     ) {
    //         _occurrenceArr = _occurrenceArr.slice(0, 3);
    //     }

    //     _occurrenceArr.push({ value: "last", label: "last" });

    //     return _occurrenceArr;
    // }, [offerServiceTmp.period]);

    // const monthOptions = useMemo(() => {
    //     let _monthArr = [];
    //     if (["2m", "3m", "6m"].includes(offerServiceTmp.period)) {
    //         _monthArr = [
    //             { value: 1, label: "first" },
    //             { value: 2, label: "second" },
    //             { value: 3, label: "third" },
    //             { value: 4, label: "fourth" },
    //             { value: 5, label: "fifth" },
    //             { value: 6, label: "sixth" },
    //             { value: 7, label: "seventh" },
    //             { value: 8, label: "eighth" },
    //             { value: 9, label: "ninth" },
    //             { value: 10, label: "tenth" },
    //             { value: 11, label: "eleventh" },
    //             { value: 12, label: "twelfth" },
    //         ];

    //         const _monthCount = parseInt(offerServiceTmp.period.charAt(0));
    //         _monthArr = _monthArr.slice(0, _monthCount);
    //     } else if (offerServiceTmp.period == "y") {
    //         _monthArr = [
    //             { value: 1, label: "January" },
    //             { value: 2, label: "February" },
    //             { value: 3, label: "March" },
    //             { value: 4, label: "April" },
    //             { value: 5, label: "May" },
    //             { value: 6, label: "June" },
    //             { value: 7, label: "July" },
    //             { value: 8, label: "August" },
    //             { value: 9, label: "September" },
    //             { value: 10, label: "October" },
    //             { value: 11, label: "November" },
    //             { value: 12, label: "December" },
    //         ];
    //     }

    //     return _monthArr;
    // }, [offerServiceTmp.period]);

    // const defaultWeekDays = () => {
    //     if (offerServiceTmp.weekdays) {
    //         return frequencyDays.filter((i) => {
    //             return offerServiceTmp.weekdays.includes(i.value);
    //         });
    //     } else {
    //         return [];
    //     }
    // };
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
                <Modal.Title>
                    {isAdd ? "Add Service" : "Edit Service"}
                </Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="d-flex align-items-center mb-3">
                    <label htmlFor="noOfWrkers">No of workers : </label>
                    <input
                        type="number"
                        min={1}
                        className="form-control w-25"
                        id="noOfWrkers"
                        defaultValue={offerServiceTmp.workers.length}
                        onChange={handleChangeWorkerCount}
                    />
                </div>

                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Property</label>
                            <select
                                className="form-control"
                                name="address"
                                value={offerServiceTmp.address || ""}
                                onChange={(e) => {
                                    handleInputChange(e);
                                }}
                            >
                                <option value="">--Please select--</option>
                                {addresses.map((address, i) => (
                                    <option value={address.id} key={i}>
                                        {address.address_name}
                                    </option>
                                ))}
                            </select>
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
                                value={offerServiceTmp.service || 0}
                                onChange={(e) => {
                                    handleInputChange(e);
                                    if (e.target.value === "10") {
                                        setToggleOtherService(true);
                                    } else if (e.target.value === '29') {
                                        setToggleAirbnbService(true);
                                        handleGetSubServices(29);
                                    } else {
                                        setToggleAirbnbService(false);
                                        setToggleOtherService(false);
                                    }
                                }}
                            >
                                <option value={0}> -- Please select --</option>
                                {services.map((s, i) => (
                                    <option
                                        name={s.name}
                                        template={s.template}
                                        value={s.id}
                                        key={i}
                                    >
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {toggleAirbnbService && (
                            <div className="form-group">
                                <label className="control-label">Sub-Service</label>
                                <Select
                                    value={selectedOptions}
                                    name="subService"
                                    isMulti
                                    options={transformedSubData}
                                    className="basic-multi-select"
                                    isClearable={true}
                                    placeholder="-- Please select --"
                                    classNamePrefix="select"
                                    onChange={(selectedOptions) => {
                                        handleSubServices(selectedOptions);
                                        const event = {
                                            target: {
                                                name: 'subService',
                                                value: selectedOptions ? selectedOptions.map(option => option.value) : []
                                            }
                                        };
                                        handleInputChange(event);
                                    }}
                                />
                            </div>
                        )}
                        {toggleOtherService && (
                            <div className="form-group">
                                <textarea
                                    type="text"
                                    name="other_title"
                                    id={`other_title`}
                                    placeholder="Service Title"
                                    className="form-control"
                                    value={offerServiceTmp.other_title || ""}
                                    onChange={(e) => handleInputChange(e)}
                                />
                            </div>
                        )}
                    </div>
                </div>

                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">Frequency</label>
                            <select
                                name="frequency"
                                className="form-control mb-2"
                                value={offerServiceTmp.frequency || 0}
                                onChange={(e) => handleInputChange(e)}
                            >
                                <option value={0}> -- Please select --</option>
                                {frequencies.map((s, i) => {
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

                        {/* <div
                        className="form-group"
                        style={{
                            display: showWeekDayOption ? "block" : "none",
                        }}
                        >
                        <div className="d-inline">
                            <span> On </span>

                            <select
                                name="weekday"
                                className="ml-2 choosen-select"
                                value={offerServiceTmp.weekday}
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
                        </div>
                    </div>

                    <div
                        className="form-group"
                        style={{
                            display: showMonthDateRadioOption
                                ? "block"
                                : "none",
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
                                    offerServiceTmp.monthday_selection_type ==
                                    "date"
                                }
                            />

                            <span> Day </span>

                            <select
                                name="month_date"
                                className="choosen-select"
                                value={offerServiceTmp.month_date}
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
                                    value={offerServiceTmp.month_occurrence}
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
                            display: showWeekDayRadioOption ? "block" : "none",
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
                                    offerServiceTmp.monthday_selection_type ==
                                    "weekday"
                                }
                            />

                            <span> The </span>

                            <select
                                name="weekday_occurrence"
                                className="choosen-select"
                                value={offerServiceTmp.weekday_occurrence}
                                onChange={(e) => {
                                    handleInputChange(e);
                                }}
                            >
                                {weekDayOccurrenceOptions.map((w, i) => {
                                    return (
                                        <option value={w.value} key={i}>
                                            {w.label}
                                        </option>
                                    );
                                })}
                            </select>

                            <select
                                name="weekday"
                                className="ml-2 choosen-select"
                                value={offerServiceTmp.weekday}
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
                                    value={offerServiceTmp.month_occurrence}
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
                                offerServiceTmp.period == "w" &&
                                offerServiceTmp.cycle > 1
                                    ? "block"
                                    : "none",
                        }}
                    >
                        <Select
                            defaultValue={defaultWeekDays}
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
                                    offerServiceTmp.period == "w"
                                        ? "block"
                                        : "none",
                            }}
                        />
                    </div> */}
                    </div>
                </div>

                <div className="row">
                    <div className="col-sm-4">
                        <div className="form-group">
                            <label className="control-label">Type</label>
                            <select
                                name="type"
                                className="form-control"
                                value={offerServiceTmp.type}
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
                            <label className="control-label">Price</label>
                            {offerServiceTmp.type !== "hourly" && (
                                <input
                                    type="number"
                                    name="fixed_price"
                                    value={offerServiceTmp.fixed_price || ""}
                                    onChange={(e) => handleInputChange(e)}
                                    className="form-control jobprice"
                                    required
                                    placeholder="Enter job price"
                                />
                            )}
                            {offerServiceTmp.type === "hourly" && (
                                <input
                                    type="text"
                                    name="rateperhour"
                                    value={offerServiceTmp.rateperhour || ""}
                                    onChange={(e) => handleInputChange(e)}
                                    className="form-control jobrate"
                                    required
                                    placeholder="Enter rate P/Hr"
                                />
                            )}
                        </div>
                    </div>
                </div>

                <div className="bg-dark text-white py-2 px-2 mb-2">
                    <strong>Workers</strong>
                </div>

                {offerServiceTmp.workers.map((worker, _index) => (
                    <WorkerForm
                        workerFormValues={worker}
                        handleTmpValue={handleWorkerForm}
                        index={_index}
                        key={_index}
                    />
                ))}
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

function WorkerForm({ workerFormValues, handleTmpValue, index }) {
    const handleInputChange = (e) => {
        let newFormValues = { ...workerFormValues };

        newFormValues[e.target.name] = e.target.value;

        handleTmpValue(index, newFormValues);
    };

    return (
        <div className="row">
            <div className="col-sm-auto text-right pt-2">
                <strong>Worker {index + 1}</strong>
            </div>

            <div className="col-sm-4">
                <div className="form-group">
                    <input
                        type="number"
                        name="jobHours"
                        value={workerFormValues.jobHours || ""}
                        onChange={(e) => handleInputChange(e)}
                        className="form-control jobhr"
                        required
                        placeholder="Enter job Hrs"
                    />
                </div>
            </div>
        </div>
    );
}
