import "flatpickr/dist/flatpickr.css";
import moment from "moment-timezone";
import React, { useEffect, useRef, useState } from "react";
import { useAlert } from "react-alert";
import Flatpickr from "react-flatpickr";
import { useNavigate } from "react-router-dom";
import Swal from "sweetalert2";
import WorkerAvailabilityTable from "./WorkerAvailabilityTable";

import FullPageLoader from "../../../Components/common/FullPageLoader";
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
    selectedService
}) {
    const navigate = useNavigate();
    const alert = useAlert();
    const [workerAvailabilities, setWorkerAvailabilities] = useState([]);
    const [selectedHours, setSelectedHours] = useState([]);
    const [setselectedSlots, setSetselectedSlots] = useState([])
    const [updatedJobs, setUpdatedJobs] = useState([]);
    const [AllWorkers, setAllWorkers] = useState([]);
    const [days, setDays] = useState([]);
    const [currentFilter, setcurrentFilter] = useState("Current Week");
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    let isPrevWorker = useRef();
    const [services, setServices] = useState(clientServices);
    const [customDateRange, setCustomDateRange] = useState([]);
    const [searchVal, setSearchVal] = useState("");
    const [loading, setLoading] = useState(false);

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

    console.log(selectedHours);
    

    const handleServices = (index) => {
        setLoading(true)
        setSelectedServiceIndex(index);

        const _service = services[index];
        setSelectedService(_service);

        if (!_service) return;

        const hours = [];
        if (_service?.workers && _service?.workers.length > 0) {
            const iterations = parseInt(_service?.cycle) > 0 ? parseInt(_service?.cycle) : 1;
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
        getWorkers(_service);
        $("#edit-work-time").modal("hide");
        setLoading(false)
    };

    const getWorkers = (_service) => {
        setLoading(true);
        axios
            .get(`/api/admin/all-workers`, {
                headers,
                params: {
                    filter: true,
                    service_id: _service.service,
                    has_cat: _service.address.is_cat_avail,
                    has_dog: _service.address.is_dog_avail,
                    prefer_type: _service.address.prefer_type,
                    ignore_worker_ids: _service.address.not_allowed_worker_ids,
                },
            })
            .then((res) => {
                setAllWorkers(res.data.workers);

                const workerAvailityData = getWorkerAvailabilities(res?.data?.workers);

                setWorkerAvailabilities(workerAvailityData);

                setLoading(false);
            })
            .catch((err) => {
                setLoading(false);
            });
    };

    const submitForm = (_data) => {
        setLoading(true)
        let viewbtn = document.querySelectorAll(".viewBtn");
        let formdata = {
            workers: _data,
            service_id: selectedService.service,
            contract_id: selectedService.contract_id,
            prevWorker: isPrevWorker.current.checked,
            updatedJobs: updatedJobs,
        };

        viewbtn[0].setAttribute("disabled", true);
        viewbtn[0].value = "please wait ...";

        axios
            .post(`/api/admin/create-job`, formdata, {
                headers,
            })
            .then((res) => {
                setLoading(false)
                alert.success(res.data.message);
                setTimeout(() => {
                    navigate("/admin/jobs");
                }, 1000);
            })
            .catch((e) => {
                setLoading(false)
                Swal.fire({
                    title: "Error!",
                    text: e.response?.data?.message,
                    icon: "error",
                });
            });
    };

    const handleSubmit = () => {
        if (selectedHours) {
            // const unfilled = selectedHours.find((worker) => {
            //     return worker.slots == null;
            // });

            // if (unfilled) {
            //     alert.error("Please select all workers.");
            //     return false;
            // }

            const data = [];
            selectedHours.forEach((worker, index) => {
                worker?.formattedSlots?.forEach((slots) => {
                    data.push(slots);
                });
            });

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
                const filteredSlots = slots.filter(slot => !selectedSlotTimes.has(slot.time.time));

                // Add current slot times to the set
                filteredSlots.forEach(slot => selectedSlotTimes.add(slot.time.time));

                return {
                    jobHours: worker.jobHours,
                    slots: filteredSlots.length > 0 ? filteredSlots : null,
                    formattedSlots:
                        filteredSlots.length > 0 ? convertShiftsFormat(filteredSlots) : null,
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

    return (
        <>
            <div className="row mb-3">
                <div className="col-sm-12" style={{ rowGap: "0.5rem" }}>
                    <div className="d-flex align-items-center flex-wrap float-left">
                        <div className="mr-3" style={{ fontWeight: "bold" }}>
                            Worker Availability
                        </div>
                        <FilterButtons
                            text="Current Week"
                            className="px-3 mr-2 mb-2"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />

                        <FilterButtons
                            text="Next Week"
                            className="px-3 mr-2 mb-2"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />

                        <FilterButtons
                            text="Next Next Week"
                            className="px-3 mr-2 mb-2"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />

                        <FilterButtons
                            text="Custom"
                            className="px-3 mr-2 mb-2"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />
                    </div>

                    <div className="float-right" style={{ width: "150px" }}>
                        <input
                            type="text"
                            className="form-control form-control-sm"
                            placeholder="Search"
                            onChange={(e) => {
                                setSearchVal(e.target.value);
                            }}
                        />
                    </div>
                </div>
                <div className="col-sm-12 mt-2">
                    <div className="form-check">
                        <label className="form-check-label">
                            <input
                                ref={isPrevWorker}
                                type="checkbox"
                                className="form-check-input"
                                name={"is_keep_prev_worker"}
                            />
                            Keep previous worker
                        </label>
                    </div>
                </div>
            </div>

            {workerAvailabilities.length === 0 ? <Loader /> : (
                <div className="tab-content" style={{ background: "#fff" }}>
                    <div
                        style={{
                            display:
                                currentFilter === "Current Week" ? "block" : "none",
                        }}
                        id="tab-worker-availability"
                        className="tab-pane active show  table-responsive"
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
                            />
                        </div>
                    </div>

                    <div
                        style={{
                            display:
                                currentFilter === "Next Week" ? "block" : "none",
                        }}
                        id="tab-current-job"
                        className="tab-pane"
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
                            />
                        </div>
                    </div>
                    <div
                        style={{
                            display:
                                currentFilter === "Next Next Week"
                                    ? "block"
                                    : "none",
                        }}
                        id="tab-current-next-job"
                        className="tab-pane"
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
                            />
                        </div>
                    </div>
                    <div
                        style={{
                            display: currentFilter === "Custom" ? "block" : "none",
                        }}
                        id="tab-current-next-job"
                        className="tab-pane"
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
                                onChange={(selectedDates, dateStr, instance) => {
                                    let start = moment(selectedDates[0]);
                                    let end = moment(selectedDates[1]);
                                    const datesArray = [];

                                    for (
                                        let date = start.clone();
                                        date.isSameOrBefore(end);
                                        date.add(1, "day")
                                    ) {
                                        datesArray.push(date.format("YYYY-MM-DD"));
                                    }
                                    setCustomDateRange(datesArray);
                                }}
                                options={{
                                    disableMobile: true,
                                    minDate: moment(
                                        nextnextweek[nextnextweek.length - 1]
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
                                    workerAvailabilities={workerAvailabilities}
                                    week={customDateRange}
                                    AllWorkers={AllWorkers}
                                    hasActive={hasActive}
                                    changeShift={changeShift}
                                    removeShift={removeShift}
                                    selectedHours={selectedHours}
                                    searchKeyword={searchVal}
                                />
                            </div>
                        )}
                    </div>
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
                                                    {services.map(
                                                        (item, index) => {
                                                            if (
                                                                item.service ==
                                                                "10"
                                                            )
                                                                return (
                                                                    <p
                                                                        key={
                                                                            index
                                                                        }
                                                                    >
                                                                        {
                                                                            item.other_title
                                                                        }
                                                                    </p>
                                                                );
                                                            else
                                                                return (
                                                                    <p
                                                                        key={
                                                                            index
                                                                        }
                                                                    >
                                                                        {
                                                                            item.name
                                                                        }
                                                                    </p>
                                                                );
                                                        }
                                                    )}
                                                </td>
                                                <td>
                                                    {services.map(
                                                        (item, index) => (
                                                            <p key={index}>
                                                                {item.freq_name}
                                                            </p>
                                                        )
                                                    )}
                                                </td>
                                                <td>
                                                    {services.map(
                                                        (item, index) => (
                                                            <div key={index}>
                                                                {item?.workers?.map(
                                                                    (
                                                                        worker,
                                                                        i
                                                                    ) => (
                                                                        <p
                                                                            className={`services-${item.service}-${item.contract_id}`}
                                                                            key={
                                                                                i
                                                                            }
                                                                        >
                                                                            {
                                                                                worker.jobHours
                                                                            }{" "}
                                                                            hours
                                                                            (Worker{" "}
                                                                            {i +
                                                                                1}
                                                                            )
                                                                        </p>
                                                                    )
                                                                )}
                                                            </div>
                                                        )
                                                    )}
                                                </td>
                                                <td>
                                                    {services.map(
                                                        (item, index) => (
                                                            <p key={index}>
                                                                {
                                                                    item
                                                                        ?.address
                                                                        ?.address_name
                                                                }
                                                            </p>
                                                        )
                                                    )}
                                                </td>
                                                <td
                                                    style={{
                                                        textTransform:
                                                            "capitalize",
                                                    }}
                                                >
                                                    {services.map(
                                                        (item, index) => (
                                                            <p key={index}>
                                                                {
                                                                    item
                                                                        ?.address
                                                                        ?.prefer_type
                                                                }
                                                            </p>
                                                        )
                                                    )}
                                                </td>
                                                <td>
                                                    {services.map(
                                                        (item, index) => (
                                                            <p key={index}>
                                                                {item?.address
                                                                    ?.is_cat_avail
                                                                    ? "Cat ,"
                                                                    : item
                                                                        ?.address
                                                                        ?.is_dog_avail
                                                                        ? "Dog"
                                                                        : !item
                                                                            ?.address
                                                                            ?.is_cat_avail &&
                                                                            !item
                                                                                ?.address
                                                                                ?.is_dog_avail
                                                                            ? "NA"
                                                                            : ""}
                                                            </p>
                                                        )
                                                    )}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div className="table-responsive">
                                    {getWorkersData(selectedHours).length >
                                        0 ? (
                                        <table className="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Worker</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Shifts</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {getWorkersData(
                                                    selectedHours
                                                ) &&
                                                    getWorkersData(
                                                        selectedHours
                                                    ).map((d, i) => (
                                                        <tr key={i}>
                                                            <td>
                                                                {d.worker_name}
                                                            </td>
                                                            <td>{d.date}</td>
                                                            <td>{d.shifts}</td>
                                                        </tr>
                                                    ))}
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
                                        {services && services.map((item, index) => {
                                            return (
                                                <option
                                                    value={index}
                                                    key={index}
                                                >
                                                    {item.service != "10"
                                                        ? item.name
                                                        : item.other_title}
                                                </option>
                                            );
                                        })}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading}/>}
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
