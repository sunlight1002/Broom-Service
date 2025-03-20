import "flatpickr/dist/flatpickr.css";
import moment from "moment-timezone";
import React, { useCallback, useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import Flatpickr from "react-flatpickr";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import WorkerAvailabilityTable from "./WorkerAvailabilityTable";

import Loader from "../../../Components/common/Loader";
import {
    convertShiftsFormat,
    getAvailableSlots,
    getWorkerAvailabilities,
    getWorkersData,
} from "../../../Utils/job.utils";

export default function CreateJobCalender({
    services: clientServices,
    client,
    setSelectedService,
    setSelectedServiceIndex,
    selectedService,
    currentFilter,
    searchVal,
    distance,
    prevWorker,
    selectedContractIndex = 0,
    contracts = [],
    setSelectedContractIndex = () => { },
    workerFilter
}) {
    const navigate = useNavigate();
    const alert = useAlert();
    const [workerAvailabilities, setWorkerAvailabilities] = useState([]);
    const [selectedHours, setSelectedHours] = useState([]);
    const [updatedJobs, setUpdatedJobs] = useState([]);
    const [AllWorkers, setAllWorkers] = useState([]);
    const [calendarStartDate, setCalendarStartDate] = useState(null);
    const [calendarEndDate, setCalendarEndDate] = useState(null);
    const [days, setDays] = useState([]);
    const headers = {
        Accept: "application/json, text/plain, /",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const [services, setServices] = useState(clientServices);
    const [customDateRange, setCustomDateRange] = useState([]);
    const [loading, setLoading] = useState(false);
    const [hasFetched, setHasFetched] = useState(false);
    const [serviceIndex, setServiceIndex] = useState(null);

    const generateWeek = (startDate) => {
        let week = [];
        let today = moment().startOf("day"); // Get the current date at the start of the day
        days.forEach((d) => {
            let day = moment(startDate).add(d, "days");
            if (day.isSameOrAfter(today)) {
                // Check if the day is greater than or equal to today
                week.push(day.format("YYYY-MM-DD"));
            }
        });
        return week;
    };

    const sundayOfCurrentWeek = moment().startOf("week");

    let week = generateWeek(sundayOfCurrentWeek);
    let nextweek = generateWeek(sundayOfCurrentWeek.add(1, "weeks"));
    let nextnextweek = generateWeek(sundayOfCurrentWeek.add(1, "weeks"));

    useEffect(() => {
        setServices(clientServices);
    }, [clientServices]);

    const getTime = () => {
        axios.get(`/api/admin/get-time`, { headers }).then((res) => {
            if (res.data.data) {
                let ar = JSON.parse(res.data.data.days);
                setDays(ar);
            }
        });
    };
    useEffect(() => {
        getTime();
    }, []);


    const handleContract = (index) => {
        setSelectedContractIndex(index);
    };

    const handleServices = (index) => {
        setServiceIndex(index);
        setLoading(true);
        setSelectedServiceIndex(index);

        const _service = services[index];

        setSelectedService(_service);

        if (!_service) return;

        const hours = [];
        if (_service?.workers && _service?.workers.length > 0) {
            const iterations =
                parseInt(_service?.cycle) > 0 ? parseInt(_service?.cycle) : 1;
            for (let i = 0; i < iterations; i++) {
                _service?.workers?.forEach((worker) => {
                    hours.push({
                        jobHours: worker?.jobHours,
                        slots: null,
                        formattedSlots: null,
                    });
                });
            }
        }


        setSelectedHours(hours);
        let _calendarStartDate = calendarStartDate;
        let _calendarEndDate = calendarEndDate;
        if (_calendarStartDate == null && week.length > 0) {
            _calendarStartDate = week[0];
            _calendarEndDate = week[week.length - 1];
            setCalendarStartDate(week[0]);
            setCalendarEndDate(week[week.length - 1]);
        }
        getWorkers(_service, _calendarStartDate, _calendarEndDate);
        $("#edit-work-time").modal("hide");
        setLoading(false);
    };

    const getWorkers = useCallback(
        async (_service, _calendarStartDate, _calendarEndDate) => {
            // if (hasFetched) return;

            // setHasFetched(true);
            try {
                setLoading(true);
                const res = await axios.get(`/api/admin/all-workers`, {
                    headers,
                    params: {
                        filter: true,
                        start_date: calendarStartDate ?? _calendarStartDate,
                        end_date: calendarEndDate ?? _calendarEndDate,
                        distance: distance,
                        service_id: _service.service,
                        has_cat: _service.address.is_cat_avail,
                        has_dog: _service.address.is_dog_avail,
                        prefer_type: _service.address.prefer_type,
                        ignore_worker_ids:
                            _service.address.not_allowed_worker_ids,
                        client_property_id: _service.address.id,
                        is_freelancer: _service?.is_freelancer ? true : false,
                    },
                });
                const workers = res.data.workers;

                setAllWorkers(workers);
                let WorkerAvailability = getWorkerAvailabilities(workers);

                console.timeEnd("get");
                setWorkerAvailabilities(WorkerAvailability);
            } catch (err) {
                alert.error("Failed to fetch workers");
            } finally {
                setLoading(false);
            }
        },
        [distance, hasFetched, calendarStartDate, calendarEndDate]
    );

    useEffect(() => {
        handleServices(serviceIndex);
    }, [serviceIndex, distance]);

    useEffect(() => {
        handleServices(serviceIndex);
    }, [serviceIndex, calendarStartDate, calendarEndDate])

    const submitForm = useCallback(
        async (_data) => {
            try {
                setLoading(true);
                let viewbtn = document.querySelectorAll(".viewBtn");
                const formdata = {
                    workers: _data,
                    service_id: selectedService.service,
                    contract_id: selectedService.contract_id,
                    prevWorker: prevWorker,
                    updatedJobs: updatedJobs,
                    selectedService: selectedService
                };
                viewbtn[0].setAttribute("disabled", true);
                viewbtn[0].value = "please wait ...";
                await axios.post(`/api/admin/create-job`, formdata, {
                    headers,
                });
                alert.success("Job created successfully");
                setTimeout(() => navigate("/admin/jobs"), 1000);
            } catch (error) {
                Swal.fire({
                    title: "Error!",
                    text: error.response?.data?.message,
                    icon: "error",
                });
            } finally {
                setLoading(false);
            }
        },
        [selectedService, updatedJobs, navigate]
    );

    const handleSubmit = () => {
        if (selectedHours) {
            const data = [];

            selectedHours.forEach((worker, index) => {
                // Loop through the worker's formatted slots
                worker?.formattedSlots?.forEach((slots) => {
                    data.push(slots);
                });
            });

            // You can now use the 'data' array which contains the updated shift information
            if (data.length > 0) {
                const _getWorkersData = getWorkersData(selectedHours);

                if (selectedHours.length != _getWorkersData.length) {
                    Swal.fire({
                        title: "Are you sure?",
                        text: "All frequency dates not selected!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Yes, sure!",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitForm(data);
                        }
                    });
                } else {
                    submitForm(data);
                }
            } else {
                let viewbtn = document.querySelectorAll(".viewBtn");
                viewbtn[0].removeAttribute("disabled");
                viewbtn[0].value = "View Job";
                alert.error("Please Select the Workers");
            }
        }
    };


    const changeShift = (w_id, date, e) => {
        const selectedSlotTimes = new Set(); // Track already selected slot times

        const promises = selectedHours.map(async (worker, index) => {
            if (worker.slots == null) {
                const slots = await getAvailableSlots(
                    workerAvailabilities,
                    w_id,
                    date,
                    e,
                    worker.jobHours,
                    false,
                    alert,
                    setWorkerAvailabilities,
                    setUpdatedJobs
                );
                // Filter out slots that have already been selected
                const filteredSlots = slots.filter(
                    (slot) => !selectedSlotTimes.has(slot.time.time)
                );

                // Add current slot times to the set
                filteredSlots.forEach((slot) =>
                    selectedSlotTimes.add(slot.time.time)
                );
                return {
                    jobHours: worker.jobHours,
                    slots: filteredSlots.length > 0 ? filteredSlots : null,
                    formattedSlots:
                        filteredSlots.length > 0
                            ? convertShiftsFormat(filteredSlots)
                            : null,
                };
            }
            return worker;
        });

        // Wait for all promises to resolve
        Promise.all(promises).then((updatedData) => {
            var isExist = selectedHours.filter((w) => w.slots == null);
            if (!isExist.length) {
                alert.error(
                    "Other slots have already been selected. Please deselect and reselect."
                );
            }
            setSelectedHours(updatedData);
        });
    };

    const removeShift = (w_id, date, shift) => {
        setSelectedHours((data) => {
            return data.map((worker) => {
                if (worker.slots != null) {
                    const slot = worker.slots.find((s) => {
                        return (
                            s.workerId == w_id &&
                            s.date == date &&
                            shift.time == s.time.time
                        );
                    });
                    if (slot) {
                        return {
                            jobHours: worker.jobHours,
                            slots: null,
                            formattedSlots: null,
                        };
                    }
                }
                return worker;
            });
        });
    };

    const hasActive = (w_id, date, shift) => {
        if (selectedHours) {
            const filtered = selectedHours.find((worker) => {
                if (worker.slots != null) {
                    const slot = worker.slots.find((s) => {
                        return (
                            s.workerId == w_id &&
                            s.date == date &&
                            shift.time == s.time.time
                        );
                    });
                    if (slot) {
                        return {
                            jobHours: worker.jobHours,
                            slots: null,
                            formattedSlots: null,
                        };
                    }
                }
                return false;
            });
            if (filtered) {
                return true;
            }
        }

        return false;
    };

    useEffect(() => {
        setHasFetched(false);
        switch (currentFilter) {
            case "Current Week":
                if (week.length > 0) {
                    setAllWorkers([]);
                    setWorkerAvailabilities([]);
                    setCalendarStartDate(week[0]);
                    setCalendarEndDate(week[week.length - 1]);
                }
                break;

            case "Next Week":
                if (nextweek.length > 0) {
                    setAllWorkers([]);
                    setWorkerAvailabilities([]);
                    setCalendarStartDate(nextweek[0]);
                    setCalendarEndDate(nextweek[nextweek.length - 1]);
                }
                break;

            case "Next Next Week":
                if (nextnextweek.length > 0) {
                    setAllWorkers([]);
                    setWorkerAvailabilities([]);
                    setCalendarStartDate(nextnextweek[0]);
                    setCalendarEndDate(nextnextweek[nextnextweek.length - 1]);
                }
                break;

            case "Custom":
                if (customDateRange.length > 0) {
                    setAllWorkers([]);
                    setWorkerAvailabilities([]);
                    setCalendarStartDate(customDateRange[0]);
                    setCalendarEndDate(customDateRange[customDateRange.length - 1]);
                }
                break;

            default:
                if (week.length > 0) {
                    setAllWorkers([]);
                    setWorkerAvailabilities([]);
                    setCalendarStartDate(week[0]);
                    setCalendarEndDate(week[week.length - 1]);
                }
                break;
        }
    }, [currentFilter, customDateRange]);


    const filteredServices = services?.filter(service => {
        if (service?.template === "airbnb") {
            return service?.sub_services?.id === selectedService?.sub_services?.id;
        }
        return service?.service === selectedService?.service;
    });

    let filteredServiceObj = Object.assign({}, filteredServices[0]);

    return (
        <>
            {workerAvailabilities.length === 0 ? (
                <Loader />
            ) : (
                <div className="tab-content" style={{ background: "#fff" }}>
                    {currentFilter === "Current Week" && (
                        <div
                            id="tab-worker-availability"
                            className="table-responsive active show tab-pane"
                            role="tab-panel"
                            aria-labelledby="current-job"
                        >
                            <div className="crt-jb-table-scrollable">
                                <WorkerAvailabilityTable
                                    workerAvailabilities={workerAvailabilities}
                                    week={week}
                                    AllWorkers={AllWorkers}
                                    hasActive={hasActive}
                                    changeShift={changeShift}
                                    removeShift={removeShift}
                                    selectedHours={selectedHours}
                                    searchKeyword={searchVal}
                                    distance={distance}
                                    workerFilter={workerFilter}
                                />
                            </div>
                        </div>
                    )}
                    {currentFilter === "Next Week" && (
                        <div
                            id="tab-current-job"
                            className="table-responsive active show tab-pane"
                            role="tab-panel"
                            aria-labelledby="current-job"
                        >
                            <div className="crt-jb-table-scrollable">
                                <WorkerAvailabilityTable
                                    workerAvailabilities={workerAvailabilities}
                                    week={nextweek}
                                    AllWorkers={AllWorkers}
                                    hasActive={hasActive}
                                    changeShift={changeShift}
                                    removeShift={removeShift}
                                    selectedHours={selectedHours}
                                    searchKeyword={searchVal}
                                    distance={distance}
                                    workerFilter={workerFilter}
                                />
                            </div>
                        </div>
                    )}
                    {currentFilter === "Next Next Week" && (
                        <div
                            id="tab-current-next-job"
                            className="table-responsive active show tab-pane"
                            role="tab-panel"
                            aria-labelledby="current-job"
                        >
                            <div className="crt-jb-table-scrollable">
                                <WorkerAvailabilityTable
                                    workerAvailabilities={workerAvailabilities}
                                    week={nextnextweek}
                                    AllWorkers={AllWorkers}
                                    hasActive={hasActive}
                                    changeShift={changeShift}
                                    removeShift={removeShift}
                                    selectedHours={selectedHours}
                                    searchKeyword={searchVal}
                                    distance={distance}
                                    workerFilter={workerFilter}
                                />
                            </div>
                        </div>
                    )}
                    {currentFilter === "Custom" && (
                        <div
                            id="tab-current-next-job"
                            className="table-responsive active show tab-pane"
                            role="tab-panel"
                            aria-labelledby="current-job"
                        >
                            <div className="form-group">
                                <label className="control-label">
                                    Select Date Range
                                </label>
                                <Flatpickr
                                    name="date"
                                    className="form-control"
                                    onChange={(
                                        selectedDates,
                                        dateStr,
                                        instance
                                    ) => {
                                        let start = moment(selectedDates[0]);
                                        let end = moment(selectedDates[1]);
                                        const datesArray = [];

                                        for (
                                            let date = start.clone();
                                            date.isSameOrBefore(end);
                                            date.add(1, "day")
                                        ) {
                                            datesArray.push(
                                                date.format("YYYY-MM-DD")
                                            );
                                        }
                                        setCustomDateRange(datesArray);
                                    }}
                                    options={{
                                        disableMobile: true,
                                        minDate: moment(
                                            nextnextweek[
                                            nextnextweek.length - 1
                                            ]
                                        )
                                            .add(1, "days")
                                            .format("YYYY-MM-DD"),
                                        mode: "range",
                                    }}
                                />
                            </div>
                            {customDateRange.length > 0 && (
                                <div className="crt-jb-table-scrollable">
                                    <WorkerAvailabilityTable
                                        workerAvailabilities={
                                            workerAvailabilities
                                        }
                                        week={customDateRange}
                                        AllWorkers={AllWorkers}
                                        hasActive={hasActive}
                                        changeShift={changeShift}
                                        removeShift={removeShift}
                                        selectedHours={selectedHours}
                                        searchKeyword={searchVal}
                                        distance={distance}
                                        workerFilter={workerFilter}
                                    />
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}

            <div className="form-group text-center mt-3">
                <input
                    type="button"
                    value="View Job"
                    className="btn navyblue viewBtn"
                    data-toggle="modal"
                    data-target="#exampleModal"
                />
            </div>

            {/* modals */}
            <div
                className="modal fade"
                id="exampleModal"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog modal-lg" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">
                                View Job
                            </h5>
                            <button
                                type="button"
                                className="close"
                                data-dismiss="modal"
                                aria-label="Close"
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="table-responsive">
                                    <table className="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th scope="col">Client</th>
                                                <th scope="col">Service</th>
                                                <th scope="col">Frequency</th>
                                                <th scope="col">
                                                    Time to Complete
                                                </th>
                                                <th scope="col">Property</th>
                                                <th scope="col">
                                                    Gender preference
                                                </th>
                                                <th scope="col">Pet animals</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    {`${client.firstname} ${client.lastname}`}
                                                </td>
                                                <td>
                                                    {" "}
                                                    {
                                                        filteredServiceObj && (
                                                            <>
                                                                <p>
                                                                    {filteredServiceObj.template === "others"
                                                                        ? filteredServiceObj.other_title
                                                                        : client?.lng === 'heb'
                                                                            ? filteredServiceObj.service_name_heb
                                                                            : filteredServiceObj.service_name_en}
                                                                </p>
                                                                {filteredServiceObj.template === "airbnb" && (
                                                                    <p className="mt-0">
                                                                        ({client?.lng === 'heb'
                                                                            ? filteredServiceObj.sub_services?.subServices?.name_heb
                                                                            : filteredServiceObj.sub_services?.subServices?.name_en})
                                                                    </p>
                                                                )}
                                                            </>
                                                        )
                                                    }
                                                </td>
                                                <td>
                                                    {filteredServiceObj && (
                                                        <p>{filteredServiceObj.freq_name}</p>
                                                    )}
                                                </td>
                                                <td>
                                                    {filteredServiceObj && filteredServiceObj?.workers?.map((worker, i) => (
                                                        <p
                                                            className={`services-${filteredServiceObj.service}-${filteredServiceObj.contract_id}`}
                                                            key={i}
                                                        >
                                                            {worker.jobHours} hours (Worker {i + 1})
                                                        </p>
                                                    ))}
                                                </td>
                                                <td>
                                                    {filteredServiceObj && (
                                                        <p>
                                                            {filteredServiceObj.template === "airbnb"
                                                                ? filteredServiceObj.sub_services?.address_name || "NA"
                                                                : filteredServiceObj.address?.address_name || "NA"}
                                                        </p>
                                                    )}
                                                </td>
                                                <td style={{ textTransform: "capitalize" }}>
                                                    {filteredServiceObj && (
                                                        <p>
                                                            {filteredServiceObj.template === "airbnb"
                                                                ? filteredServiceObj.sub_services?.fulladdress?.prefer_type || "NA"
                                                                : filteredServiceObj.address?.prefer_type || "NA"}
                                                        </p>
                                                    )}
                                                </td>
                                                <td>
                                                    {filteredServiceObj && (
                                                        <p>
                                                            {
                                                                (() => {
                                                                    const isCatAvail = filteredServiceObj.template === "airbnb"
                                                                        ? filteredServiceObj.sub_services?.fulladdress?.is_cat_avail
                                                                        : filteredServiceObj.address?.is_cat_avail;

                                                                    const isDogAvail = filteredServiceObj.template === "airbnb"
                                                                        ? filteredServiceObj.sub_services?.fulladdress?.is_dog_avail
                                                                        : filteredServiceObj.address?.is_dog_avail;

                                                                    if (isCatAvail && isDogAvail) {
                                                                        return "Cat and Dog";
                                                                    } else if (isCatAvail) {
                                                                        return "Cat";
                                                                    } else if (isDogAvail) {
                                                                        return "Dog";
                                                                    }
                                                                    return "NA";
                                                                })()
                                                            }
                                                        </p>
                                                    )}
                                                </td>
                                            </tr>
                                        </tbody>

                                    </table>
                                </div>
                                <div className="table-responsive">
                                    {selectedHours.length > 0 ? (
                                        <table className="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Worker</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Shifts</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {selectedHours &&
                                                    selectedHours?.map((d, i) =>
                                                        d?.formattedSlots?.map(
                                                            (slot, j) => {
                                                                return (
                                                                    <tr key={j}>
                                                                        <td>
                                                                            {
                                                                                slot.worker_name
                                                                            }
                                                                        </td>
                                                                        <td>
                                                                            {
                                                                                slot.date
                                                                            }
                                                                        </td>
                                                                        <td>
                                                                            {
                                                                                slot.shifts
                                                                            }
                                                                        </td>
                                                                    </tr>
                                                                );
                                                            }
                                                        )
                                                    )}
                                            </tbody>
                                        </table>
                                    ) : (
                                        ""
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button
                                type="button"
                                className="btn btn-secondary closeb"
                                data-dismiss="modal"
                            >
                                Close
                            </button>
                            <button
                                type="button"
                                onClick={handleSubmit}
                                className="btn btn-primary"
                                data-dismiss="modal"
                            >
                                Save and Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div
                className="modal fade"
                id="edit-work-time"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">
                                Select Service
                            </h5>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                {
                                    contracts && contracts.length > 0 ? (
                                        <div className="col-sm-12 mb-2">
                                            <label className="control-label">
                                                Contracts
                                            </label>
                                            <select
                                                onChange={(e) =>
                                                    handleContract(e.target.value)
                                                }
                                                className="form-control"
                                            >
                                                <option value="">
                                                    --- Please Select Offer ID ---
                                                </option>
                                                {contracts &&
                                                    contracts.map((item, index) => {
                                                        return (
                                                            <option
                                                                value={item.id}
                                                                key={index}
                                                            >
                                                                Offer ID: {item.offer_id}
                                                            </option>
                                                        );
                                                    })}
                                            </select>
                                        </div>
                                    ) : null
                                }
                                <div className="col-sm-12">
                                    <label className="control-label">
                                        Services
                                    </label>
                                    <select
                                        onChange={(e) =>
                                            handleServices(e.target.value)
                                        }
                                        className="form-control"
                                    >
                                        <option value="">
                                            --- Please Select Service ---
                                        </option>
                                        {services &&
                                            services.map((item, index) => {
                                                if (contracts && contracts.length > 0 && (item.contract_id == selectedContractIndex)) {
                                                    return (
                                                        <option
                                                            value={index}
                                                            key={index}
                                                        >
                                                            {item.template != "others"
                                                                ? item.name
                                                                : item.other_title}
                                                        </option>
                                                    );
                                                } else if (!contracts || contracts.length == 0) {
                                                    return (
                                                        <option
                                                            value={index}
                                                            key={index}
                                                        >
                                                            {item.template != "others"
                                                                ? item.name
                                                                : item.other_title}
                                                        </option>
                                                    );
                                                }
                                            })}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

const FilterButtons = ({
    text,
    className,
    selectedFilter,
    setselectedFilter,
    onClick,
}) => (
    <button
        className={`btn btn-sm border ${className}`}
        type="button"
        style={
            selectedFilter !== text
                ? {
                    background: "#EDF1F6",
                    color: "#2c3f51",
                    borderRadius: "6px",
                }
                : {
                    background: "#2c3f51",
                    color: "white",
                    borderRadius: "6px",
                }
        }
        onClick={() => {
            onClick?.();
            setselectedFilter(text);
        }}
    >
        {text}
    </button>
);
