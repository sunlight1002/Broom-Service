import React, { useState, useEffect, useRef, useMemo } from "react";
import moment from "moment-timezone";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import { Base64 } from "js-base64";

import {
    convertShiftsFormat,
    getAvailableSlots,
    getWorkerAvailabilities,
    getWorkersData,
} from "../../../Utils/job.utils";
import WorkerAvailabilityTable from "../../../Admin/Components/Job/WorkerAvailabilityTable";
import { useTranslation } from "react-i18next";

export default function ChangeScheduleCalender({ job }) {
    const [workerAvailabilities, setWorkerAvailabilities] = useState([]);
    const [selectedHours, setSelectedHours] = useState([]);
    const [updatedJobs, setUpdatedJobs] = useState([]);
    const [AllWorkers, setAllWorkers] = useState([]);
    const [days, setDays] = useState([]);
    const [formValues, setFormValues] = useState({
        repeatancy: "one_time",
        until_date: null,
    });
    const [minUntilDate, setMinUntilDate] = useState(null);
    const [currentFilter, setcurrentFilter] = useState("Current Week");
    const [customDateRange, setCustomDateRange] = useState([]);
    const [searchVal, setSearchVal] = useState("");
    const [loading, setLoading] = useState(false);

    const params = useParams();
    const navigate = useNavigate();
    const alert = useAlert();
    const { t } = useTranslation();

    const jobId = Base64.decode(params.id);

    const flatpickrRef = useRef(null);
    let isSameWorker = useRef();

    const headers = {
        Accept: "application/json, text/plain, /",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    const getTime = () => {
        axios.get(`/api/client/get-time`, { headers }).then((res) => {
            if (res.data.data) {
                
                let ar = JSON.parse(res.data.data.days);
                setDays(ar);
            }
        });
    };

    const jobHours = useMemo(
        () => (job.jobservice.duration_minutes / 4) / 60 ,
        [job.jobservice.duration_minutes]
    );


    const getWorkers = () => {
        axios
            .get(`/api/client/workers`, {
                headers,
                params: {
                    filter: true,
                    service_id: job.jobservice.service_id,
                    has_cat: job.property_address.is_cat_avail,
                    has_dog: job.property_address.is_dog_avail,
                    prefer_type: job.property_address.prefer_type,
                    ignore_worker_ids: isSameWorker.current.checked
                        ? ""
                        : job.worker_id,
                    only_worker_ids: isSameWorker.current.checked
                        ? job.worker_id
                        : "",
                    job_id: job.id,
                },
            })
            .then((res) => {
                setAllWorkers(res.data.workers);


                const workerAvailityData = getWorkerAvailabilities(res?.data?.workers);

                setWorkerAvailabilities(workerAvailityData);

                setLoading(false);
            });
    };

    useEffect(() => {
        getTime();

        $("#edit-work-time").modal({
            backdrop: "static",
            keyboard: false,
        });
    }, []);

    useEffect(() => {
        setMinUntilDate(
            moment().startOf("day").add(1, "day").format("YYYY-MM-DD")
        );

        return () => setMinUntilDate(null);
    }, []);

    useEffect(() => {
        setSelectedHours([
            {
                jobHours: jobHours,
                slots: null,
                formattedSlots: null,
            },
        ]);
    }, [jobHours]);

    const handleSubmit = () => {
        if (!formValues.repeatancy) {
            alert.error(t("client.jobs.change.Repeatancy"));
            return false;
        }

        if (formValues.repeatancy == "until_date" && !formValues.until_date) {
            alert.error(t("client.jobs.change.UntilDate"));
            return false;
        }

        if (selectedHours) {
            const unfilled = selectedHours.find((worker) => {
                return worker.slots == null;
            });
            if (unfilled) {
                alert.error(t("client.jobs.change.pleaseSelectAllWorker"));
            } else {
                const data = [];
                selectedHours.forEach((worker, index) => {
                    worker?.formattedSlots?.forEach((slots) => {
                        data.push(slots);
                    });
                });

                let formdata = {
                    worker: data[0],
                    repeatancy: formValues.repeatancy,
                    until_date: formValues.until_date,
                    updatedJobs: updatedJobs,
                };
                let viewbtn = document.querySelectorAll(".viewBtn");
                if (data.length > 0) {
                    viewbtn[0].setAttribute("disabled", true);
                    viewbtn[0].value = t("client.jobs.change.pleaseWait");

                    axios
                        .post(
                           ` /api/client/jobs/${jobId}/change-worker`,
                            formdata,
                            {
                                headers,
                            }
                        )
                        .then((res) => {
                            alert.success(res.data.message);
                            setTimeout(() => {
                                navigate("/client/jobs");
                            }, 1000);
                        })
                        .catch((e) => {
                            Swal.fire({
                                title: "Error!",
                                text: e.response.data.message,
                                icon: "error",
                            });
                        });
                } else {
                    viewbtn[0].removeAttribute("disabled");
                    viewbtn[0].value = t("client.jobs.change.viewJob");
                    alert.error(t("client.jobs.change.pleaseSelectWorker"));
                }
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

    const feeInAmount = useMemo(() => {
        const diffInDays = moment(job.start_date).diff(
            moment().startOf("day"),
            "days"
        );

        const _feePercentage = diffInDays >= 1 ? 50 : 100;

        return job.total_amount * (_feePercentage / 100);
    }, [job]);

    const handleWorkerList = () => {
        getWorkers();

        $("#edit-work-time").modal("hide");
    };    
    

    return (
        <>
            <div className="row mb-3">
                <div className="col-sm-12" style={{ rowGap: "0.5rem" }}>
                    <div
                        className="mr-3 col-12 col-lg-3"
                        style={{ fontWeight: "bold" }}
                    >
                        {t("client.jobs.change.worker_availability")}
                    </div>
                    <div className="col-12 col-lg-9 d-flex align-items-center flex-wrap float-left">
                        <FilterButtons
                            text={t("client.jobs.change.currentWeek")}
                            className="px-3 mr-2 mb-2 mb-sm-0"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />

                        <FilterButtons
                            text={t("client.jobs.change.nextWeek")}
                            className="px-3 mr-2 mb-2 mb-sm-0"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />

                        <FilterButtons
                            text={t("client.jobs.change.nextnextWeek")}
                            className="px-3 mr-2"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />

                        <FilterButtons
                            text={t("client.jobs.change.Custom")}
                            className="px-3 mr-2"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />
                    </div>

                    <div className="float-right" style={{ width: "150px" }}>
                        <input
                            type="text"
                            className="form-control form-control-sm mt-2 mt-lg-0"
                            placeholder={t("client.jobs.change.Search")}
                            onChange={(e) => {
                                setSearchVal(e.target.value);
                            }}
                        />
                    </div>
                </div>
            </div>
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
                            isClient={true}
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
                    <WorkerAvailabilityTable
                        workerAvailabilities={workerAvailabilities}
                        week={nextweek}
                        AllWorkers={AllWorkers}
                        hasActive={hasActive}
                        changeShift={changeShift}
                        removeShift={removeShift}
                        selectedHours={selectedHours}
                        searchKeyword={searchVal}
                        isClient={true}
                    />
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
                            isClient={true}
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
                            {t("client.jobs.change.selectDate")}
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
                                isClient={true}
                            />
                        </div>
                    )}
                </div>
            </div>
            <div className="form-group text-center mt-3">
                <input
                    type="button"
                    value="View Job"
                    className="btn btn-pink viewBtn"
                    data-toggle="modal"
                    data-target="#exampleModal"
                />
            </div>
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
                                {t("client.jobs.change.viewJob")}
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
                                                <th scope="col">
                                                    {t(
                                                        "client.jobs.change.services"
                                                    )}
                                                </th>
                                                <th scope="col">
                                                    {t(
                                                        "client.jobs.change.frequency"
                                                    )}
                                                </th>
                                                {/* <th scope="col">
                                                    Time to Complete
                                                </th> */}
                                                <th scope="col">
                                                    {t(
                                                        "client.jobs.change.property"
                                                    )}
                                                </th>
                                                <th scope="col">
                                                    {t(
                                                        "client.jobs.change.gender_preference"
                                                    )}
                                                </th>
                                                <th scope="col">
                                                    {t(
                                                        "client.jobs.change.pet_animals"
                                                    )}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    {" "}
                                                    <p>{job.jobservice.name}</p>
                                                </td>
                                                <td>
                                                    <p>
                                                        {
                                                            job.jobservice
                                                                .freq_name
                                                        }
                                                    </p>
                                                </td>
                                                {/* <td>
                                                    <p>
                                                        {convertMinsToDecimalHrs(
                                                            job.jobservice
                                                                .duration_minutes
                                                        )}{" "}
                                                        hours
                                                    </p>
                                                </td> */}
                                                <td>
                                                    <p>
                                                        {
                                                            job.property_address
                                                                .address_name
                                                        }
                                                    </p>
                                                </td>
                                                <td
                                                    style={{
                                                        textTransform:
                                                            "capitalize",
                                                    }}
                                                >
                                                    <p>
                                                        {
                                                            job.property_address
                                                                .prefer_type
                                                        }
                                                    </p>
                                                </td>
                                                <td>
                                                    <p>
                                                        {job.property_address
                                                            .is_cat_avail
                                                            ? "Cat ,"
                                                            : job
                                                                  .property_address
                                                                  .is_dog_avail
                                                            ? "Dog"
                                                            : !job
                                                                  .property_address
                                                                  .is_cat_avail &&
                                                              !job
                                                                  .property_address
                                                                  .is_dog_avail
                                                            ? "NA"
                                                            : ""}
                                                    </p>
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
                                                    <th scope="col">
                                                        {t(
                                                            "client.jobs.change.Worker"
                                                        )}
                                                    </th>
                                                    <th scope="col">
                                                        {t(
                                                            "client.jobs.change.date"
                                                        )}
                                                    </th>
                                                    <th scope="col">
                                                        {t(
                                                            "client.jobs.change.shift"
                                                        )}
                                                    </th>
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

                            <div className="row">
                                <div className="offset-sm-4 col-sm-4">
                                    <p className="mb-4">
                                        {t(
                                            "client.jobs.change.Cancellationfee",
                                            { feeInAmount }
                                        )}
                                    </p>

                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("client.jobs.change.Repeatancy")}
                                        </label>

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
                                                {t(
                                                    "client.jobs.change.oneTime"
                                                )}
                                            </option>
                                            <option value="until_date">
                                                {t(
                                                    "client.jobs.change.UntilDate"
                                                )}
                                            </option>
                                            <option value="forever">
                                                {t(
                                                    "client.jobs.change.Forever"
                                                )}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                {formValues.repeatancy == "until_date" && (
                                    <div className="offset-sm-4 col-sm-4">
                                        <div className="form-group">
                                            <label className="control-label">
                                                {t(
                                                    "client.jobs.change.UntilDate"
                                                )}
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
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button
                                type="button"
                                className="btn btn-secondary closeb"
                                data-dismiss="modal"
                            >
                                {t("client.jobs.change.Close")}
                            </button>
                            <button
                                type="button"
                                onClick={handleSubmit}
                                className="btn btn-primary"
                                data-dismiss="modal"
                            >
                                {t("client.jobs.change.SaveAndSend")}
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
                                {t("client.jobs.change.SelectService")}
                            </h5>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="col-sm-12 mb-4">
                                    <div className="form-check">
                                        <label className="form-check-label">
                                            <input
                                                ref={isSameWorker}
                                                type="checkbox"
                                                className="form-check-input"
                                            />
                                            {t(
                                                "client.jobs.change.KeepSameWorker"
                                            )}
                                        </label>
                                    </div>
                                </div>
                                <div className="col-sm-12 mb-4">
                                    <button
                                        type="button"
                                        className="btn btn-primary"
                                        onClick={handleWorkerList}
                                    >
                                        {t("client.jobs.change.Continue")}
                                    </button>
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