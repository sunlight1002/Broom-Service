import React, { useState, useEffect, useRef, useMemo } from "react";
import moment from "moment-timezone";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import { useTranslation } from "react-i18next";

import {
    convertShiftsFormat,
    getAvailableSlots,
    getWorkerAvailabilities,
    getWorkersData,
} from "../../../Utils/job.utils";
import WorkerAvailabilityTable from "./WorkerAvailabilityTable";
import { convertMinsToDecimalHrs } from "../../../Utils/common.utils";
import MiniLoader from "../../../Components/common/MiniLoader";

export default function ChangeWorkerCalender({ job }) {
    const [workerAvailabilities, setWorkerAvailabilities] = useState([]);
    const [selectedHours, setSelectedHours] = useState([]);
    const [updatedJobs, setUpdatedJobs] = useState([]);
    const [AllWorkers, setAllWorkers] = useState([]);
    const [days, setDays] = useState([]);
    const [formValues, setFormValues] = useState({
        fee: "0",
        repeatancy: "one_time",
        until_date: null,
    });
    const [minUntilDate, setMinUntilDate] = useState(null);
    const [currentFilter, setcurrentFilter] = useState("Current Week");
    const [searchVal, setSearchVal] = useState("");
    const [customDateRange, setCustomDateRange] = useState([]);
    const [miniLoader, setMiniLoader] = useState(false);

    const params = useParams();
    const navigate = useNavigate();
    const alert = useAlert();

    const { t } = useTranslation();
    const flatpickrRef = useRef(null);
    let isSameWorker = useRef();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getTime = () => {
        axios.get(`/api/admin/get-time`, { headers }).then((res) => {
            if (res.data.data) {
                let ar = JSON.parse(res.data?.data?.days);
                setDays(ar);
            }
        });
    };

    const getWorkers = () => {
        setMiniLoader(true);
        axios
            .get(`/api/admin/all-workers`, {
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
                setMiniLoader(false);
                setAllWorkers(res.data?.workers);
                setWorkerAvailabilities(
                    getWorkerAvailabilities(res.data.workers)
                );
            });
    };

    useEffect(() => {
        getTime();

        // $("#edit-work-time").modal({
        //     backdrop: "static",
        //     keyboard: false,
        // });
    }, []);

    useEffect(() => {
        getWorkers();
    }, [])

    useEffect(() => {
        setMinUntilDate(
            moment().startOf("day").add(1, "day").format("YYYY-MM-DD")
        );
    }, []);

    useEffect(() => {
        setSelectedHours([
            {
                jobHours: job.jobservice.duration_minutes / 60,
                slots: null,
                formattedSlots: null,
            },
        ]);
    }, [job]);

    const handleSubmit = () => {
        if (!formValues.repeatancy) {
            alert.error("The Repeatancy is missing");
            return false;
        }

        if (formValues.repeatancy == "until_date" && !formValues.until_date) {
            alert.error("The Until Date is missing");
            return false;
        }

        if (!formValues.fee) {
            alert.error("The fee is missing");
            return false;
        }

        if (selectedHours) {
            const unfilled = selectedHours.find((worker) => {
                return worker.slots == null;
            });
            if (unfilled) {
                alert.error("Please select all workers.");
            } else {
                const data = [];
                selectedHours.forEach((worker, index) => {
                    worker?.formattedSlots?.forEach((slots) => {
                        data.push(slots);
                    });
                });

                let formdata = {
                    worker: data[0],
                    fee: formValues.fee,
                    repeatancy: formValues.repeatancy,
                    until_date: formValues.until_date,
                    updatedJobs: updatedJobs,
                };
                let viewbtn = document.querySelectorAll(".viewBtn");
                if (data?.length > 0) {
                    viewbtn[0].setAttribute("disabled", true);
                    viewbtn[0].value = "please wait ...";

                    axios
                        .post(
                            `/api/admin/jobs/${params.id}/change-worker`,
                            formdata,
                            {
                                headers,
                            }
                        )
                        .then((res) => {
                            alert.success(res.data?.message);
                            setTimeout(() => {
                                navigate("/admin/jobs");
                            }, 1000);
                        })
                        .catch((e) => {
                            Swal.fire({
                                title: "Error!",
                                text: e.response?.data?.message,
                                icon: "error",
                            });
                        });
                } else {
                    viewbtn[0].removeAttribute("disabled");
                    viewbtn[0].value = "View Job";
                    alert.error("Please Select the Workers");
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
        let added = false;
        const promises = selectedHours.map(async (worker, index) => {
            if (
                (worker.slots == null || worker?.slots[0]?.workerId == w_id) &&
                !added
            ) {
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
                added = true;
                return {
                    jobHours: worker.jobHours,
                    slots: slots.length > 0 ? slots : null,
                    formattedSlots:
                        slots.length > 0 ? convertShiftsFormat(slots) : null,
                };
            }
            if (!added && selectedHours.length === index + 1) {
                alert.error("Already other workers selected.");
            }
            return worker;
        });

        // Wait for all promises to resolve
        Promise.all(promises).then((updatedData) => {
            // Update the state with the resolved values
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

    const handleWorkerList = () => {
        getWorkers();

    };

    const feeInAmount = useMemo(() => {
        return job.total_amount * (formValues.fee / 100);
    }, [formValues.fee]);

    return (
        <>
            <div className="row mb-3">
                <div className="col-sm-12" style={{ rowGap: "0.5rem" }}>
                    <div className="d-flex align-items-center flex-wrap float-left">
                        <div className="mr-3" style={{ fontWeight: "bold" }}>
                            {t("client.jobs.change.worker_availability")}
                        </div>
                        <FilterButtons
                            text={t("client.jobs.change.currentWeek")}
                            className="px-3 mr-2 mb-2"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />

                        <FilterButtons
                            text={t("client.jobs.change.nextWeek")}
                            className="px-3 mr-2 mb-2"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />

                        <FilterButtons
                            text={t("client.jobs.change.nextnextWeek")}
                            className="px-3 mr-2 mb-2"
                            selectedFilter={currentFilter}
                            setselectedFilter={setcurrentFilter}
                        />

                        <FilterButtons
                            text={t("client.jobs.change.Custom")}
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
                                ref={isSameWorker}
                                type="checkbox"
                                className="form-check-input"
                                onChange={handleWorkerList}
                            />
                            {t("client.jobs.change.KeepSameWorker")}
                        </label>
                    </div>
                </div>
            </div>
            {
                miniLoader !== true ? (
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
                                    {t("worker.schedule.select_date_range")}
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
                ) : <div className="d-flex justify-content-center"><MiniLoader /></div>}

            <div className="form-group text-center mt-3">
                <input
                    type="button"
                    value={t("global.viewJob")}
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
                                {t("global.viewJob")}
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
                                                <th scope="col">{t("worker.jobs.client")}</th>
                                                <th scope="col">{t("worker.jobs.service")}</th>
                                                <th scope="col">{t("client.offer.view.frequency")}</th>
                                                <th scope="col">
                                                    {t("client.jobs.change.time_to_complete")}
                                                </th>
                                                <th scope="col">{t("client.jobs.change.propert")}</th>
                                                <th scope="col">
                                                    {t("client.jobs.change.gender_preference")}
                                                </th>
                                                <th scope="col"> {t("client.jobs.change.pet_animals")}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    {`${job.client.firstname} ${job.client.lastname}`}
                                                </td>
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
                                                <td>
                                                    <p>
                                                        {convertMinsToDecimalHrs(
                                                            job.jobservice
                                                                .duration_minutes
                                                        )}{" "}
                                                        {t("client.jobs.review.hours")}
                                                    </p>
                                                </td>
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
                                                            ? t("admin.leads.AddLead.addAddress.Cat")
                                                            : job
                                                                .property_address
                                                                .is_dog_avail
                                                                ? t("admin.leads.AddLead.addAddress.Dog")
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
                                                    <th scope="col">{t("client.jobs.change.Worker")}</th>
                                                    <th scope="col">{t("client.jobs.change.date")}</th>
                                                    <th scope="col">{t("client.jobs.change.shift")}</th>
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
                                                {t("client.jobs.change.oneTime")}
                                            </option>
                                            <option value="until_date">
                                                {t("client.jobs.change.UntilDate")}
                                            </option>
                                            <option value="forever">
                                                {t("client.jobs.change.Forever")}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                {formValues.repeatancy == "until_date" && (
                                    <div className="offset-sm-4 col-sm-4">
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

                                <div className="offset-sm-4 col-sm-4">
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
                                                    handleFeeChange(
                                                        e.target.value
                                                    );
                                                }}
                                                style={{ height: "unset" }}
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
                                                    handleFeeChange(
                                                        e.target.value
                                                    );
                                                }}
                                                style={{ height: "unset" }}
                                            />
                                            <label
                                                className="form-check-label"
                                                htmlFor="fee100"
                                            >
                                                100%
                                            </label>
                                        </div>

                                        {feeInAmount > 0 ? (
                                            <p>
                                                {feeInAmount} ILS {t("admin.global.willBeCharged")}.
                                            </p>
                                        ) : (
                                            <p>
                                                {t(
                                                    "admin.schedule.jobs.CancelModal.NoCharge"
                                                )}
                                            </p>
                                        )}
                                    </div>
                                </div>
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

            {/* <div
                className="modal fade"
                id="edit-work-time"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
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
                                            Keep same worker
                                        </label>
                                    </div>
                                </div>
                                <div className="col-sm-12 mb-4">
                                    <button
                                        type="button"
                                        className="btn btn-primary"
                                        onClick={handleWorkerList}
                                    >
                                        Continue
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> */}
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
