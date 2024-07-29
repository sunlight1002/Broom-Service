import axios from "axios";
import React, { useState, useEffect } from "react";
import moment from "moment-timezone";
import { useNavigate, useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import { useTranslation } from "react-i18next";

import Sidebar from "../../Layouts/Sidebar";
import WorkerAvailabilityTable from "../../Components/Job/WorkerAvailabilityTable";
import {
    convertShiftsFormat,
    getAvailableSlots,
    getWorkerAvailabilities,
    getWorkersData,
} from "../../../Utils/job.utils";

export default function FreezeShiftWorkers() {
    const { t } = useTranslation();
    const params = useParams();
    const navigate = useNavigate();
    const alert = useAlert();
    const [workerAvailabilities, setWorkerAvailabilities] = useState([]);
    const [startDate, setStartDate] = useState(null);
    const [selectedHours, setSelectedHours] = useState([]);
    const [removedSlots, setRemovedSlots] = useState([]);
    const [workerFreezeDates, setWorkerFreezeDates] = useState([]);
    const [AllWorkers, setAllWorkers] = useState([]);
    const [days, setDays] = useState([]);
    const [currentFilter, setcurrentFilter] = useState("Current Week");
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const [customDateRange, setCustomDateRange] = useState([]);

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
        getWorkers();
        getWorkerFreezeDates();
    }, []);

    useEffect(() => {
        if (
            workerAvailabilities.length > 0 &&
            workerFreezeDates.length > 0 &&
            AllWorkers.length > 0
        ) {
            workerFreezeDates.forEach((freezeDate) => {
                let start = moment(
                    freezeDate.date + " " + freezeDate.start_time
                );
                let end = moment(freezeDate.date + " " + freezeDate.end_time);
                let workerFreezeSlots = [];
                workerAvailabilities.forEach((worker) => {
                    let slots = [];
                    if (
                        worker.slots.length > 0 &&
                        worker.workerId == freezeDate.user_id
                    ) {
                        worker.slots.forEach((slot) => {
                            if (slot.allSlots.length > 0) {
                                slot.allSlots.forEach((s) => {
                                    if (!s.isBooked && !s.notAvailable) {
                                        let slotDate = moment(
                                            slot.date + " " + s.time
                                        );
                                        if (
                                            (slotDate.isAfter(start) &&
                                                slotDate.isBefore(end)) ||
                                            (slotDate.isAfter(end) &&
                                                slotDate.isBefore(start)) ||
                                            slotDate.isSame(start)
                                        ) {
                                            slots.push({
                                                time: s,
                                                date: slot.date,
                                            });
                                        }
                                    }
                                });
                            }
                        });
                    }

                    if (slots.length > 0) {
                        workerFreezeSlots.push({
                            id: freezeDate.id,
                            workerId: worker.workerId,
                            slots,
                        });
                    }
                });
                setSelectedHours((data) => {
                    return data.concat(workerFreezeSlots);
                });
            });
        }
    }, [workerAvailabilities, workerFreezeDates, AllWorkers]);

    const getWorkers = () => {
        axios
            .get(`/api/admin/all-workers`, {
                headers,
                params: {
                    filter: true,
                    only_worker_ids: params.id,
                },
            })
            .then((res) => {
                setAllWorkers(res.data.workers);
                setWorkerAvailabilities(
                    getWorkerAvailabilities(res.data.workers)
                );
            });
    };

    const getWorkerFreezeDates = () => {
        axios
            .get(`/api/admin/workers/workers/freeze-shift/${params.id}`, {
                headers,
            })
            .then((res) => {
                setWorkerFreezeDates(res.data.data);
            });
    };

    const handleSubmit = () => {
        if (selectedHours) {
            const unfilled = selectedHours?.find((worker) => {
                return worker.slots == null;
            });
            if (unfilled) {
                alert.error("Please select all workers.");
            } else {
                const data = selectedHours?.map((worker, index) => {
                    let formattedSlots = convertShiftsFormat(
                        worker?.slots?.map((s) => {
                            return {
                                ...s,
                                workerId: worker.workerId,
                                workerName: "test",
                            };
                        })
                    );
                    return {
                        ...worker,
                        formattedSlots,
                    };
                });
                let formdata = {
                    workers: getWorkersData(data),
                    removedSlots,
                };
                let viewbtn = document.querySelectorAll(".viewBtn");
                viewbtn[0].setAttribute("disabled", true);
                viewbtn[0].value = "please wait ...";

                axios
                    .post(`/api/admin/workers/freeze-shift`, formdata, {
                        headers,
                    })
                    .then((res) => {
                        alert.success(res.data.message);
                        setTimeout(() => {
                            navigate("/admin/workers");
                        }, 1000);
                    })
                    .catch((e) => {
                        Swal.fire({
                            title: "Error!",
                            text: e.response.data.message,
                            icon: "error",
                        });
                    });
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
        if (!startDate) {
            setStartDate({
                workerId: w_id,
                date: date,
                time: e.time,
                isBooked: e.isBooked,
                isFreezed: e.isFreezed,
                notAvailable: e.notAvailable,
            });
        } else {
            let start = moment(startDate.date + " " + startDate.time);
            let end = moment(date + " " + e.time);
            let workerFreezeSlots = [];
            workerAvailabilities.forEach((worker) => {
                let slots = [];
                if (worker.slots.length > 0 && worker.workerId == w_id) {
                    worker.slots.forEach((slot) => {
                        if (slot.allSlots.length > 0) {
                            slot.allSlots.forEach((s) => {
                                if (!s.isBooked && !s.notAvailable) {
                                    let slotDate = moment(
                                        slot.date + " " + s.time
                                    );
                                    if (
                                        (slotDate.isAfter(start) &&
                                            slotDate.isBefore(end)) ||
                                        (slotDate.isAfter(end) &&
                                            slotDate.isBefore(start)) ||
                                        slotDate.isSame(start) ||
                                        slotDate.isSame(end)
                                    ) {
                                        slots.push({
                                            time: s,
                                            date: slot.date,
                                        });
                                    }
                                }
                            });
                        }
                    });
                }

                if (slots.length > 0) {
                    workerFreezeSlots.push({
                        workerId: worker.workerId,
                        slots,
                    });
                }
            });
            setStartDate(null);
            setSelectedHours((data) => {
                return data.concat(workerFreezeSlots);
            });
        }
    };

    const removeShift = (w_id, date, shift) => {
        setSelectedHours((data) => {
            return data.filter((worker) => {
                const slot = worker.slots?.find((s) => {
                    return (
                        worker.workerId == w_id &&
                        s.date == date &&
                        shift.time == s.time.time
                    );
                });
                if (slot) {
                    if (worker?.id) {
                        setRemovedSlots((d) => {
                            d.push({
                                workerId: worker.workerId,
                                id: worker.id,
                            });
                            return d;
                        });
                    }
                    return false;
                }
                return true;
            });
        });
    };

    const hasActive = (w_id, date, shift) => {
        if (selectedHours) {
            const filtered = selectedHours?.find((worker) => {
                if (worker.slots != null) {
                    const slot = worker.slots?.find((s) => {
                        return (
                            worker.workerId == w_id &&
                            s.date == date &&
                            shift.time == s.time.time
                        );
                    });
                    if (slot) {
                        return true;
                    }
                }
                return false;
            });
            if (filtered) {
                return true;
            }
        }
        if (startDate) {
            return (
                startDate.workerId == w_id &&
                startDate.date == date &&
                startDate.time == shift.time
            );
        }
        return false;
    };

    return (
        <div id="container">
            <Sidebar />
            <div id="content">
                <div className="titleBox customer-title">
                    <div className="row">
                        <div className="col-sm-6">
                            <h1 className="page-title">{t("global.freezeShift")}</h1>
                        </div>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body">
                        <div className="boxPanel">
                            <div className="row mb-3">
                                <div className="col-sm-12 d-flex flex-wrap align-items-center">
                                    <div
                                        className="mr-3"
                                        style={{ fontWeight: "bold" }}
                                    >
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
                            </div>
                            <div
                                className="tab-content"
                                style={{ background: "#fff" }}
                            >
                                <div
                                    style={{
                                        display:
                                            currentFilter === "Current Week"
                                                ? "block"
                                                : "none",
                                    }}
                                    id="tab-worker-availability"
                                    className="tab-pane active show  table-responsive"
                                    role="tab-panel"
                                    aria-labelledby="current-job"
                                >
                                    <div className="crt-jb-table-scrollable">
                                        <WorkerAvailabilityTable
                                            workerAvailabilities={
                                                workerAvailabilities
                                            }
                                            week={week}
                                            AllWorkers={AllWorkers}
                                            hasActive={hasActive}
                                            changeShift={changeShift}
                                            removeShift={removeShift}
                                        />
                                    </div>
                                </div>

                                <div
                                    style={{
                                        display:
                                            currentFilter === "Next Week"
                                                ? "block"
                                                : "none",
                                    }}
                                    id="tab-current-job"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="current-job"
                                >
                                    <div className="crt-jb-table-scrollable">
                                        <WorkerAvailabilityTable
                                            workerAvailabilities={
                                                workerAvailabilities
                                            }
                                            week={nextweek}
                                            AllWorkers={AllWorkers}
                                            hasActive={hasActive}
                                            changeShift={changeShift}
                                            removeShift={removeShift}
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
                                            workerAvailabilities={
                                                workerAvailabilities
                                            }
                                            week={nextnextweek}
                                            AllWorkers={AllWorkers}
                                            hasActive={hasActive}
                                            changeShift={changeShift}
                                            removeShift={removeShift}
                                        />
                                    </div>
                                </div>
                                <div
                                    style={{
                                        display:
                                            currentFilter === "Custom"
                                                ? "block"
                                                : "none",
                                    }}
                                    id="tab-current-next-job"
                                    className="tab-pane"
                                    role="tab-panel"
                                    aria-labelledby="current-job"
                                >
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t(global.selectDateRange)}
                                        </label>
                                        <Flatpickr
                                            name="date"
                                            className="form-control"
                                            onChange={(
                                                selectedDates,
                                                dateStr,
                                                instance
                                            ) => {
                                                let start = moment(
                                                    selectedDates[0]
                                                );
                                                let end = moment(
                                                    selectedDates[1]
                                                );
                                                const datesArray = [];

                                                for (
                                                    let date = start.clone();
                                                    date.isSameOrBefore(end);
                                                    date.add(1, "day")
                                                ) {
                                                    datesArray.push(
                                                        date.format(
                                                            "YYYY-MM-DD"
                                                        )
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
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="form-group text-center mt-3">
                                <input
                                    type="button"
                                    value="Save"
                                    className="btn btn-pink viewBtn"
                                    onClick={handleSubmit}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
